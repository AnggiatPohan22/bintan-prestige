<?php

namespace App\Services\Plugin;

use App\Models\Plugin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

class PluginRegistry
{
    public const ACTIVE_CACHE_KEY = 'active_plugins';

    public const CACHE_TTL = 3600; // 60 minutes

    /** Required keys every plugin.json must have. */
    private const REQUIRED_FIELDS = ['name', 'slug', 'version', 'service_provider'];

    /**
     * Scan the Plugins directory and sync manifests to the DB.
     *
     * Passing $root overrides the default app_path('Plugins') — used in tests.
     *
     * @return array{installed: string[], updated: string[], orphaned: string[], unchanged: string[]}
     */
    public function sync(?string $root = null): array
    {
        $root = $root ?? app_path('Plugins');

        $result = [
            'installed' => [],
            'updated'   => [],
            'orphaned'  => [],
            'unchanged' => [],
        ];

        if (! is_dir($root)) {
            return $result;
        }

        // Index all DB slugs so we can detect orphans and updates efficiently.
        $inDb = Plugin::all()->keyBy('slug');

        $foundSlugs = [];

        foreach (glob($root . '/*/plugin.json') ?: [] as $manifestPath) {
            $manifest = $this->readManifest($manifestPath);

            if ($manifest === null) {
                continue;
            }

            $slug = $manifest['slug'];
            $foundSlugs[] = $slug;

            try {
                if (! $inDb->has($slug)) {
                    Plugin::create([
                        'name'         => $manifest['name'],
                        'slug'         => $slug,
                        'version'      => $manifest['version'],
                        'author'       => $manifest['author'] ?? null,
                        'description'  => $manifest['description'] ?? null,
                        'is_active'    => false,
                        'installed_at' => now(),
                    ]);

                    $result['installed'][] = $slug;
                } elseif ($inDb->get($slug)->version !== $manifest['version']) {
                    Plugin::where('slug', $slug)->update([
                        'name'        => $manifest['name'],
                        'version'     => $manifest['version'],
                        'author'      => $manifest['author'] ?? null,
                        'description' => $manifest['description'] ?? null,
                    ]);

                    $result['updated'][] = $slug;
                } else {
                    $result['unchanged'][] = $slug;
                }
            } catch (Throwable $e) {
                Log::warning('PluginRegistry: failed to sync plugin.', [
                    'manifest' => $manifestPath,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        // Slugs in DB that have no manifest on disk.
        $result['orphaned'] = $inDb->keys()
            ->diff($foundSlugs)
            ->values()
            ->all();

        $this->forget();

        return $result;
    }

    /** All plugins from DB, ordered by name. */
    public function all(): Collection
    {
        return Plugin::orderBy('name')->get();
    }

    /** Active plugins from DB, cached for CACHE_TTL seconds. */
    public function active(): Collection
    {
        return Cache::remember(
            self::ACTIVE_CACHE_KEY,
            self::CACHE_TTL,
            fn () => Plugin::active()->orderBy('name')->get()
        );
    }

    /** Find a plugin by slug. Returns null if not found. */
    public function find(string $slug): ?Plugin
    {
        return Plugin::where('slug', $slug)->first();
    }

    /** Clear the active plugins cache (call after activate/deactivate/sync). */
    public function forget(): void
    {
        Cache::forget(self::ACTIVE_CACHE_KEY);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Read and validate a plugin.json manifest.
     * Returns null on invalid JSON, missing file, or missing required fields.
     */
    private function readManifest(string $path): ?array
    {
        try {
            $contents = file_get_contents($path);

            if ($contents === false) {
                return null;
            }

            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($data)) {
                return null;
            }

            if (! $this->validateManifest($data)) {
                Log::info('PluginRegistry: skipping manifest with missing required fields.', [
                    'path'   => $path,
                    'fields' => array_keys($data),
                ]);

                return null;
            }

            return $data;
        } catch (JsonException) {
            Log::warning('PluginRegistry: invalid JSON in manifest.', ['path' => $path]);

            return null;
        }
    }

    /**
     * All required fields must be present and non-empty strings.
     */
    private function validateManifest(array $data): bool
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($data[$field]) || ! is_string($data[$field])) {
                return false;
            }
        }

        return true;
    }
}
