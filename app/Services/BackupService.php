<?php

namespace App\Services;

use App\Models\Backup;
use App\Services\Backup\DatabaseDumper;
use App\Services\Backup\MediaSnapshotter;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

/**
 * Phase 8 A2 + B1 — core backup engine.
 *
 * Public surface: `dumpDatabase()`, `snapshotMedia()`, `verify()`, `prune()`,
 * `record()`. Each backup produces both a physical artifact on disk (except
 * the `skipped` media case) and a `Backup` row so the admin surface (B3),
 * restore path (B4), and retention prune all query the same source of truth.
 *
 * Process execution is delegated: `DatabaseDumper` for mysqldump,
 * `MediaSnapshotter` for the media walker + ZIP writer. Both are DI-injected
 * so BackupService can be tested end-to-end without a real mysqldump binary
 * and without touching production storage paths.
 */
class BackupService
{
    public function __construct(
        private DatabaseDumper $dumper,
        private MediaSnapshotter $mediaSnapshotter,
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

        $stamp        = $this->timestampMs();
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

        $backup = $backup->fresh() ?? $backup;

        if ($backup->status === Backup::STATUS_OK) {
            $this->copyToOffsiteIfConfigured($backup);
        }

        return $backup->fresh() ?? $backup;
    }

    /**
     * Full media backup pipeline (B1): walk source paths → per-file SHA-256 →
     * dedup against prev backup's manifest → (optional) ZIP write → record →
     * verify → prune.
     *
     * When the current manifest's total_sha256 matches the newest ok backup,
     * we record a `skipped` row (audit trail: "backup ran, nothing changed")
     * without writing a new ZIP.
     */
    public function snapshotMedia(?string $purpose = null): Backup
    {
        $diskName    = (string) $this->config->get('backup.disks.media', 'local');
        $relativeDir = trim((string) $this->config->get('backup.paths.media', 'backups/media'), '/');
        $disk        = $this->filesystem->disk($diskName);
        $absoluteDir = $this->ensureDirectory($disk, $relativeDir);

        /** @var list<string> $sourcePaths */
        $sourcePaths     = (array) $this->config->get('backup.media.source_paths', []);
        $excludePatterns = (array) $this->config->get('backup.media.exclude_patterns', []);
        $followSymlinks  = (bool)  $this->config->get('backup.media.follow_symlinks', false);

        if ($sourcePaths === []) {
            return $this->record([
                'type'    => Backup::TYPE_MEDIA,
                'disk'    => $diskName,
                'path'    => '',
                'status'  => Backup::STATUS_FAILED,
                'purpose' => $purpose,
                'meta'    => ['error' => 'no media source_paths configured — set backup.media.source_paths'],
            ]);
        }

        $rootBase = base_path();
        $manifest = $this->mediaSnapshotter->computeManifest(
            $sourcePaths,
            $rootBase,
            $excludePatterns,
            $followSymlinks,
        );

        if ($manifest['files'] === []) {
            return $this->record([
                'type'    => Backup::TYPE_MEDIA,
                'disk'    => $diskName,
                'path'    => '',
                'status'  => Backup::STATUS_FAILED,
                'purpose' => $purpose,
                'meta'    => [
                    'error'        => 'no readable files found under configured source_paths',
                    'source_paths' => $sourcePaths,
                ],
            ]);
        }

        // Dedup: latest ok media backup wins as the comparison target.
        /** @var Backup|null $prev */
        $prev = Backup::query()
            ->where('type', Backup::TYPE_MEDIA)
            ->where('status', Backup::STATUS_OK)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($prev !== null) {
            $prevAbsolute = $this->absolutePath($this->filesystem->disk($prev->disk), $prev->path);
            $prevManifest = $prevAbsolute !== null
                ? $this->mediaSnapshotter->readEmbeddedManifest($prevAbsolute)
                : null;

            if ($prevManifest !== null && $prevManifest['total_sha256'] === $manifest['total_sha256']) {
                return $this->record([
                    'type'    => Backup::TYPE_MEDIA,
                    'disk'    => $diskName,
                    'path'    => $prev->path, // pointer to the still-current ZIP
                    'status'  => Backup::STATUS_SKIPPED,
                    'purpose' => $purpose,
                    'meta'    => [
                        'reason'             => 'no media changes since previous backup',
                        'previous_backup_id' => $prev->id,
                        'file_count'         => count($manifest['files']),
                        'total_sha256'       => $manifest['total_sha256'],
                    ],
                ]);
            }
        }

        $stamp       = $this->timestampMs();
        $baseName    = "{$stamp}.zip";
        $absoluteZip = $absoluteDir.DIRECTORY_SEPARATOR.$baseName;
        $relativeZip = "{$relativeDir}/{$baseName}";

        try {
            $this->mediaSnapshotter->writeZip($manifest, $absoluteZip);
        } catch (RuntimeException $e) {
            @unlink($absoluteZip);

            return $this->record([
                'type'    => Backup::TYPE_MEDIA,
                'disk'    => $diskName,
                'path'    => $relativeZip,
                'status'  => Backup::STATUS_FAILED,
                'purpose' => $purpose,
                'meta'    => ['error' => $e->getMessage()],
            ]);
        }

        $checksum = hash_file('sha256', $absoluteZip);
        $sizeBytes = (int) filesize($absoluteZip);

        @file_put_contents($absoluteZip.'.sha256', "{$checksum}  ".basename($absoluteZip)."\n");

        $backup = $this->record([
            'type'            => Backup::TYPE_MEDIA,
            'disk'            => $diskName,
            'path'            => $relativeZip,
            'size_bytes'      => $sizeBytes,
            'checksum_sha256' => $checksum,
            'status'          => Backup::STATUS_OK,
            'purpose'         => $purpose,
            'meta'            => [
                'file_count'   => count($manifest['files']),
                'total_sha256' => $manifest['total_sha256'],
                'source_paths' => $sourcePaths,
            ],
        ]);

        if ((bool) $this->config->get('backup.verify.enabled', true) && ! $this->verify($backup)) {
            $backup->update([
                'status' => Backup::STATUS_FAILED,
                'meta'   => array_merge((array) $backup->meta, ['error' => 'verify failed after write']),
            ]);
        }

        $this->prune(Backup::TYPE_MEDIA);

        $backup = $backup->fresh() ?? $backup;

        if ($backup->status === Backup::STATUS_OK) {
            $this->copyToOffsiteIfConfigured($backup);
        }

        return $backup->fresh() ?? $backup;
    }

