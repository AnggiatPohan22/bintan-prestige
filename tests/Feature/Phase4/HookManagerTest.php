<?php

namespace Tests\Feature\Phase4;

use App\Facades\CmsHooks;
use App\Models\Plugin;
use App\Services\Plugin\PluginManager;
use App\Support\HookManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HookManagerTest extends TestCase
{
    use RefreshDatabase;

    private HookManager $hooks;

    protected function setUp(): void
    {
        parent::setUp();
        // Each test gets the app's singleton HookManager — fresh per test because
        // TestCase::setUp() calls createApplication(), giving a new container.
        $this->hooks = $this->app->make(HookManager::class);
    }

    // =========================================================================
    // G1–G5 — Action hooks
    // =========================================================================

    /** @test */
    public function test_registered_action_callback_is_called(): void // G1
    {
        $called = false;

        $this->hooks->addAction('test.action', function () use (&$called) {
            $called = true;
        });

        $this->hooks->doAction('test.action');

        $this->assertTrue($called);
    }

    /** @test */
    public function test_action_callback_receives_arguments(): void // G2
    {
        $received = null;

        $this->hooks->addAction('test.action', function ($value) use (&$received) {
            $received = $value;
        });

        $this->hooks->doAction('test.action', 'hello-world');

        $this->assertSame('hello-world', $received);
    }

    /** @test */
    public function test_multiple_action_callbacks_all_fire(): void // G3
    {
        $calls = [];

        $this->hooks->addAction('test.action', function () use (&$calls) { $calls[] = 'a'; });
        $this->hooks->addAction('test.action', function () use (&$calls) { $calls[] = 'b'; });
        $this->hooks->addAction('test.action', function () use (&$calls) { $calls[] = 'c'; });

        $this->hooks->doAction('test.action');

        $this->assertCount(3, $calls);
    }

    /** @test */
    public function test_action_callbacks_fire_in_priority_order(): void // G4
    {
        $order = [];

        $this->hooks->addAction('test.action', function () use (&$order) { $order[] = 'low';    }, priority: 20);
        $this->hooks->addAction('test.action', function () use (&$order) { $order[] = 'high';   }, priority: 1);
        $this->hooks->addAction('test.action', function () use (&$order) { $order[] = 'normal'; }, priority: 10);

        $this->hooks->doAction('test.action');

        $this->assertSame(['high', 'normal', 'low'], $order);
    }

    /** @test */
    public function test_do_action_on_unregistered_hook_does_not_throw(): void // G5
    {
        $this->expectNotToPerformAssertions();

        $this->hooks->doAction('hook.that.does.not.exist');
    }

    // =========================================================================
    // G6–G10 — Filter hooks
    // =========================================================================

    /** @test */
    public function test_registered_filter_transforms_value(): void // G6
    {
        $this->hooks->addFilter('test.filter', fn (string $v) => strtoupper($v));

        $result = $this->hooks->applyFilters('test.filter', 'hello');

        $this->assertSame('HELLO', $result);
    }

    /** @test */
    public function test_filter_callback_receives_extra_arguments(): void // G7
    {
        $this->hooks->addFilter('test.filter', function (string $value, string $suffix) {
            return $value . $suffix;
        });

        $result = $this->hooks->applyFilters('test.filter', 'hello', '-world');

        $this->assertSame('hello-world', $result);
    }

    /** @test */
    public function test_multiple_filter_callbacks_chain_correctly(): void // G8
    {
        $this->hooks->addFilter('test.filter', fn (int $v) => $v + 1);
        $this->hooks->addFilter('test.filter', fn (int $v) => $v * 2);
        $this->hooks->addFilter('test.filter', fn (int $v) => $v - 3);

        // ((5 + 1) * 2) - 3 = 9
        $result = $this->hooks->applyFilters('test.filter', 5);

        $this->assertSame(9, $result);
    }

    /** @test */
    public function test_filter_callbacks_fire_in_priority_order(): void // G9
    {
        $this->hooks->addFilter('test.filter', fn (string $v) => $v . '-last',   priority: 30);
        $this->hooks->addFilter('test.filter', fn (string $v) => $v . '-first',  priority: 1);
        $this->hooks->addFilter('test.filter', fn (string $v) => $v . '-middle', priority: 15);

        $result = $this->hooks->applyFilters('test.filter', 'start');

        $this->assertSame('start-first-middle-last', $result);
    }

    /** @test */
    public function test_apply_filters_returns_original_value_when_no_filters_registered(): void // G10
    {
        $result = $this->hooks->applyFilters('unregistered.filter', 'original-value');

        $this->assertSame('original-value', $result);
    }

    // =========================================================================
    // G11–G13 — has* and removeAll utilities
    // =========================================================================

    /** @test */
    public function test_has_action_returns_true_when_registered(): void // G11
    {
        $this->assertFalse($this->hooks->hasAction('my.action'));

        $this->hooks->addAction('my.action', fn () => null);

        $this->assertTrue($this->hooks->hasAction('my.action'));
    }

    /** @test */
    public function test_has_filter_returns_true_when_registered(): void // G12
    {
        $this->assertFalse($this->hooks->hasFilter('my.filter'));

        $this->hooks->addFilter('my.filter', fn ($v) => $v);

        $this->assertTrue($this->hooks->hasFilter('my.filter'));
    }

    /** @test */
    public function test_remove_all_clears_both_actions_and_filters(): void // G13
    {
        $this->hooks->addAction('my.hook', fn () => null);
        $this->hooks->addFilter('my.hook', fn ($v) => $v);

        $this->hooks->removeAll('my.hook');

        $this->assertFalse($this->hooks->hasAction('my.hook'));
        $this->assertFalse($this->hooks->hasFilter('my.hook'));
    }

    // =========================================================================
    // G14–G15 — Facade + singleton
    // =========================================================================

    /** @test */
    public function test_cms_hooks_facade_resolves_to_hook_manager(): void // G14
    {
        $this->assertInstanceOf(HookManager::class, CmsHooks::getFacadeRoot());
    }

    /** @test */
    public function test_hook_manager_is_a_singleton(): void // G15
    {
        $instance1 = $this->app->make(HookManager::class);
        $instance2 = $this->app->make(HookManager::class);

        $this->assertSame($instance1, $instance2);
    }

    // =========================================================================
    // G16 — Integration: plugin.activated hook fires via PluginManager
    // =========================================================================

    /** @test */
    public function test_plugin_activated_hook_fires_when_plugin_is_activated(): void // G16
    {
        $fired = false;

        CmsHooks::addAction('plugin.activated', function () use (&$fired) {
            $fired = true;
        });

        $plugin = Plugin::factory()->inactive()->create(['slug' => 'test-plugin']);
        $this->app->make(PluginManager::class)->activate($plugin);

        $this->assertTrue($fired);
    }

    /** @test */
    public function test_plugin_deactivated_hook_fires_when_plugin_is_deactivated(): void // G17
    {
        $fired = false;

        CmsHooks::addAction('plugin.deactivated', function () use (&$fired) {
            $fired = true;
        });

        $plugin = Plugin::factory()->active()->create(['slug' => 'test-plugin']);
        $this->app->make(PluginManager::class)->deactivate($plugin);

        $this->assertTrue($fired);
    }

    // =========================================================================
    // G18 — Integration: admin.loaded hook fires via AdminMiddleware
    // =========================================================================

    /** @test */
    public function test_admin_loaded_hook_fires_on_admin_request(): void // G18
    {
        $fired = false;

        CmsHooks::addAction('admin.loaded', function () use (&$fired) {
            $fired = true;
        });

        $admin = \App\Models\User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'));

        $this->assertTrue($fired);
    }
}
