<?php

namespace App\Services;

use App\Models\Backup;
use App\Services\Backup\DatabaseDumper;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use RuntimeException;

/**
 * Phase 8 A2 — core backup engine.
 *
 * The public surface is deliberately narrow: `dumpDatabase()`, `verify()`,
 * `prune()`, `record()`. Each backup produces both a physical artifact on
 * disk and a `Backup` row so the admin surface (B3), restore path (B4), and
 * retention prune can all query the same source of truth.
 *
 * Process execution is delegated to `DatabaseDumper` so BackupService can be
 * tested end-to-end without invoking real `mysqldump`.
 */
class BackupService
{
    public function __construct(
        private DatabaseDumper $dumper,
        private FilesystemManager $filesystem,
        private Repository $config,
    ) {
    }

    /**
     * Full DB backup pipeline: dump → gzip → checksum → record → verify → prune.
     * Returns the persisted `Backup` row (whether ok or failed).
     */
    public function dumpDatabase(?string $purpose = null): Backup
    {
        $sourceConnection = (string) ($this->config->get('backup.source_connection')
            ?: $this->config->get('database.default'));
        $driver = (string) $this->config->get("database.connections.{$sourceConnection}.driver");

        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            throw new RuntimeException(
                "BackupService::dumpDatabase only supports mysql / mariadb — got '{$driver}' for connection '{$sourceConnection}'"
            );
        }

        $diskName    = (string) $this->config->get('backup.disks.db', 'local');
        $relativeDir = trim((string) $this->config->get('backup.paths.db', 'backups/db'), '/');
        $disk        = $this->filesystem->disk($diskName);
        $absoluteDir = $this->ensureDirectory($disk, $relativeDir);

        $stamp        = date('Ymd-His');
        $baseName     = "{$stamp}.sql";
        $absoluteRaw  = $absoluteDir.DIRECTORY_SEPARATOR.$baseName;
        $absoluteGz   = $absoluteRaw.'.gz';
        $relativeGz   = "{$relativeDir}/{$baseName}.gz";

        $connection = (array) $this->config->get("database.connections.{$sourceConnection}", []);
        $timeout    = (int) $this->config->get('backup.mysqldump.timeout_seconds', 1800);

        $exit = $this->dumper->dump($connection, $absoluteRaw, $timeout);
        if ($exit !== 0) {
            @unlink($absoluteRaw);

            return $this->record([
                'type'    => Backup::TYPE_DB,
                'disk'    => $diskName,
                'path'    => "{$relativeDir}/{$baseName}",
                'status'  => Backup::STATUS_FAILED,
                'purpose' => $purpose,
                'meta'    => [
                    'exit_code' => $exit,
                    'error'     => $this->dumper->lastErrorOutput(),
                ],
            ]);
        }

        if (! is_file($absoluteRaw) || filesize($absoluteRaw) === 0) {
            @unlink($absoluteRaw);

            return $this->record([
                'type'    => Backup::TYPE_DB,
                'disk'    => $diskName,
                'path'    => "{$relativeDir}/{$baseName}",
                'status'  => Backup::STATUS_FAILED,
                'purpose' => $purpose,
                'meta'    => [
                    'error' => 'mysqldump exited 0 but produced no bytes',
                ],
            ]);
        }

        try {
            $this->gzipFile($absoluteRaw, $absoluteGz);
        } catch (RuntimeException $e) {
            @unlink($absoluteRaw);
            @unlink($absoluteGz);

            return $this->record([
                'type'    => Backup::TYPE_DB,
                'disk'    => $diskName,
                'path'    => $relativeGz,
                'status'  => Backup::STATUS_FAILED,
                'purpose' => $purpose,
                'meta'    => [
                    'error' => $e->getMessage(),
                ],
            ]);
        }

        @unlink($absoluteRaw);

        $checksum = hash_file('sha256', $absoluteGz);
        $sizeBytes = (int) filesize($absoluteGz);

        // Write companion .sha256 sidecar so the file is verifiable from the
        // shell without booting the app.
        @file_put_contents($absoluteGz.'.sha256', "{$checksum}  ".basename($absoluteGz)."\n");

        $backup = $this->record([
            'type'            => Backup::TYPE_DB,
            'disk'            => $diskName,
            'path'            => $relativeGz,
            'size_bytes'      => $sizeBytes,
            'checksum_sha256' => $checksum,
            'status'          => Backup::STATUS_OK,
            'purpose'         => $purpose,
            'meta'            => [
                'source_db'   => (string) ($connection['database'] ?? ''),
                'source_host' => (string) ($connection['host'] ?? ''),
            ],
        ]);

