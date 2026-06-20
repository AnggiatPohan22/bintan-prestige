<?php

namespace App\Services;

use App\Models\Theme;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

class ThemeDiscoveryService
{
    /**
     * Scan for theme.json manifests and upsert them into the themes table.
     *
     * Passing $root overrides the default base_path('themes') — used in tests.
     *
     * @return string[]  slugs of every theme that was successfully registered
     */
    public function scan(?string $root = null): array
    {
        $root = $root ?? base_path('themes');

        if (! is_dir($root)) {
            return [];
        }

        $discovered = [];

        foreach (glob($root . '/*/theme.json') ?: [] as $manifestPath) {
            $manifest = $this->readManifest($manifestPath);

            if ($manifest === null) {
                continue;
            }

            $directory = 'themes/' . basename(dirname($manifestPath));

            try {
                Theme::updateOrCreate(
                    ['slug' => $manifest['slug']],
                    [
                        'name'        => $manifest['name'],
                        'directory'   => $directory,
                        'version'     => $manifest['version'] ?? null,
                        'author'      => $manifest['author'] ?? null,
                        'description' => $manifest['description'] ?? null,
                        'screenshot'  => $manifest['screenshot'] ?? null,
                        'sort_order'  => $manifest['sort_order'] ?? 0,
                    ]
                );

                $discovered[] = $manifest['slug'];
            } catch (Throwable $e) {
                Log::warning('ThemeDiscoveryService: failed to register theme.', [
                    'manifest' => $manifestPath,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return $discovered;
    }

    /** Read and validate a theme.json file. Returns null on any failure. */
    private function readManifest(string $path): ?array
    {
        try {
            $contents = file_get_contents($path);

            if ($contents === false) {
                return null;
            }

            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($data) || empty($data['name']) || empty($data['slug'])) {
                return null;
            }

            return $data;
        } catch (JsonException) {
            Log::warning('ThemeDiscoveryService: invalid JSON in manifest.', ['path' => $path]);

            return null;
        }
    }
}
