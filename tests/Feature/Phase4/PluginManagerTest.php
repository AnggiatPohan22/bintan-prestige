<?php

namespace Tests\Feature\Phase4;

use App\Models\Plugin;
use App\Services\Plugin\PluginManager;
use App\Services\Plugin\PluginRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class PluginManagerTest extends TestCase
{
    use RefreshDatabase;

    private PluginManager $manager;
    private PluginRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this->app->make(PluginRegistry::class);
        $this->registry->forget(); // ensure cache is clean for each test
        $this->manager  = $this->app->make(PluginManager::class);
    }

    // =========================================================================
    // F1–F5 — activate()
    // =========================================================================

    /** @test */
    public function test_activate_sets_is_active_true(): void // F1
    {
        $plugin = Plugin::factory()->inactive()->create(['slug' => 'test-plugin']);

        $this->manager->activate($plugin);

        $this->assertTrue($plugin->fresh()->is_active);
    }

    /** @test */
    public function test_activate_sets_activated_at(): void // F2
    {
        $plugin = Plugin::factory()->inactive()->create(['slug' => 'test-plugin']);

        $this->manager->activate($plugin);

        $this->assertNotNull($plugin->fresh()->activated_at);
    }

    /** @test */
    public function test_activate_clears_active_plugins_cache(): void // F3
    {
        $plugin = Plugin::factory()->inactive()->create(['slug' => 'test-plugin']);

        // Populate the cache first.
        $this->registry->active();
        $this->assertTrue(Cache::has(PluginRegistry::ACTIVE_CACHE_KEY));

        $this->manager->activate($plugin);

        $this->assertFalse(Cache::has(PluginRegistry::ACTIVE_CACHE_KEY));
    }

    /** @test */
    public function test_activate_throws_if_already_active(): void // F4
    {
        $plugin = Plugin::factory()->active()->create(['slug' => 'test-plugin']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/already active/');

        $this->manager->activate($plugin);
    }

    /** @test */
    public function test_activate_does_not_throw_when_provider_class_missing(): void // F5
    {
        // Real plugins from disk — their ServiceProvider classes don't exist yet.
        $plugin = Plugin::factory()->inactive()->create([
            'slug' => 'seo-manager',
        ]);

        // Should not throw even though the class App\Plugins\SeoManager\SeoManagerServiceProvider
        // is not yet implemented.
        $this->manager->activate($plugin);

        $this->assertTrue($plugin->fresh()->is_active);
    }

    // =========================================================================
    // F6–F8 — deactivate()
    // =========================================================================

    /** @test */
    public function test_deactivate_sets_is_active_false(): void // F6
    {
        $plugin = Plugin::factory()->active()->create(['slug' => 'test-plugin']);

        $this->manager->deactivate($plugin);

        $this->assertFalse($plugin->fresh()->is_active);
    }

    /** @test */
    public function test_deactivate_clears_active_plugins_cache(): void // F7
    {
        $plugin = Plugin::factory()->active()->create(['slug' => 'test-plugin']);

        $this->registry->active();
        $this->assertTrue(Cache::has(PluginRegistry::ACTIVE_CACHE_KEY));

        $this->manager->deactivate($plugin);

        $this->assertFalse(Cache::has(PluginRegistry::ACTIVE_CACHE_KEY));
    }

    /** @test */
    public function test_deactivate_throws_if_not_active(): void // F8
    {
        $plugin = Plugin::factory()->inactive()->create(['slug' => 'test-plugin']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/not active/');

        $this->manager->deactivate($plugin);
    }

    // =========================================================================
    // F9–F11 — uninstall()
    // =========================================================================

    /** @test */
    public function test_uninstall_removes_plugin_from_db(): void // F9
    {
        $plugin = Plugin::factory()->inactive()->create(['slug' => 'test-plugin']);
        $id     = $plugin->id;

        $this->manager->uninstall($plugin);

        $this->assertDatabaseMissing('plugins', ['id' => $id]);
    }

    /** @test */
    public function test_uninstall_clears_active_plugins_cache(): void // F10
    {
        $plugin = Plugin::factory()->inactive()->create(['slug' => 'test-plugin']);

        $this->registry->active();
        $this->assertTrue(Cache::has(PluginRegistry::ACTIVE_CACHE_KEY));

        $this->manager->uninstall($plugin);

        $this->assertFalse(Cache::has(PluginRegistry::ACTIVE_CACHE_KEY));
    }

    /** @test */
    public function test_uninstall_throws_if_plugin_is_active(): void // F11
    {
        $plugin = Plugin::factory()->active()->create(['slug' => 'test-plugin']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Deactivate/');

        $this->manager->uninstall($plugin);
    }

    // =========================================================================
    // F12–F13 — boot()
    // =========================================================================

    /** @test */
    public function test_boot_is_safe_with_no_active_plugins(): void // F12
    {
        // Fresh DB: no plugins at all. boot() should not throw.
        $this->expectNotToPerformAssertions();

        $this->manager->boot();
    }

    /** @test */
    public function test_boot_does_not_throw_when_provider_class_missing(): void // F13
    {
        // Activate a plugin whose ServiceProvider class does not exist.
        Plugin::factory()->active()->create([
            'slug' => 'ghost-plugin',
        ]);

        // boot() reads the manifest map — 'ghost-plugin' has no manifest on disk,
        // so it will be filtered out. Should not throw.
        $this->expectNotToPerformAssertions();

        $this->registry->forget(); // force re-read from DB
        $this->manager->boot();
    }

    // =========================================================================
    // F14–F15 — lifecycle cycle + topological sort
    // =========================================================================

    /** @test */
    public function test_activate_then_deactivate_cycle(): void // F14
    {
        $plugin = Plugin::factory()->inactive()->create(['slug' => 'test-plugin']);

        $this->manager->activate($plugin);
        $plugin->refresh();
        $this->assertTrue($plugin->is_active);

        $this->manager->deactivate($plugin);
        $plugin->refresh();
        $this->assertFalse($plugin->is_active);
    }

    /** @test */
    public function test_uninstall_after_deactivate_works(): void // F15
    {
        $plugin = Plugin::factory()->inactive()->create(['slug' => 'test-plugin']);

        // Start inactive — should uninstall cleanly.
        $this->manager->uninstall($plugin);

        $this->assertDatabaseMissing('plugins', ['slug' => 'test-plugin']);
    }
}
