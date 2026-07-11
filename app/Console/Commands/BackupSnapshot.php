<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Phase 8 A2 — scheduled/manual DB snapshot via `BackupService`.
 *
 * This is the DIFFERENT command from `db:backup`:
 *   - `db:backup <task-id>`        — manual pre-ALTER dumper (AGENTS.md §13),
 *                                     writes to storage/app/db-backups/pre-*.sql,
 *                                     no Backup row, uncompressed.
 *   - `backup:snapshot`            — scheduled or manual snapshot writing to
 *                                     storage/app/backups/db/*.sql.gz, records
 *                                     a `Backup` row, checksummed, retention-aware.
 *
 * Both live side-by-side on purpose — the pre-ALTER habit stays untouched.
 */
class BackupSnapshot extends Command
{
    /** @var string */
    protected $signature = 'backup:snapshot
                            {--purpose= : Optional human-readable label stored on the Backup row}';

    /** @var string */
    protected $description = 'Take a gzipped, checksummed DB snapshot into storage/app/backups/db/ and record it in the backups table.';

    public function handle(BackupService $service): int
    {
        $sourceConnection = (string) (config('backup.source_connection') ?: config('database.default'));
        $driver = (string) config("database.connections.{$sourceConnection}.driver");

        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            $this->error("backup:snapshot only supports the mysql connection — got driver '{$driver}' for connection '{$sourceConnection}'.");

            return self::FAILURE;
        }

        $purpose = (string) ($this->option('purpose') ?? '') ?: 'Scheduled or ad-hoc snapshot';

        $this->info('→ Snapshotting DB via BackupService...');

        try {
            $backup = $service->dumpDatabase($purpose);
        } catch (RuntimeException $e) {
            $this->error('BackupService threw: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($backup->status !== Backup::STATUS_OK) {
            $this->error('Snapshot FAILED (id='.$backup->id.'): '.($backup->meta['error'] ?? 'unknown error'));

            return self::FAILURE;
        }

        $this->info(sprintf(
            '✓ Snapshot OK — id=%d, path=%s, size=%d bytes, sha256=%s...',
            $backup->id,
            $backup->path,
            (int) $backup->size_bytes,
            substr((string) $backup->checksum_sha256, 0, 12),
        ));

        return self::SUCCESS;
    }
}
