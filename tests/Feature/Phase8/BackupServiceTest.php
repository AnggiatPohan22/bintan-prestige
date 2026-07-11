<?php

namespace Tests\Feature\Phase8;

use App\Models\Backup;
use App\Services\Backup\DatabaseDumper;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Phase 8 A2 — regression fences for BackupService. The dumper is stubbed so
 * these run against sqlite without a real mysqldump binary. Real dumper
 * behavior is covered by `BackupDatabaseCommandTest` (surface) + the manual
 * runbook rehearsal (integration).
 */
class BackupServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Point BackupService at the mysql connection metadata for the driver
        // check, while the Backup model itself keeps writing to the default
        // sqlite (:memory:) test DB via `database.default`. The stub dumper
        // prevents any actual MySQL connection attempt.
        config([
            'backup.source_connection'          => 'mysql',
            'database.connections.mysql.driver' => 'mysql',
            'backup.disks.db'                   => 'local-backup-test',
            'backup.paths.db'                   => 'backups/db',
            'backup.retention.db_daily'         => 3, // small window for the prune fence
            'backup.verify.enabled'             => true,
            'backup.verify.min_size_bytes'      => 1, // gzipped stubs are tiny
        ]);

        Storage::fake('local-backup-test');
    }

    private function makeService(?DatabaseDumper $dumper = null): BackupService
    {
        $dumper ??= new StubDumper(exitCode: 0, sqlContent: $this->stubDumpBody());
        $this->app->instance(DatabaseDumper::class, $dumper);

        return $this->app->make(BackupService::class);
    }

    private function stubDumpBody(): string
    {
        // Includes the "MySQL dump" marker so any code that peeks at the
        // header treats it as a real dump. Big enough to survive the
        // min_size_bytes threshold once gzipped.
        return "-- MySQL dump 8.4\n".str_repeat("INSERT INTO x VALUES (1,'a');\n", 40);
    }

    /* -------------------- record -------------------- */

    public function test_record_creates_backup_row_with_all_fields(): void
    {
        $service = $this->makeService();

        $backup = $service->record([
            'type'            => Backup::TYPE_DB,
            'disk'            => 'local-backup-test',
            'path'            => 'backups/db/20260101-000000.sql.gz',
            'size_bytes'      => 1024,
            'checksum_sha256' => str_repeat('a', 64),
            'status'          => Backup::STATUS_OK,
            'purpose'         => 'unit test',
            'meta'            => ['note' => 'ok'],
        ]);

        $this->assertDatabaseHas('backups', ['id' => $backup->id, 'status' => 'ok', 'purpose' => 'unit test']);
        $this->assertSame(1024, $backup->size_bytes);
        $this->assertSame(['note' => 'ok'], $backup->meta);
    }

    /* -------------------- dumpDatabase -------------------- */

    public function test_dump_records_ok_backup_and_gzips_output(): void
    {
        $service = $this->makeService();

        $backup = $service->dumpDatabase('Scheduled test');

        $this->assertSame(Backup::STATUS_OK, $backup->status);
        $this->assertNotNull($backup->checksum_sha256);
        $this->assertSame(64, strlen((string) $backup->checksum_sha256));
        $this->assertGreaterThan(0, (int) $backup->size_bytes);
        $this->assertStringStartsWith('backups/db/', (string) $backup->path);
        $this->assertStringEndsWith('.sql.gz', (string) $backup->path);

        Storage::disk('local-backup-test')->assertExists($backup->path);
        Storage::disk('local-backup-test')->assertExists($backup->path.'.sha256');

        // Raw .sql should be gone (only the .gz survives).
        Storage::disk('local-backup-test')->assertMissing(rtrim($backup->path, '.gz'));
    }

    public function test_dump_marks_backup_failed_when_dumper_exit_code_nonzero(): void
    {
        $service = $this->makeService(new StubDumper(exitCode: 1, sqlContent: ''));

        $backup = $service->dumpDatabase('Should fail');

        $this->assertSame(Backup::STATUS_FAILED, $backup->status);
        $this->assertSame(1, ($backup->meta['exit_code'] ?? null));
        $this->assertNotEmpty($backup->meta['error'] ?? '');
    }

    public function test_dump_marks_backup_failed_when_dumper_exits_zero_but_writes_no_bytes(): void
    {
        // Silent 0-byte dump — the class of failure that inspired
        // BackupDatabase's own "trust-but-verify" guard.
        $dumper = new class implements DatabaseDumper
        {
            public function dump(array $connection, string $outputSqlPath, int $timeoutSeconds): int
            {
                @touch($outputSqlPath); // exists but 0 bytes

                return 0;
            }

            public function lastErrorOutput(): string
            {
                return '';
            }
        };

        $service = $this->makeService($dumper);

        $backup = $service->dumpDatabase();

        $this->assertSame(Backup::STATUS_FAILED, $backup->status);
        $this->assertStringContainsString('no bytes', (string) ($backup->meta['error'] ?? ''));
    }

    public function test_dump_throws_when_source_connection_is_not_mysql(): void
    {
        config(['backup.source_connection' => 'sqlite']);

        $service = $this->makeService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("only supports mysql");

        $service->dumpDatabase();
    }

    /* -------------------- verify -------------------- */

    public function test_verify_returns_true_when_checksum_matches(): void
    {
        $service = $this->makeService();
        $backup  = $service->dumpDatabase();

        $this->assertTrue($service->verify($backup));
    }

    public function test_verify_returns_false_when_file_missing(): void
    {
        $service = $this->makeService();
        $backup  = $service->dumpDatabase();

        Storage::disk('local-backup-test')->delete($backup->path);

        $this->assertFalse($service->verify($backup));
    }

    public function test_verify_returns_false_when_checksum_mismatched(): void
    {
        $service = $this->makeService();
        $backup  = $service->dumpDatabase();

        $backup->update(['checksum_sha256' => str_repeat('f', 64)]);

        $this->assertFalse($service->verify($backup->fresh()));
    }

    public function test_verify_returns_false_when_file_smaller_than_min_size(): void
    {
        config(['backup.verify.min_size_bytes' => 999_999]);

        $service = $this->makeService();
        $backup  = $service->dumpDatabase();

        // Force config to a huge threshold AFTER creation so the write itself
        // is not blocked; verify() reads the config live.
        config(['backup.verify.min_size_bytes' => 999_999]);

        $this->assertFalse($service->verify($backup));
    }

    /* -------------------- prune -------------------- */

    public function test_prune_removes_oldest_ok_db_backups_beyond_retention(): void
    {
        $service = $this->makeService();

        // retention.db_daily = 3 (setUp). Create 6 ok db backups.
        for ($i = 1; $i <= 6; $i++) {
            $service->record([
                'type'   => Backup::TYPE_DB,
                'disk'   => 'local-backup-test',
                'path'   => "backups/db/2026010{$i}-000000.sql.gz",
                'status' => Backup::STATUS_OK,
            ]);
            Backup::query()->latest('id')->first()?->update([
                'created_at' => now()->subDays(6 - $i),
            ]);
        }

        $pruned = $service->prune(Backup::TYPE_DB);

        $this->assertSame(3, $pruned, 'should prune 3 rows (6 total - 3 retention)');
        $this->assertSame(3, Backup::where('status', Backup::STATUS_OK)->count());
        $this->assertSame(3, Backup::where('status', Backup::STATUS_PRUNED)->count());
    }

    public function test_prune_leaves_pruned_rows_in_place_for_audit(): void
    {
        $service = $this->makeService();

        for ($i = 1; $i <= 5; $i++) {
            $backup = $service->record([
                'type'   => Backup::TYPE_DB,
                'disk'   => 'local-backup-test',
                'path'   => "backups/db/2026010{$i}-000000.sql.gz",
                'status' => Backup::STATUS_OK,
            ]);
            $backup->update(['created_at' => now()->subDays(5 - $i)]);
        }

        $service->prune(Backup::TYPE_DB);

        // 5 total rows should still exist — none hard-deleted.
        $this->assertSame(5, Backup::count());
    }

    public function test_prune_deletes_associated_files_from_disk(): void
    {
        $service = $this->makeService();
        $disk    = Storage::disk('local-backup-test');

        for ($i = 1; $i <= 5; $i++) {
            $path = "backups/db/2026010{$i}-000000.sql.gz";
            $disk->put($path, 'stub-content');
            $disk->put($path.'.sha256', 'stub-checksum');

            $backup = $service->record([
                'type'   => Backup::TYPE_DB,
                'disk'   => 'local-backup-test',
                'path'   => $path,
                'status' => Backup::STATUS_OK,
            ]);
            $backup->update(['created_at' => now()->subDays(5 - $i)]);
        }

        $service->prune(Backup::TYPE_DB); // retention=3, so 2 oldest get pruned

        // The 2 oldest files (i=1 and i=2) should be gone from disk.
        $disk->assertMissing('backups/db/20260101-000000.sql.gz');
        $disk->assertMissing('backups/db/20260101-000000.sql.gz.sha256');
        $disk->assertMissing('backups/db/20260102-000000.sql.gz');
        $disk->assertMissing('backups/db/20260102-000000.sql.gz.sha256');

        // The 3 newest survive.
        $disk->assertExists('backups/db/20260103-000000.sql.gz');
        $disk->assertExists('backups/db/20260104-000000.sql.gz');
        $disk->assertExists('backups/db/20260105-000000.sql.gz');
    }

    public function test_prune_does_nothing_when_below_retention(): void
    {
        $service = $this->makeService();

        for ($i = 1; $i <= 2; $i++) {
            $service->record([
                'type'   => Backup::TYPE_DB,
                'disk'   => 'local-backup-test',
                'path'   => "backups/db/2026010{$i}-000000.sql.gz",
                'status' => Backup::STATUS_OK,
            ]);
        }

        $this->assertSame(0, $service->prune(Backup::TYPE_DB));
        $this->assertSame(2, Backup::where('status', Backup::STATUS_OK)->count());
    }
}

/**
 * Injectable dumper stub — writes deterministic SQL to the target path so
 * BackupService's post-dump gzip + checksum + verify steps have real content
 * to work with, without touching MySQL.
 */
class StubDumper implements DatabaseDumper
{
    public function __construct(
        private int $exitCode = 0,
        private string $sqlContent = '-- MySQL dump 8.4',
    ) {
    }

    public function dump(array $connection, string $outputSqlPath, int $timeoutSeconds): int
    {
        if ($this->exitCode === 0) {
            file_put_contents($outputSqlPath, $this->sqlContent);
        }

        return $this->exitCode;
    }

    public function lastErrorOutput(): string
    {
        return $this->exitCode === 0 ? '' : "stub error (exit={$this->exitCode})";
    }
}
