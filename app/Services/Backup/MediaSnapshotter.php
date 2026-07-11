<?php

namespace App\Services\Backup;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use ZipArchive;

/**
 * Phase 8 B1 — pure media-snapshot helper.
 *
 * Walks the configured source paths, computes per-file SHA-256, and (when a
 * write is needed) produces a ZIP containing every file at its base_path()
 * relative path plus an embedded `manifest.json`. The manifest lets a later
 * snapshot decide whether *anything* changed without touching the old ZIP
 * beyond a manifest read.
 *
 * This class does no DB work and reads no config — that's BackupService's job.
 */
class MediaSnapshotter
{
    public const MANIFEST_VERSION = 1;
    public const MANIFEST_FILE    = 'manifest.json';

    /**
     * Walk source paths and produce a deterministic manifest.
     *
     * @param  list<string>  $sourcePaths       Filesystem paths relative to $rootBase.
     * @param  string        $rootBase          Absolute directory that $sourcePaths are relative to.
     * @param  list<string>  $excludePatterns   Skip entries whose relative path contains any of these substrings.
     * @param  bool          $followSymlinks    Follow symlinks (default false — safer default).
     * @return array{version:int, created_at:string, root_base:string, source_paths:list<string>, files:list<array{path:string,sha256:string,size:int}>, total_sha256:string}
     */
    public function computeManifest(
        array $sourcePaths,
        string $rootBase,
        array $excludePatterns = [],
        bool $followSymlinks = false,
    ): array {
        $files = [];

        foreach ($sourcePaths as $relSource) {
            $absSource = rtrim($rootBase, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($relSource, '/\\');
            if (! is_dir($absSource)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $absSource,
                    FilesystemIterator::SKIP_DOTS
                        | ($followSymlinks ? FilesystemIterator::FOLLOW_SYMLINKS : 0),
                ),
                RecursiveIteratorIterator::LEAVES_ONLY,
            );

            /** @var SplFileInfo $entry */
            foreach ($iterator as $entry) {
                if (! $entry->isFile()) {
                    continue;
                }

                // Broken symlinks or unreadable files: skip, do not crash the whole run.
                $realPath = $entry->getRealPath();
                if ($realPath === false || ! is_readable($realPath)) {
                    continue;
                }

                $rel = $this->relativePath($realPath, $rootBase);
                if ($rel === null) {
                    continue;
                }
                $rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);

                if ($this->isExcluded($rel, $excludePatterns)) {
                    continue;
                }

                $files[] = [
                    'path'   => $rel,
                    'sha256' => (string) hash_file('sha256', $realPath),
                    'size'   => (int) $entry->getSize(),
                ];
            }
        }

        // Determinism matters for dedup: sort by path.
        usort($files, fn (array $a, array $b): int => strcmp($a['path'], $b['path']));

        return [
            'version'      => self::MANIFEST_VERSION,
            'created_at'   => gmdate('c'),
            'root_base'    => $rootBase,
            'source_paths' => $sourcePaths,
            'files'        => $files,
            'total_sha256' => $this->totalSha256($files),
        ];
    }

    /**
     * Write a ZIP containing every file in the manifest at its manifest-path,
     * plus an embedded `manifest.json`. Overwrites $zipPath if it exists.
     *
     * @param array{version:int, created_at:string, root_base:string, source_paths:list<string>, files:list<array{path:string,sha256:string,size:int}>, total_sha256:string} $manifest
     */
    public function writeZip(array $manifest, string $zipPath): void
    {
        $rootBase = $manifest['root_base'];

        $zip = new ZipArchive();
        $open = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($open !== true) {
            throw new RuntimeException("Could not open ZIP for writing at {$zipPath} (ZipArchive::open returned {$open})");
        }

        foreach ($manifest['files'] as $file) {
            $absSource = rtrim($rootBase, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $file['path']);
            if (! is_file($absSource) || ! is_readable($absSource)) {
                continue; // best-effort; manifest is authoritative
            }

            $zip->addFile($absSource, $file['path']);
        }

        $manifestJson = json_encode(
            $manifest,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
        );
        if ($manifestJson === false) {
            $zip->close();
            @unlink($zipPath);

            throw new RuntimeException('Could not encode manifest.json for media backup');
        }

        $zip->addFromString(self::MANIFEST_FILE, $manifestJson);

        if (! $zip->close()) {
            throw new RuntimeException("Could not finalize ZIP at {$zipPath}");
        }
    }

    /**
     * Read the manifest.json embedded inside an existing snapshot ZIP.
     * Returns null if the ZIP is unreadable or has no manifest.
     *
     * @return array{version:int, created_at:string, root_base:string, source_paths:list<string>, files:list<array{path:string,sha256:string,size:int}>, total_sha256:string}|null
     */
    public function readEmbeddedManifest(string $zipPath): ?array
    {
        if (! is_file($zipPath) || ! is_readable($zipPath)) {
            return null;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::RDONLY) !== true) {
            return null;
        }

        $raw = $zip->getFromName(self::MANIFEST_FILE);
        $zip->close();

        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || ! isset($decoded['total_sha256'], $decoded['files'])) {
            return null;
        }

        /** @var array{version:int, created_at:string, root_base:string, source_paths:list<string>, files:list<array{path:string,sha256:string,size:int}>, total_sha256:string} $decoded */
        return $decoded;
    }

    /**
     * True when $rel matches any exclude substring.
     *
     * @param  list<string>  $excludePatterns
     */
    private function isExcluded(string $rel, array $excludePatterns): bool
    {
        foreach ($excludePatterns as $needle) {
            if ($needle !== '' && str_contains($rel, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function relativePath(string $absolute, string $rootBase): ?string
    {
        $rootBase = rtrim($rootBase, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (! str_starts_with($absolute, $rootBase)) {
            return null;
        }

        return substr($absolute, strlen($rootBase));
    }

    /**
     * "Hash of hashes" — SHA-256 over each `{path}:{sha256}\n` line in path
     * order. Comparing two manifests' `total_sha256` answers "did anything
     * change?" without walking both file lists.
     *
     * @param  list<array{path:string,sha256:string,size:int}>  $files
     */
    private function totalSha256(array $files): string
    {
        $ctx = hash_init('sha256');
        foreach ($files as $file) {
            hash_update($ctx, $file['path'].':'.$file['sha256']."\n");
        }

        return hash_final($ctx);
    }
}
