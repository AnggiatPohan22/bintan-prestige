<?php

namespace Tests\Feature\Phase4;

use App\Models\Plugin;
use App\Services\Plugin\PluginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * STEP 1 — Plugin Registry & Discovery
 *
 * Tests cover:
 *   D1 — sync() discovers new plugins and inserts them into DB
 *   D2 — sync() updates version when manifest version changes
 *   D3 — sync() reports unchanged for plugins already at current version
 *   D4 — sync() reports orphaned slugs for DB entries with no manifest on disk
 *   D5 — sync() skips manifests with invalid JSON
 *   D6 — sync() skips manifests missing required fields
 *   D7 — sync() returns empty result for nonexistent directory
 *   D8 — sync() discovers all three real app/Plugins manifests
 *   D9 — sync() does not activate discovered plugins
 *   D10 — all() returns every plugin ordered by name
 *   D11 — active() returns only active plugins and caches the result
 *   D12 — find() locates a plugin by slug; returns null for unknown slug
 *   D13 — forget() invalidates the active plugins cache
 */
class PluginRegistryTest extends TestCase
{
    use RefreshDatabase;

    private PluginRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->registry = new PluginRegistry();
    }

    // -------------------------------------------------------------------------
    // D1 — sync() discovers new plugins
    // -------------------------------------------------------------------------

    public function test_sync_installs_a_new_plugin_from_a_manifest_on_disk(): void
    {
        $dir = $this->makeTempPlugin('my-plugin', [
            'name'             => 'My Plugin',
            'slug'             => 'my-plugin',
            'version'          => '1.0.0',
            'service_provider' => 'App\\Plugins\\MyPlugin\\MyPluginServiceProvider',
        ]);

        $result = $this->registry->sync($dir);

        $this->assertContains('my-plugin', $result['installed']);
        $this->assertEmpty($result['updated']);
        $this->assertEmpty($result['orphaned']);

        $this->assertDatabaseHas('plugins', [
            'slug'      => 'my-plugin',
            'name'      => 'My Plugin',
            'version'   => '1.0.0',
            'is_active' => false,
        ]);

        $this->cleanTempDir($dir);
    }

    public function test_sync_installs_multiple_plugins_from_the_same_directory(): void
    {
        $dir = sys_get_temp_dir() . '/bp-plugins-multi-' . uniqid();
        mkdir($dir);

        foreach (['alpha', 'beta', 'gamma'] as $slug) {
            $this->writeManifest($dir, $slug, [
                'name'             => ucfirst($slug) . ' Plugin',
                'slug'             => $slug,
                'version'          => '1.0.0',
                'service_provider' => "App\\Plugins\\" . ucfirst($slug) . "\\Provider",
            ]);
        }

        $result = $this->registry->sync($dir);

        $this->assertCount(3, $result['installed']);
        $this->assertDatabaseCount('plugins', 3);

        $this->cleanTempDir($dir);
    }

    // -------------------------------------------------------------------------
    // D2 — sync() updates version when manifest version changes
    // -------------------------------------------------------------------------

    public function test_sync_updates_plugin_when_manifest_version_is_bumped(): void
    {
        Plugin::create([
            'name'      => 'Versioned Plugin',
            'slug'      => 'versioned-plugin',
            'version'   => '1.0.0',
            'is_active' => false,
        ]);

        $dir = $this->makeTempPlugin('versioned-plugin', [
            'name'             => 'Versioned Plugin',
            'slug'             => 'versioned-plugin',
            'version'          => '1.1.0',
            'service_provider' => 'App\\Plugins\\VersionedPlugin\\Provider',
        ]);

        $result = $this->registry->sync($dir);

        $this->assertContains('versioned-plugin', $result['updated']);
        $this->assertEmpty($result['installed']);

        $this->assertDatabaseHas('plugins', [
            'slug'    => 'versioned-plugin',
            'version' => '1.1.0',
        ]);

        $this->cleanTempDir($dir);
    }

    // -------------------------------------------------------------------------
    // D3 — sync() reports unchanged for same-version plugins
    // -------------------------------------------------------------------------

    public function test_sync_reports_unchanged_for_plugin_already_at_current_version(): void
    {
        Plugin::create([
            'name'      => 'Stable Plugin',
            'slug'      => 'stable-plugin',
            'version'   => '2.0.0',
            'is_active' => true,
        ]);

        $dir = $this->makeTempPlugin('stable-plugin', [
            'name'             => 'Stable Plugin',
            'slug'             => 'stable-plugin',
            'version'          => '2.0.0',
            'service_provider' => 'App\\Plugins\\StablePlugin\\Provider',
        ]);

        $result = $this->registry->sync($dir);

        $this->assertContains('stable-plugin', $result['unchanged']);
        $this->assertEmpty($result['installed']);
        $this->assertEmpty($result['updated']);
        $this->assertDatabaseCount('plugins', 1);

        $this->cleanTempDir($dir);
    }

    // -------------------------------------------------------------------------
    // D4 — sync() reports orphaned slugs
    // -------------------------------------------------------------------------

    public function test_sync_reports_orphaned_plugin_when_its_directory_is_removed(): void
    {
        Plugin::create([
            'name'      => 'Ghost Plugin',
            'slug'      => 'ghost-plugin',
            'version'   => '1.0.0',
            'is_active' => false,
        ]);

        // Sync an empty directory — ghost-plugin has no manifest on disk.
        $emptyDir = sys_get_temp_dir() . '/bp-plugins-empty-' . uniqid();
        mkdir($emptyDir);

        $result = $this->registry->sync($emptyDir);

        $this->assertContains('ghost-plugin', $result['orphaned']);

        // Orphaned plugins are NOT deleted from DB automatically.
        $this->assertDatabaseHas('plugins', ['slug' => 'ghost-plugin']);

        $this->cleanTempDir($emptyDir);
    }

    // -------------------------------------------------------------------------
    // D5 — sync() skips invalid JSON manifests
    // -------------------------------------------------------------------------

    public function test_sync_skips_manifest_with_invalid_json(): void
    {
        $dir = $this->makeTempPlugin('bad-json', '{ not valid json }');

        $result = $this->registry->sync($dir);

        $this->assertEmpty($result['installed']);
        $this->assertDatabaseCount('plugins', 0);

        $this->cleanTempDir($dir);
    }

    // -------------------------------------------------------------------------
    // D6 — sync() skips manifests missing required fields
    // -------------------------------------------------------------------------

    public function test_sync_skips_manifest_missing_required_name(): void
    {
        $dir = $this->makeTempPlugin('no-name', [
            'slug'             => 'no-name',
            'version'          => '1.0.0',
            'service_provider' => 'App\\NoName',
        ]);

        $result = $this->registry->sync($dir);

        $this->assertEmpty($result['installed']);
        $this->cleanTempDir($dir);
    }

    public function test_sync_skips_manifest_missing_required_slug(): void
    {
        $dir = $this->makeTempPlugin('no-slug', [
            'name'             => 'No Slug Plugin',
            'version'          => '1.0.0',
            'service_provider' => 'App\\NoSlug',
        ]);

        $result = $this->registry->sync($dir);

        $this->assertEmpty($result['installed']);
        $this->cleanTempDir($dir);
    }

    public function test_sync_skips_manifest_missing_required_version(): void
    {
        $dir = $this->makeTempPlugin('no-version', [
            'name'             => 'No Version Plugin',
            'slug'             => 'no-version',
            'service_provider' => 'App\\NoVersion',
        ]);

        $result = $this->registry->sync($dir);

        $this->assertEmpty($result['installed']);
        $this->cleanTempDir($dir);
    }

    public function test_sync_skips_manifest_missing_required_service_provider(): void
    {
        $dir = $this->makeTempPlugin('no-provider', [
            'name'    => 'No Provider Plugin',
            'slug'    => 'no-provider',
            'version' => '1.0.0',
        ]);

        $result = $this->registry->sync($dir);

        $this->assertEmpty($result['installed']);
        $this->cleanTempDir($dir);
    }

    // -------------------------------------------------------------------------
    // D7 — sync() handles nonexistent directory gracefully
    // -------------------------------------------------------------------------

    public function test_sync_returns_empty_result_for_nonexistent_directory(): void
    {
        $result = $this->registry->sync('/nonexistent/path/that/does/not/exist');

        $this->assertSame([], $result['installed']);
        $this->assertSame([], $result['updated']);
        $this->assertSame([], $result['orphaned']);
        $this->assertSame([], $result['unchanged']);
    }

    // -------------------------------------------------------------------------
    // D8 — sync() discovers all three real app/Plugins manifests
    // -------------------------------------------------------------------------

    public function test_sync_discovers_all_three_real_plugin_manifests_from_app_plugins(): void
    {
        $result = $this->registry->sync(app_path('Plugins'));

        $this->assertContains('seo-manager', $result['installed']);
        $this->assertContains('contact-form', $result['installed']);
        $this->assertContains('analytics', $result['installed']);

        $this->assertDatabaseHas('plugins', ['slug' => 'seo-manager',   'version' => '1.0.0']);
        $this->assertDatabaseHas('plugins', ['slug' => 'contact-form',  'version' => '1.0.0']);
        $this->assertDatabaseHas('plugins', ['slug' => 'analytics',     'version' => '1.0.0']);
    }

    // -------------------------------------------------------------------------
    // D9 — sync() does not activate discovered plugins
    // -------------------------------------------------------------------------

    public function test_sync_never_activates_discovered_plugins(): void
    {
        $dir = $this->makeTempPlugin('activation-check', [
            'name'             => 'Activation Check',
            'slug'             => 'activation-check',
            'version'          => '1.0.0',
            'service_provider' => 'App\\Plugins\\ActivationCheck\\Provider',
        ]);

        $this->registry->sync($dir);

        foreach (Plugin::all() as $plugin) {
            $this->assertFalse(
                $plugin->is_active,
                "Plugin '{$plugin->slug}' was unexpectedly activated during sync."
            );
        }

        $this->cleanTempDir($dir);
    }

    // -------------------------------------------------------------------------
    // D10 — all() returns every plugin ordered by name
    // -------------------------------------------------------------------------

    public function test_all_returns_all_plugins_ordered_by_name(): void
    {
        Plugin::create(['name' => 'Zebra Plugin', 'slug' => 'zebra', 'version' => '1.0.0']);
        Plugin::create(['name' => 'Alpha Plugin', 'slug' => 'alpha', 'version' => '1.0.0']);
        Plugin::create(['name' => 'Mid Plugin',   'slug' => 'mid',   'version' => '1.0.0']);

        $plugins = $this->registry->all();

        $this->assertCount(3, $plugins);
        $this->assertSame('Alpha Plugin', $plugins->first()->name);
        $this->assertSame('Zebra Plugin', $plugins->last()->name);
    }

    // -------------------------------------------------------------------------
    // D11 — active() returns only active plugins and caches the result
    // -------------------------------------------------------------------------

    public function test_active_returns_only_active_plugins(): void
    {
        Plugin::create(['name' => 'Active One',   'slug' => 'active-one',   'version' => '1.0.0', 'is_active' => true]);
        Plugin::create(['name' => 'Inactive One', 'slug' => 'inactive-one', 'version' => '1.0.0', 'is_active' => false]);

        $active = $this->registry->active();

        $this->assertCount(1, $active);
        $this->assertSame('active-one', $active->first()->slug);
    }

    public function test_active_result_is_stored_in_cache(): void
    {
        Plugin::create(['name' => 'Cached Plugin', 'slug' => 'cached-plugin', 'version' => '1.0.0', 'is_active' => true]);

        $this->assertFalse(Cache::has(PluginRegistry::ACTIVE_CACHE_KEY));

        $this->registry->active();

        $this->assertTrue(Cache::has(PluginRegistry::ACTIVE_CACHE_KEY));
    }

    // -------------------------------------------------------------------------
    // D12 — find() by slug
    // -------------------------------------------------------------------------

    public function test_find_returns_plugin_by_slug(): void
    {
        Plugin::create(['name' => 'Findable Plugin', 'slug' => 'findable', 'version' => '1.0.0']);

        $plugin = $this->registry->find('findable');

        $this->assertNotNull($plugin);
        $this->assertSame('Findable Plugin', $plugin->name);
    }

    public function test_find_returns_null_for_unknown_slug(): void
    {
        $this->assertNull($this->registry->find('does-not-exist'));
    }

    // -------------------------------------------------------------------------
    // D13 — forget() clears the active plugins cache
    // -------------------------------------------------------------------------

    public function test_forget_clears_the_active_plugins_cache(): void
    {
        Plugin::create(['name' => 'Cache Test Plugin', 'slug' => 'cache-test', 'version' => '1.0.0', 'is_active' => true]);

        $this->registry->active(); // prime cache

        $this->assertTrue(Cache::has(PluginRegistry::ACTIVE_CACHE_KEY));

        $this->registry->forget();

        $this->assertFalse(Cache::has(PluginRegistry::ACTIVE_CACHE_KEY));
    }

    public function test_sync_clears_cache_after_any_change(): void
    {
        // Prime the cache with stale data.
        Cache::put(PluginRegistry::ACTIVE_CACHE_KEY, collect(), PluginRegistry::CACHE_TTL);

        $dir = $this->makeTempPlugin('cache-bust', [
            'name'             => 'Cache Bust Plugin',
            'slug'             => 'cache-bust',
            'version'          => '1.0.0',
            'service_provider' => 'App\\Plugins\\CacheBust\\Provider',
        ]);

        $this->registry->sync($dir);

        $this->assertFalse(Cache::has(PluginRegistry::ACTIVE_CACHE_KEY));

        $this->cleanTempDir($dir);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a temp directory with one plugin.json.
     * Pass an array for valid JSON or a string for raw (potentially invalid) JSON.
     */
    private function makeTempPlugin(string $slug, array|string $manifest): string
    {
        $root     = sys_get_temp_dir() . '/bp-plugins-' . uniqid();
        $pluginDir = $root . '/' . $slug;

        mkdir($pluginDir, 0755, true);

        $json = is_array($manifest) ? json_encode($manifest, JSON_PRETTY_PRINT) : $manifest;
        file_put_contents($pluginDir . '/plugin.json', $json);

        return $root;
    }

    /** Write a manifest into an existing root directory under the given slug subfolder. */
    private function writeManifest(string $root, string $slug, array $manifest): void
    {
        $dir = $root . '/' . $slug;
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/plugin.json', json_encode($manifest, JSON_PRETTY_PRINT));
    }

    private function cleanTempDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        ) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($dir);
    }
}
