<?php

namespace App\Services\Plugin;

use App\Contracts\PluginLifecycle;
use App\Exceptions\PluginSecurityException;
use App\Facades\CmsHooks;
use App\Models\AuditLog;
use App\Models\Plugin;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PluginManager
{
    public function __construct(
        private readonly PluginRegistry $registry,
        private readonly Application $app,
        private readonly PluginScanner $scanner,
    ) {}

    /**
     * Register all active plugin ServiceProviders in dependency order.
     *
     * Called from AppServiceProvider::register() so plugin providers
     * participate in the full Laravel boot cycle.
     *
     * A broken plugin must never crash the CMS — all exceptions are caught.
     */
    public function boot(): void
    {
        try {
            $active = $this->registry->active();

            if ($active->isEmpty()) {
                return;
            }

            $manifestMap = $this->buildManifestMap();

            $toLoad = $active
                ->map(fn ($plugin) => $manifestMap[$plugin->slug] ?? null)
                ->filter()
                ->values()
                ->all();

            $sorted = $this->topologicalSort($toLoad);

            foreach ($sorted as $manifest) {
                $this->registerProvider($manifest);
            }
        } catch (Throwable $e) {
            Log::error('PluginManager: boot failed.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Activate a plugin: security scan, call lifecycle hook, mark active in DB, clear cache.
     *
     * @throws RuntimeException         if the plugin is already active.
     * @throws PluginSecurityException  if the plugin contains blocked functions or unknown permission scopes.
     */
    public function activate(Plugin $plugin): void
    {
        if ($plugin->is_active) {
            throw new RuntimeException("Plugin '{$plugin->slug}' is already active.");
        }

        // Security scan must pass before any lifecycle method is called.
        $this->scanner->scan($plugin->slug);
        $manifest = $this->buildManifestMap()[$plugin->slug] ?? [];
        $this->scanner->validatePermissions($manifest);

        $this->callLifecycle($plugin, 'onActivate');

        $plugin->update([
            'is_active'    => true,
            'activated_at' => now(),
        ]);

        $this->registry->forget();

        AuditLog::record('plugin.activated', $plugin);

        CmsHooks::doAction('plugin.activated', $plugin);
    }

    /**
     * Deactivate a plugin: call lifecycle hook, mark inactive in DB, clear cache.
     *
     * @throws RuntimeException if the plugin is not currently active.
     */
    public function deactivate(Plugin $plugin): void
    {
        if (! $plugin->is_active) {
            throw new RuntimeException("Plugin '{$plugin->slug}' is not active.");
        }

        $this->callLifecycle($plugin, 'onDeactivate');

        $plugin->update(['is_active' => false]);

        $this->registry->forget();

        AuditLog::record('plugin.deactivated', $plugin);

        CmsHooks::doAction('plugin.deactivated', $plugin);
    }

    /**
     * Uninstall a plugin: call lifecycle hook, remove from DB, clear cache.
     *
     * @throws RuntimeException if the plugin is still active.
     */
    public function uninstall(Plugin $plugin): void
    {
        if ($plugin->is_active) {
            throw new RuntimeException(
                "Deactivate plugin '{$plugin->slug}' before uninstalling."
            );
        }

        $this->callLifecycle($plugin, 'onUninstall');

        $plugin->delete();

        $this->registry->forget();

        AuditLog::record('plugin.uninstalled', $plugin);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Build a map of slug → manifest array from all plugin.json files on disk.
     */
    private function buildManifestMap(?string $root = null): array
    {
        $root = $root ?? app_path('Plugins');
        $map  = [];

        foreach (glob($root . '/*/plugin.json') ?: [] as $path) {
            try {
                $content = file_get_contents($path);

                if ($content === false) {
                    continue;
                }

                $manifest = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

                if (is_array($manifest) && ! empty($manifest['slug'])) {
                    $map[$manifest['slug']] = $manifest;
                }
            } catch (Throwable) {
                // Skip unreadable or invalid manifests during boot.
            }
        }

        return $map;
    }

    /**
     * Topological sort of manifests by their `requires` dependency list.
     * Dependencies are always placed before the plugins that need them.
     *
     * Cycles are broken by the visited guard — the first encountered path wins.
     */
    private function topologicalSort(array $manifests): array
    {
        $bySlug  = [];
        foreach ($manifests as $manifest) {
            $bySlug[$manifest['slug']] = $manifest;
        }

        $sorted  = [];
        $visited = [];

        $visit = function (array $manifest) use (&$visit, &$sorted, &$visited, $bySlug): void {
            $slug = $manifest['slug'];

            if (isset($visited[$slug])) {
                return;
            }

            $visited[$slug] = true;

            foreach ($manifest['requires'] ?? [] as $dep) {
                if (isset($bySlug[$dep])) {
                    $visit($bySlug[$dep]);
                }
            }

            $sorted[] = $manifest;
        };

        foreach ($manifests as $manifest) {
            $visit($manifest);
        }

        return $sorted;
    }

    /**
     * Register a plugin's ServiceProvider with the application container.
     * Skips silently if the class does not exist (STEPs 5–7 not yet implemented).
     */
    private function registerProvider(array $manifest): void
    {
        $class = $manifest['service_provider'] ?? null;

        if ($class === null || ! class_exists($class)) {
            return;
        }

        try {
            $this->app->register($class);
        } catch (Throwable $e) {
            Log::warning("PluginManager: failed to register provider for '{$manifest['slug']}'.", [
                'provider' => $class,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve the plugin's ServiceProvider and call a lifecycle method on it.
     * Skips silently if the class does not exist.
     * Exceptions are logged — a broken plugin must not crash the CMS.
     */
    private function callLifecycle(Plugin $plugin, string $method): void
    {
        try {
            $manifest = $this->buildManifestMap()[$plugin->slug] ?? null;

            if ($manifest === null) {
                return;
            }

            $class = $manifest['service_provider'] ?? null;

            if ($class === null || ! class_exists($class)) {
                return;
            }

            $provider = $this->app->make($class);

            if ($provider instanceof PluginLifecycle) {
                $provider->{$method}();
            }
        } catch (Throwable $e) {
            Log::warning("PluginManager: lifecycle {$method} failed for '{$plugin->slug}'.", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
