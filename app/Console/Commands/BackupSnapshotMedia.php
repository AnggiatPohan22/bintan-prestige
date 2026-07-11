<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Phase 8 B1 — scheduled/manual media snapshot via `BackupService`.
 *
 * Weekly-by-default (Sun 04:00 WIB). Produces a ZIP of everything under the
 * configured `backup.media.source_paths`, dedupped against the previous ok
 * media backup — when nothing has changed, no new ZIP is written and the run
 * is recorded as `skipped`.
 */
class BackupSnapshotMedia extends Command
{
    /** @var string */
    protected $signature = 'backup:snapshot-media
                            {--purpose= : Optional human-readable label stored on the Backup row}';

    /** @var string */
    protected $description = 'Take a ZIP snapshot of the configured media source paths into storage/app/backups/media/ and record it in the backups table.';

    public function handle(BackupService $service): int
    {
        $purpose = (string) ($this->option('purpose') ?? '') ?: 'Scheduled or ad-hoc media snapshot';

        $this->info('→ Snapshotting media via BackupService...');

        try {
            $backup = $service->snapshotMedia($purpose);
        } catch (RuntimeException $e) {
            $this->error('BackupService threw: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($backup->status === Backup::STATUS_FAILED) {
            $this->error('Media snapshot FAILED (id='.$backup->id.'): '.($backup->meta['error'] ?? 'unknown error'));

            return self::FAILURE;
        }

        if ($backup->status === Backup::STATUS_SKIPPED) {
            $this->info(sprintf(
                '↷ Media snapshot SKIPPED — no changes since backup #%d (id=%d, %d files).',
                (int) ($backup->meta['previous_backup_id'] ?? 0),
                $backup->id,
                (int) ($backup->meta['file_count'] ?? 0),
            ));

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '✓ Media snapshot OK — id=%d, path=%s, size=%d bytes, %d files, sha256=%s...',
            $backup->id,
            $backup->path,
            (int) $backup->size_bytes,
            (int) ($backup->meta['file_count'] ?? 0),
            substr((string) $backup->checksum_sha256, 0, 12),
        ));

        return self::SUCCESS;
    }
}
