<?php

namespace Tests\Feature\Phase8;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 8 B2 — regression fences for `copyToOffsite` + the
 * `copy_after_snapshot` integration hook. R2 (real network) is exercised
 * manually by the developer; every test in this file uses `Storage::fake`
 * for both the local and offsite disks so the entire pipeline runs
 * against real (temporary) local files without touching the network.
 */
class BackupOffsiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            // Point both local and offsite at faked local disks — same
            // Filesystem contract, so BackupService can't tell the
            // difference.
            'backup.disks.db'                     => 'local-backup-test',
            'backup.disks.media'                  => 'local-backup-test',
            'backup.disks.db_offsite'             => 'offsite-backup-test',
            'backup.disks.media_offsite'          => 'offsite-backup-test',
            'backup.offsite.copy_after_snapshot'  => true,
            'backup.offsite.retry_attempts'       => 2,
            'backup.offsite.retry_delay_seconds'  => 0,
            'backup.offsite.alert_email'          => 'ops@example.test',
            'backup.verify.enabled'               => true,
            'backup.verify.min_size_bytes'        => 1,
        ]);

        Storage::fake('local-backup-test');
        Storage::fake('offsite-backup-test');
    }

    private function seedLocalBackup(string $type = Backup::TYPE_DB, string $content = 'stub-bytes-for-offsite'): Backup
    {
        $path = $type === Backup::TYPE_DB
            ? 'backups/db/20260101-000000-000.sql.gz'
            : 'backups/media/20260101-000000-000.zip';

        Storage::disk('local-backup-test')->put($path, $content);
        Storage::disk('local-backup-test')->put($path.'.sha256', 'sidecar');

        return $this->app->make(BackupService::class)->record([
            'type'            => $type,
            'disk'            => 'local-backup-test',
            'path'            => $path,
            'size_bytes'      => strlen($content),
            'checksum_sha256' => hash('sha256', $content),
            'status'          => Backup::STATUS_OK,
        ]);
    }

    /* -------------------- copyToOffsite -------------------- */

    public function test_copy_to_offsite_writes_file_to_offsite_disk_and_updates_meta(): void
    {
        $backup = $this->seedLocalBackup();
        $service = $this->app->make(BackupService::class);

        $updated = $service->copyToOffsite($backup);

        Storage::disk('offsite-backup-test')->assertExists($backup->path);
        Storage::disk('offsite-backup-test')->assertExists($backup->path.'.sha256');

        $meta = (array) $updated->meta;
        $this->assertSame('offsite-backup-test', $meta['offsite_disk'] ?? null);
        $this->assertSame($backup->path, $meta['offsite_path'] ?? null);
        $this->assertNotEmpty($meta['offsite_verified_at'] ?? null);
        $this->assertSame(1, $meta['offsite_attempts'] ?? null);
    }

    public function test_copy_to_offsite_is_a_noop_when_offsite_disk_not_configured(): void
    {
        config(['backup.disks.db_offsite' => null]);

        $backup  = $this->seedLocalBackup();
        $updated = $this->app->make(BackupService::class)->copyToOffsite($backup);

        $meta = (array) $updated->meta;
        $this->assertArrayNotHasKey('offsite_disk', $meta);
        Storage::disk('offsite-backup-test')->assertMissing($backup->path);
    }

    public function test_copy_to_offsite_marks_failure_when_source_missing(): void
    {
        $backup = $this->seedLocalBackup();
        Storage::disk('local-backup-test')->delete($backup->path);

        Mail::fake();
        $updated = $this->app->make(BackupService::class)->copyToOffsite($backup);

        $meta = (array) $updated->meta;
        $this->assertArrayNotHasKey('offsite_verified_at', $meta);
        $this->assertStringContainsString('source file missing', (string) ($meta['offsite_error'] ?? ''));
    }

    public function test_copy_to_offsite_records_offsite_error_meta_on_failure(): void
    {
        // Alert mail is a best-effort side-effect that Mail::fake doesn't
        // reliably capture for `Mail::raw()` calls — the operationally
        // critical signal is that the failure gets recorded on the row so
        // the dashboard + `backup:sync-offsite` see it.
        $backup = $this->seedLocalBackup();
        Storage::disk('local-backup-test')->delete($backup->path);

        $updated = $this->app->make(BackupService::class)->copyToOffsite($backup);

        $meta = (array) $updated->meta;
        $this->assertNotEmpty($meta['offsite_error'] ?? '');
        $this->assertNotEmpty($meta['offsite_last_attempt_at'] ?? '');
    }

    public function test_copy_to_offsite_returns_backup_unchanged_when_status_not_ok(): void
    {
        $backup = $this->seedLocalBackup();
        $backup->update(['status' => Backup::STATUS_FAILED]);

        $updated = $this->app->make(BackupService::class)->copyToOffsite($backup->fresh() ?? $backup);

        Storage::disk('offsite-backup-test')->assertMissing($backup->path);
        $meta = (array) $updated->meta;
        $this->assertArrayNotHasKey('offsite_verified_at', $meta);
    }

    /* -------------------- copyToOffsiteIfConfigured -------------------- */

    public function test_configured_hook_skips_when_master_toggle_off(): void
    {
        config(['backup.offsite.copy_after_snapshot' => false]);

        $backup  = $this->seedLocalBackup();
        $updated = $this->app->make(BackupService::class)->copyToOffsiteIfConfigured($backup);

        Storage::disk('offsite-backup-test')->assertMissing($backup->path);
        $meta = (array) $updated->meta;
        $this->assertArrayNotHasKey('offsite_verified_at', $meta);
    }

    public function test_configured_hook_skips_when_no_offsite_disk_configured(): void
    {
        config([
            'backup.disks.db_offsite' => null,
            'backup.disks.media_offsite' => null,
        ]);

        $backup  = $this->seedLocalBackup();
        $updated = $this->app->make(BackupService::class)->copyToOffsiteIfConfigured($backup);

        Storage::disk('offsite-backup-test')->assertMissing($backup->path);
        $meta = (array) $updated->meta;
        $this->assertArrayNotHasKey('offsite_verified_at', $meta);
    }

    /* -------------------- backup:sync-offsite command -------------------- */

    public function test_command_is_registered(): void
    {
        $this->assertArrayHasKey(
            'backup:sync-offsite',
            $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->all(),
        );
    }

    public function test_command_syncs_specific_backup_by_id(): void
    {
        $b1 = $this->seedLocalBackup(Backup::TYPE_DB, 'first');
        $b2 = $this->seedLocalBackup(Backup::TYPE_MEDIA, 'second');

        $this->artisan('backup:sync-offsite', ['backup_id' => $b2->id])->assertSuccessful();

        // Only b2 has been mirrored.
        Storage::disk('offsite-backup-test')->assertExists($b2->path);
        Storage::disk('offsite-backup-test')->assertMissing($b1->path);
    }

    public function test_command_skips_already_verified_backups_without_force(): void
    {
        $backup = $this->seedLocalBackup();
        $backup->update([
            'meta' => ['offsite_verified_at' => now()->toIso8601String()],
        ]);

        $this->artisan('backup:sync-offsite')
            ->expectsOutputToContain('skipped=1')
            ->assertSuccessful();
    }

    public function test_command_rejects_invalid_type(): void
    {
        $this->artisan('backup:sync-offsite', ['--type' => 'garbage'])
            ->expectsOutputToContain('Invalid --type')
            ->assertExitCode(2);
    }
}
