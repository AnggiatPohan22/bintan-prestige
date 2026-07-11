<?php

namespace Tests\Feature\Phase8;

use App\Models\Backup;
use App\Services\Backup\MediaSnapshotter;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * Phase 8 B1 — regression fences for the media-snapshot pipeline.
 *
 * The fixture builds a fake `storage/app/public` under a temp dir and points
 * `backup.media.source_paths` at it, so the walker + zipper run against real
 * files without touching the actual dev/storage tree.
 */
class BackupServiceMediaTest extends TestCase
{
    use RefreshDatabase;

    private string $tempMediaRoot = '';
    private string $tempMediaRelative = '';

    protected function setUp(): void
    {
        parent::setUp();

        // A per-test scratch directory. Placed under storage/framework/testing
        // so it's cleaned up between test suites.
        $this->tempMediaRelative = 'framework/testing/backup-media-'.uniqid();
        $this->tempMediaRoot     = storage_path($this->tempMediaRelative);
        File::ensureDirectoryExists($this->tempMediaRoot);

        // Seed a handful of files with predictable contents so hashes are
        // deterministic across runs.
        File::put($this->tempMediaRoot.'/one.txt', 'alpha');
        File::ensureDirectoryExists($this->tempMediaRoot.'/nested');
        File::put($this->tempMediaRoot.'/nested/two.txt', 'beta');
        File::put($this->tempMediaRoot.'/nested/three.txt', 'gamma');

        // The BackupService walker takes source_paths as strings relative to
        // base_path(). storage_path() returns an absolute path — we need it
        // relative to base_path() so the walker's join produces the right
        // absolute directory.
        $rel = str_replace(base_path().DIRECTORY_SEPARATOR, '', $this->tempMediaRoot);

        config([
            'backup.disks.media'               => 'local-media-test',
            'backup.paths.media'               => 'backups/media',
            'backup.media.source_paths'        => [str_replace('\\', '/', $rel)],
            'backup.media.exclude_patterns'    => ['/.DS_Store', '/Thumbs.db'],
            'backup.media.follow_symlinks'     => false,
            'backup.retention.media_weekly'    => 2, // small window for the prune fence
            'backup.verify.enabled'            => true,
            'backup.verify.min_size_bytes'     => 1,
        ]);

        Storage::fake('local-media-test');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempMediaRoot);
        parent::tearDown();
    }

    private function makeService(): BackupService
    {
        return $this->app->make(BackupService::class);
    }

    /* -------------------- snapshotMedia (write path) -------------------- */

    public function test_snapshot_media_records_ok_backup_with_zip_and_sidecar(): void
    {
        $service = $this->makeService();

        $backup = $service->snapshotMedia('Test snapshot');

        $this->assertSame(Backup::STATUS_OK, $backup->status);
        $this->assertNotNull($backup->checksum_sha256);
        $this->assertSame(64, strlen((string) $backup->checksum_sha256));
        $this->assertGreaterThan(0, (int) $backup->size_bytes);
        $this->assertStringStartsWith('backups/media/', (string) $backup->path);
        $this->assertStringEndsWith('.zip', (string) $backup->path);

        Storage::disk('local-media-test')->assertExists($backup->path);
        Storage::disk('local-media-test')->assertExists($backup->path.'.sha256');
    }

    public function test_snapshot_media_includes_all_source_files_in_zip(): void
    {
        $service = $this->makeService();
        $backup  = $service->snapshotMedia();

        $zipAbs = Storage::disk('local-media-test')->path($backup->path);
        $zip    = new ZipArchive();
        $this->assertTrue($zip->open($zipAbs) === true);

        // Every source file should be in the ZIP + the embedded manifest.
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = (string) $zip->getNameIndex($i);
        }
        $zip->close();

        $this->assertContains(MediaSnapshotter::MANIFEST_FILE, $names);
        $this->assertGreaterThanOrEqual(4, count($names)); // 3 files + manifest
    }

    public function test_snapshot_media_embeds_manifest_json_inside_zip(): void
    {
        $service = $this->makeService();
        $backup  = $service->snapshotMedia();

        $zipAbs   = Storage::disk('local-media-test')->path($backup->path);
        $snapshotter = $this->app->make(MediaSnapshotter::class);
        $manifest = $snapshotter->readEmbeddedManifest($zipAbs);

        $this->assertNotNull($manifest);
        $this->assertSame(MediaSnapshotter::MANIFEST_VERSION, $manifest['version']);
        $this->assertNotEmpty($manifest['total_sha256']);
        $this->assertCount(3, $manifest['files']);
    }

    public function test_snapshot_media_records_failed_when_no_source_paths_configured(): void
    {
        config(['backup.media.source_paths' => []]);

        $backup = $this->makeService()->snapshotMedia();

        $this->assertSame(Backup::STATUS_FAILED, $backup->status);
        $this->assertStringContainsString('no media source_paths', (string) ($backup->meta['error'] ?? ''));
    }

    public function test_snapshot_media_records_failed_when_source_dir_is_empty(): void
    {
        // Point at a real-but-empty dir. The walker returns zero files → failed.
        File::deleteDirectory($this->tempMediaRoot);
        File::ensureDirectoryExists($this->tempMediaRoot);

        $backup = $this->makeService()->snapshotMedia();

        $this->assertSame(Backup::STATUS_FAILED, $backup->status);
        $this->assertStringContainsString('no readable files', (string) ($backup->meta['error'] ?? ''));
    }

    /* -------------------- dedup (skip path) -------------------- */

    public function test_snapshot_media_records_skipped_when_nothing_changed(): void
    {
        $service = $this->makeService();

        $first = $service->snapshotMedia('First');
        $this->assertSame(Backup::STATUS_OK, $first->status);

        $second = $service->snapshotMedia('Second — should skip');

        $this->assertSame(Backup::STATUS_SKIPPED, $second->status);
        $this->assertSame($first->id, (int) ($second->meta['previous_backup_id'] ?? 0));
        $this->assertSame($first->path, $second->path); // pointer to the still-current ZIP
    }

    public function test_snapshot_media_creates_new_zip_when_a_file_changes(): void
    {
        $service = $this->makeService();

        $first = $service->snapshotMedia('First');
        $this->assertSame(Backup::STATUS_OK, $first->status);

        // Mutate the fixture.
        File::put($this->tempMediaRoot.'/one.txt', 'alpha-changed');

        $second = $service->snapshotMedia('Second — should write');

        $this->assertSame(Backup::STATUS_OK, $second->status);
        $this->assertNotSame($first->id, $second->id);
        $this->assertNotSame($first->path, $second->path);
    }

    public function test_snapshot_media_creates_new_zip_when_a_file_is_added(): void
    {
        $service = $this->makeService();

        $first = $service->snapshotMedia('First');

        File::put($this->tempMediaRoot.'/nested/four.txt', 'delta');

        $second = $service->snapshotMedia('Second — added file');

        $this->assertSame(Backup::STATUS_OK, $second->status);
        $this->assertNotSame($first->path, $second->path);
    }

    /* -------------------- verify -------------------- */

    public function test_verify_returns_true_for_ok_media_backup(): void
    {
        $service = $this->makeService();
        $backup  = $service->snapshotMedia();

        $this->assertTrue($service->verify($backup));
    }

    public function test_verify_returns_false_when_media_zip_missing(): void
    {
        $service = $this->makeService();
        $backup  = $service->snapshotMedia();

        Storage::disk('local-media-test')->delete($backup->path);

        $this->assertFalse($service->verify($backup));
    }

    /* -------------------- prune (media type) -------------------- */

    public function test_prune_media_uses_media_weekly_retention(): void
    {
        $service = $this->makeService();

        // Seed 5 ok media backups (retention = 2). Prune should mark 3 pruned.
        for ($i = 1; $i <= 5; $i++) {
            $b = $service->record([
                'type'   => Backup::TYPE_MEDIA,
                'disk'   => 'local-media-test',
                'path'   => "backups/media/2026010{$i}-000000.zip",
                'status' => Backup::STATUS_OK,
            ]);
            $b->update(['created_at' => now()->subDays(5 - $i)]);
        }

        $pruned = $service->prune(Backup::TYPE_MEDIA);

        $this->assertSame(3, $pruned);
        $this->assertSame(2, Backup::where('type', Backup::TYPE_MEDIA)->where('status', Backup::STATUS_OK)->count());
        $this->assertSame(3, Backup::where('type', Backup::TYPE_MEDIA)->where('status', Backup::STATUS_PRUNED)->count());
    }

    public function test_prune_media_never_touches_db_backups(): void
    {
        $service = $this->makeService();

        for ($i = 1; $i <= 3; $i++) {
            $service->record([
                'type'   => Backup::TYPE_DB,
                'disk'   => 'local-media-test',
                'path'   => "backups/db/2026010{$i}-000000.sql.gz",
                'status' => Backup::STATUS_OK,
            ]);
        }

        $service->prune(Backup::TYPE_MEDIA);

        // All DB backups untouched.
        $this->assertSame(3, Backup::where('type', Backup::TYPE_DB)->where('status', Backup::STATUS_OK)->count());
    }
}