    /**
     * Copy an ok backup to the configured offsite disk (B2). Handles retry
     * with a delay between attempts; on final failure, updates the row's
     * `meta.offsite_error` and (optionally) mails an alert. Returns the row.
     *
     * The physical bytes are streamed via readStream/writeStream so a
     * multi-gigabyte media ZIP doesn't need to be buffered in memory. After
     * upload, the SHA-256 is recomputed from the remote disk to catch
     * transport corruption.
     */
    public function copyToOffsite(Backup $backup): Backup
    {
        $offsiteDiskName = $this->offsiteDiskFor($backup->type);
        if ($offsiteDiskName === null) {
            return $backup;
        }

        if ($backup->status !== Backup::STATUS_OK) {
            return $backup;
        }

        $sourceDisk = $this->filesystem->disk($backup->disk);
        $sourcePath = $backup->path;

        if (! $sourceDisk->exists($sourcePath)) {
            return $this->markOffsiteFailed($backup, $offsiteDiskName, 'source file missing on local disk');
        }

        $attempts   = max(1, (int) $this->config->get('backup.offsite.retry_attempts', 3));
        $retryDelay = max(0, (int) $this->config->get('backup.offsite.retry_delay_seconds', 30));

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $offsiteDisk = $this->filesystem->disk($offsiteDiskName);

                // Streaming write of the backup artifact.
                $sourceStream = $sourceDisk->readStream($sourcePath);
                if ($sourceStream === null) {
                    throw new RuntimeException('readStream returned null for '.$sourcePath);
                }
                $offsiteDisk->writeStream($sourcePath, $sourceStream);
                if (is_resource($sourceStream)) {
                    @fclose($sourceStream);
                }

                // Sidecar checksum file (best-effort; local layouts include it,
                // remote-only restores can regenerate).
                $checksumPath = $sourcePath.'.sha256';
                if ($sourceDisk->exists($checksumPath)) {
                    $sidecarStream = $sourceDisk->readStream($checksumPath);
                    if (is_resource($sidecarStream)) {
                        $offsiteDisk->writeStream($checksumPath, $sidecarStream);
                        @fclose($sidecarStream);
                    }
                }

                // Post-upload verification: fetch a stream from offsite and
                // recompute SHA-256. Catches silent transport corruption.
                if ($backup->checksum_sha256 !== null) {
                    $remoteChecksum = $this->hashRemoteFile($offsiteDisk, $sourcePath);
                    if ($remoteChecksum !== $backup->checksum_sha256) {
                        throw new RuntimeException(
                            "offsite checksum mismatch (local={$backup->checksum_sha256}, remote={$remoteChecksum})"
                        );
                    }
                }

                $meta = (array) $backup->meta;
                $meta['offsite_disk']                 = $offsiteDiskName;
                $meta['offsite_path']                 = $sourcePath;
                $meta['offsite_verified_at']          = now()->toIso8601String();
                $meta['offsite_attempts']             = $attempt;
                unset($meta['offsite_error'], $meta['offsite_last_attempt_at']);

                $backup->update(['meta' => $meta]);

                return $backup->fresh() ?? $backup;
            } catch (Throwable $e) {
                if ($attempt < $attempts) {
                    if ($retryDelay > 0) {
                        sleep($retryDelay);
                    }
                    continue;
                }

                return $this->markOffsiteFailed(
                    $backup,
                    $offsiteDiskName,
                    $e->getMessage()
                );
            }
        }

        return $backup->fresh() ?? $backup;
    }

    /**
     * Convenience wrapper — used from `dumpDatabase()` and `snapshotMedia()`
     * so the write path is a no-op when offsite isn't configured, and to
     * respect the master `backup.offsite.copy_after_snapshot` toggle.
     */
    public function copyToOffsiteIfConfigured(Backup $backup): Backup
    {
        if (! (bool) $this->config->get('backup.offsite.copy_after_snapshot', true)) {
            return $backup;
        }

        if ($this->offsiteDiskFor($backup->type) === null) {
            return $backup;
        }

        return $this->copyToOffsite($backup);
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
        $keep = match ($type) {
            Backup::TYPE_MEDIA => (int) $this->config->get('backup.retention.media_weekly', 4),
            default            => (int) $this->config->get('backup.retention.db_daily', 14),
        };

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
     * Resolve the offsite disk name for a given backup type — or null when
     * offsite is not configured for that type.
     */
    private function offsiteDiskFor(string $type): ?string
    {
        $key  = "backup.disks.{$type}_offsite";
        $name = (string) $this->config->get($key, '');

        return $name !== '' ? $name : null;
    }

    /**
     * Compute SHA-256 of a file on a Laravel disk by streaming — safe for
     * multi-gigabyte backups because we never hold the whole file in memory.
     */
    private function hashRemoteFile(Filesystem $disk, string $path): string
    {
        $stream = $disk->readStream($path);
        if (! is_resource($stream)) {
            throw new RuntimeException("Could not open remote stream for {$path}");
        }

        $ctx = hash_init('sha256');
        while (! feof($stream)) {
            $chunk = fread($stream, 262144);
            if ($chunk === false) {
                break;
            }
            hash_update($ctx, $chunk);
        }
        @fclose($stream);

        return hash_final($ctx);
    }

    /**
     * Record an offsite failure into `meta`, emit an alert mail when
     * configured, and log it. Never throws — offsite failure must not
     * prevent the local backup from being usable.
     */
    private function markOffsiteFailed(Backup $backup, string $offsiteDiskName, string $error): Backup
    {
        $meta = (array) $backup->meta;
        $meta['offsite_disk']            = $offsiteDiskName;
        $meta['offsite_error']           = $error;
        $meta['offsite_last_attempt_at'] = now()->toIso8601String();
        $meta['offsite_attempts']        = (int) $this->config->get('backup.offsite.retry_attempts', 3);

        $backup->update(['meta' => $meta]);

        Log::warning('Backup offsite copy failed', [
            'backup_id' => $backup->id,
            'type'      => $backup->type,
            'disk'      => $offsiteDiskName,
            'error'     => $error,
        ]);

        $recipient = $this->offsiteAlertRecipient();
        if ($recipient !== null) {
            try {
                Mail::raw(
                    "Backup offsite copy FAILED\n\n"
                    ."Backup ID: {$backup->id}\n"
                    ."Type: {$backup->type}\n"
                    ."Offsite disk: {$offsiteDiskName}\n"
                    ."Error: {$error}\n\n"
                    ."Take a manual `php artisan backup:sync-offsite {$backup->id}` and investigate the disk.",
                    function ($message) use ($recipient, $backup) {
                        $message->to($recipient)
                            ->subject("[Bintan Prestige] Backup offsite copy failed (id={$backup->id})");
                    }
                );
            } catch (Throwable $mailError) {
                Log::warning('Backup offsite alert mail failed', [
                    'error' => $mailError->getMessage(),
                ]);
            }
        }

        return $backup->fresh() ?? $backup;
    }

    /**
     * Recipient for offsite failure alerts. Config value wins; falls back to
     * `mail.from.address`. Returns null when neither is set (silent failure
     * mode — the row still records the error via `meta`).
     */
    private function offsiteAlertRecipient(): ?string
    {
        $configured = (string) $this->config->get('backup.offsite.alert_email', '');
        if ($configured !== '') {
            return $configured;
        }

        $fallback = (string) $this->config->get('mail.from.address', '');

        return $fallback !== '' ? $fallback : null;
    }

    /**
     * Timestamp with millisecond precision — makes rapid-successive snapshots
     * (test suites, manual retries) produce unique filenames. Format
     * `YYYYMMDD-HHMMSS-mmm`.
     */
    private function timestampMs(): string
    {
        $now  = microtime(true);
        $sec  = (int) $now;
        $ms   = (int) round(($now - $sec) * 1000);
        $ms   = str_pad((string) $ms, 3, '0', STR_PAD_LEFT);

        return date('Ymd-His', $sec).'-'.$ms;
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
