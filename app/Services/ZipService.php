<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class ZipService
{
    public const MAX_SIZE_BYTES = 52_428_800; // 50 MB

    /** Extensions that must never appear inside an imported ZIP. */
    private const BLOCKED_EXTENSIONS = ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'htaccess'];

    /** Required fields in a theme.json manifest. */
    private const THEME_REQUIRED = ['name', 'slug', 'version'];

    // -------------------------------------------------------------------------
    // Export
    // -------------------------------------------------------------------------

    /**
     * Create a ZIP archive from a directory tree.
     *
     * @param  string               $sourceDir   Absolute path to the directory to zip.
     * @param  array<string,string> $extraFiles  Map of ZIP-internal path → file content string.
     * @return string               Absolute path to the temp ZIP file (caller must delete it).
     *
     * @throws RuntimeException if the source directory does not exist or ZipArchive fails.
     */
    public function createFromDirectory(string $sourceDir, array $extraFiles = []): string
    {
        if (! is_dir($sourceDir)) {
            throw new RuntimeException("Source directory does not exist: {$sourceDir}");
        }

        $tempPath = sys_get_temp_dir() . '/bp-theme-export-' . uniqid() . '.zip';

        $zip = new ZipArchive();

        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create ZIP archive at: {$tempPath}");
        }

        $this->addDirectoryToZip($zip, $sourceDir, '');

        foreach ($extraFiles as $zipPath => $content) {
            $zip->addFromString($zipPath, $content);
        }

        $zip->close();

        return $tempPath;
    }

    // -------------------------------------------------------------------------
    // Import
    // -------------------------------------------------------------------------

    /**
     * Validate a ZIP file and extract it as a theme into $targetBase/{slug}/.
     *
     * Security checks (in order):
     *   1. File size ≤ 50 MB
     *   2. Openable by ZipArchive
     *   3. No blocked extensions inside
     *   4. No path traversal sequences
     *   5. Contains a valid theme.json (name, slug, version)
     *
     * @param  string $zipPath    Absolute path to the uploaded ZIP.
     * @param  string $targetBase Absolute path where themes live (e.g. base_path('themes')).
     * @return array  Parsed theme.json manifest data.
     *
     * @throws RuntimeException on any security or validation failure.
     */
    public function extractTheme(string $zipPath, string $targetBase): array
    {
        // 1 — Size guard.
        if (filesize($zipPath) > self::MAX_SIZE_BYTES) {
            throw new RuntimeException('Theme ZIP exceeds the 50 MB maximum allowed size.');
        }

        // 2 — Open archive.
        $zip = new ZipArchive();
        $status = $zip->open($zipPath);

        if ($status !== true) {
            throw new RuntimeException("Could not open ZIP archive (ZipArchive error code: {$status}).");
        }

        try {
            // 3 & 4 — Security scan.
            $this->assertNoBlockedContent($zip);

            // 5 — Locate and validate theme.json.
            $manifestInfo = $this->findThemeManifest($zip);

            if ($manifestInfo === null) {
                throw new RuntimeException('The ZIP does not contain a theme.json manifest at the root or one level deep.');
            }

            ['entry' => $manifestEntry, 'prefix' => $prefix] = $manifestInfo;

            $manifest = $this->parseManifest($zip->getFromName($manifestEntry), $manifestEntry);

            // Extract all files to targetBase/{slug}/.
            $slug      = $manifest['slug'];
            $targetDir = rtrim($targetBase, '/\\') . DIRECTORY_SEPARATOR . $slug;

            $this->extractEntries($zip, $prefix, $targetDir);
        } finally {
            $zip->close();
        }

        return $manifest;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function addDirectoryToZip(ZipArchive $zip, string $absoluteDir, string $zipPrefix): void
    {
        // Normalise to forward slashes so the prefix-stripping works on all OS.
        $absoluteDir = rtrim(str_replace('\\', '/', $absoluteDir), '/');

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absoluteDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $realPath = str_replace('\\', '/', (string) $file->getRealPath());
            $relative = ltrim(str_replace($absoluteDir . '/', '', $realPath), '/');
            $zipEntry = $zipPrefix !== '' ? $zipPrefix . '/' . $relative : $relative;

            $zip->addFile((string) $file->getRealPath(), $zipEntry);
        }
    }

    /**
     * Scan all ZIP entries for blocked extensions and path traversal.
     *
     * @throws RuntimeException if a violation is found.
     */
    private function assertNoBlockedContent(ZipArchive $zip): void
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if ($name === false) {
                continue;
            }

            // Block path traversal.
            if (str_contains($name, '../') || str_contains($name, '..\\')) {
                throw new RuntimeException("Theme ZIP contains a path traversal sequence: {$name}");
            }

            // Block dangerous file types.
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, self::BLOCKED_EXTENSIONS, true)) {
                throw new RuntimeException("Theme ZIP contains a blocked file type (.{$ext}): {$name}");
            }
        }
    }

    /**
     * Find theme.json in the ZIP: at root level or exactly one level deep.
     *
     * @return array{entry: string, prefix: string}|null
     */
    private function findThemeManifest(ZipArchive $zip): ?array
    {
        // Root-level: theme.json
        if ($zip->locateName('theme.json') !== false) {
            return ['entry' => 'theme.json', 'prefix' => ''];
        }

        // One-level deep: {folder}/theme.json
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if ($name !== false && preg_match('/^[^\/]+\/theme\.json$/', $name)) {
                return ['entry' => $name, 'prefix' => dirname($name) . '/'];
            }
        }

        return null;
    }

    /**
     * Parse and validate the manifest JSON string.
     *
     * @throws RuntimeException on invalid JSON or missing required fields.
     */
    private function parseManifest(string|false $content, string $entry): array
    {
        if ($content === false) {
            throw new RuntimeException("Could not read manifest from ZIP entry: {$entry}");
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException("theme.json contains invalid JSON: {$e->getMessage()}");
        }

        if (! is_array($data)) {
            throw new RuntimeException('theme.json did not decode to an array.');
        }

        foreach (self::THEME_REQUIRED as $field) {
            if (empty($data[$field]) || ! is_string($data[$field])) {
                throw new RuntimeException("theme.json is missing required field: \"{$field}\".");
            }
        }

        return $data;
    }

    /**
     * Extract all ZIP entries to $targetDir, stripping $prefix from entry names.
     */
    private function extractEntries(ZipArchive $zip, string $prefix, string $targetDir): void
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);

            if ($entryName === false) {
                continue;
            }

            // Strip the in-ZIP folder prefix.
            $relative = $prefix !== '' && str_starts_with($entryName, $prefix)
                ? substr($entryName, strlen($prefix))
                : $entryName;

            // Skip directory entries and empty paths.
            if ($relative === '' || str_ends_with($relative, '/')) {
                continue;
            }

            $targetPath = $targetDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $parentDir  = dirname($targetPath);

            if (! is_dir($parentDir)) {
                mkdir($parentDir, 0755, true);
            }

            $content = $zip->getFromIndex($i);

            if ($content !== false) {
                file_put_contents($targetPath, $content);
            }
        }
    }
}