        if ((bool) $this->config->get('backup.verify.enabled', true) && ! $this->verify($backup)) {
            $backup->update([
                'status' => Backup::STATUS_FAILED,
                'meta'   => array_merge((array) $backup->meta, ['error' => 'verify failed after write']),
            ]);
        }

        $this->prune(Backup::TYPE_DB);

        return $backup->fresh() ?? $backup;
    }

    /**
     * Recompute checksum + confirm file exists + confirm size threshold.
     * Used both post-write and by C3 rehearsal / admin verify actions.
     */
    public function verify(Backup $backup): bool
    {
        $disk = $this->filesystem->disk($backup->disk);
        $path = $backup->path;

        if (! $disk->exists($path)) {
            return false;
        }

        $absolute = $this->absolutePath($disk, $path);
        if ($absolute === null || ! is_file($absolute)) {
            return false;
        }

        $minSize = (int) $this->config->get('backup.verify.min_size_bytes', 1024);
        if (filesize($absolute) < $minSize) {
            return false;
        }

        if ($backup->checksum_sha256 !== null) {
            $recomputed = hash_file('sha256', $absolute);
            if ($recomputed !== $backup->checksum_sha256) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark the oldest `status='ok'` backups of the given type as `pruned`
     * once we're above retention, unlinking their disk artifacts. Returns
     * the count of newly-pruned rows.
     */
    public function prune(string $type = Backup::TYPE_DB): int
    {
        $keep = (int) $this->config->get("backup.retention.{$type}_daily", 14);
        if ($keep < 1) {
            return 0;
        }

        $prunable = Backup::query()
            ->where('type', $type)
            ->where('status', Backup::STATUS_OK)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->skip($keep)
            ->take(1000)
            ->get();

        $prunedCount = 0;
        foreach ($prunable as $backup) {
            $this->unlinkArtifact($backup);
            $backup->update(['status' => Backup::STATUS_PRUNED]);
            $prunedCount++;
        }

        return $prunedCount;
    }

    /**
     * Low-level `Backup` row creation. Intentionally public so tests and the
     * admin surface (B3, later) can record backups without touching the file.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function record(array $attributes): Backup
    {
        return Backup::create($attributes);
    }

    /* ---------- helpers ---------- */

    private function ensureDirectory(Filesystem $disk, string $relativeDir): string
    {
        $absolute = $this->absolutePath($disk, $relativeDir);
        if ($absolute === null) {
            throw new RuntimeException(
                "Backup disk '{$disk->path('')}' does not expose an absolute path — B2's cloud-only path lands with a streaming rewrite."
            );
        }

        if (! is_dir($absolute) && ! mkdir($absolute, 0755, true) && ! is_dir($absolute)) {
            throw new RuntimeException("Failed to create backup directory: {$absolute}");
        }

        return $absolute;
    }

    private function gzipFile(string $sourcePath, string $destPath): void
    {
        $inHandle  = @fopen($sourcePath, 'rb');
        $outHandle = @gzopen($destPath, 'wb9');

        if ($inHandle === false || $outHandle === false) {
            if ($inHandle !== false) {
                fclose($inHandle);
            }
            if ($outHandle !== false) {
                gzclose($outHandle);
            }
            throw new RuntimeException("Could not open gzip streams for {$sourcePath} → {$destPath}");
        }

        while (! feof($inHandle)) {
            $chunk = fread($inHandle, 262144); // 256 KB
            if ($chunk === false) {
                break;
            }
            gzwrite($outHandle, $chunk);
        }

        fclose($inHandle);
        gzclose($outHandle);
    }

    private function unlinkArtifact(Backup $backup): void
    {
        $disk = $this->filesystem->disk($backup->disk);

        if ($disk->exists($backup->path)) {
            $disk->delete($backup->path);
        }

        $checksumPath = $backup->path.'.sha256';
        if ($disk->exists($checksumPath)) {
            $disk->delete($checksumPath);
        }
    }

    /**
     * Absolute path helper — Storage disks that don't extend `LocalAdapter`
     * (e.g. S3) will throw or return an unusable value here, in which case
     * verify/prune fall back to disk-native operations only.
     */
    private function absolutePath(Filesystem $disk, string $relativePath): ?string
    {
        try {
            return $disk->path($relativePath);
        } catch (\Throwable) {
            return null;
        }
    }
}
