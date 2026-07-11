<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Console\Command;

/**
 * Phase 8 B2 — manual retry command for offsite copies.
 *
 * Usage:
 *   php artisan backup:sync-offsite               # every ok backup w/ no offsite yet
 *   php artisan backup:sync-offsite 42            # a specific backup id
 *   php artisan backup:sync-offsite --type=db     # only db backups
 *   php artisan backup:sync-offsite --force       # even ones with meta.offsite_verified_at
 *
 * The scheduled snapshots already invoke offsite copy right after each ok
 * write (via BackupService::copyToOffsiteIfConfigured), so this command is
 * only needed when: (a) an earlier snapshot failed to copy, or (b) you
 * added an offsite disk after backups already existed.
 */
class BackupSyncOffsite extends Command
{
    /** @var string */
    protected $signature = 'backup:sync-offsite
                            {backup_id? : Sync only the backup with this id}
                            {--type=  : Restrict to db or media}
                            {--force  : Sync even when meta.offsite_verified_at is already present}';

    /** @var string */
    protected $description = 'Copy ok backups to the configured offsite disk. Idempotent — safe to run repeatedly.';

    public function handle(BackupService $service): int
    {
        $query = Backup::query()->where('status', Backup::STATUS_OK);

        $backupId = $this->argument('backup_id');
        if ($backupId !== null) {
            $query->where('id', (int) $backupId);
        }

        $type = (string) ($this->option('type') ?? '');
        if ($type !== '') {
            if (! in_array($type, [Backup::TYPE_DB, Backup::TYPE_MEDIA], true)) {
                $this->error("Invalid --type: {$type}. Use 'db' or 'media'.");

                return self::INVALID;
            }
            $query->where('type', $type);
        }

        $backups = $query->orderBy('id')->get();
        if ($backups->isEmpty()) {
            $this->info('No matching ok backups to sync.');

            return self::SUCCESS;
        }

        $force  = (bool) $this->option('force');
        $ok     = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($backups as $backup) {
            $alreadyVerified = isset(((array) $backup->meta)['offsite_verified_at']);
            if ($alreadyVerified && ! $force) {
                $skipped++;
                continue;
            }

            $this->line("→ syncing backup #{$backup->id} ({$backup->type}) …");
            $updated = $service->copyToOffsite($backup);

            $meta = (array) $updated->meta;
            if (isset($meta['offsite_verified_at'])) {
                $this->info("  ✓ offsite ok");
                $ok++;
            } else {
                $this->error("  ✗ offsite failed: ".($meta['offsite_error'] ?? 'unknown'));
                $failed++;
            }
        }

        $this->line('');
        $this->info("Done. ok={$ok}, failed={$failed}, skipped={$skipped}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
