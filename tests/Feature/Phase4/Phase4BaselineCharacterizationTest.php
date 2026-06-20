<?php

namespace Tests\Feature\Phase4;

use App\Models\AuditLog;
use App\Models\Page;
use App\Models\Plugin;
use App\Models\User;
use App\Services\GlobalSettingsService;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * STEP 0 — Phase 4 Audit & Safety Baseline
 *
 * These tests lock the six contracts that Phase 4 (Plugin System) will extend.
 * Any Phase 4 change that breaks these tests has broken backward compatibility.
 *
 * Contracts covered:
 *   C1 — Plugin directory: app/Plugins/ exists on disk and namespace is available
 *   C2 — AuditLog contract: record() creates entries correctly; no-op when unauthenticated
 *   C3 — Admin route middleware: Phase 1–3 admin routes require auth + admin
 *   C4 — Cache key isolation: Phase 4 keys don't collide with Phase 1–3 keys
 *   C5 — Plugin table schema: correct columns, is_active default, unique slug constraint
 *   C6 — Regression baseline: Phase 1–3 test file count is ≥ 45
 */
class Phase4BaselineCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // -------------------------------------------------------------------------
    // C1 — Plugin directory: app/Plugins/ exists and namespace is available
    // -------------------------------------------------------------------------

    public function test_app_plugins_directory_exists_on_disk(): void
    {
        $this->assertDirectoryExists(
            app_path('Plugins'),
            'app/Plugins/ directory must exist — it is the root of all plugin packages.'
        );
    }

    public function test_app_plugins_directory_is_writable(): void
    {
        $this->assertTrue(
            is_writable(app_path('Plugins')),
            'app/Plugins/ must be writable so PluginRegistry can detect new plugin directories.'
        );
    }

    public function test_plugin_namespace_class_path_is_correct(): void
    {
        // App\Plugins\{Name}\Provider autoloads via the PSR-4 App\ → app/ mapping.
        // Verify that mapping exists and that app/Plugins/ is a child of the App\ root.
        $composerPsr4 = require base_path('vendor/composer/autoload_psr4.php');

        $this->assertArrayHasKey(
            'App\\',
            $composerPsr4,
            'App\\ PSR-4 autoload entry must exist for App\\Plugins namespace to work.'
        );

        $appRoot = realpath($composerPsr4['App\\'][0]);

        // app/Plugins/ must be a subdirectory of the PSR-4 App\ root (app/).
        $this->assertStringStartsWith(
            $appRoot,
            app_path('Plugins'),
            'app/Plugins/ must be under the PSR-4 App\\ root so App\\Plugins\\* classes are found.'
        );
    }

    // -------------------------------------------------------------------------
    // C2 — AuditLog contract
    // -------------------------------------------------------------------------

    public function test_audit_log_record_creates_entry_when_user_is_authenticated(): void
    {
        $admin = User::factory()->admin()->create();
        $page  = Page::create(['title' => 'Audit Test Page', 'slug' => 'audit-test', 'status' => 'draft']);

        $this->actingAs($admin);

        AuditLog::record('created', $page, null, ['title' => 'Audit Test Page']);

        $this->assertDatabaseHas('audit_logs', [
            'user_id'        => $admin->id,
            'action'         => 'created',
            'auditable_type' => 'Page',
            'auditable_id'   => $page->id,
        ]);
    }

    public function test_audit_log_record_is_a_noop_when_no_user_is_authenticated(): void
    {
        $page = Page::create(['title' => 'Noop Page', 'slug' => 'noop-page', 'status' => 'draft']);

        // No actingAs — guest context.
        AuditLog::record('created', $page, null, []);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_audit_log_resolves_label_from_title_field(): void
    {
        $admin = User::factory()->admin()->create();
        $page  = Page::create(['title' => 'Label Resolution Page', 'slug' => 'label-page', 'status' => 'draft']);

        $this->actingAs($admin);
        AuditLog::record('created', $page, null, []);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_label' => 'Label Resolution Page',
        ]);
    }

    public function test_audit_log_updated_action_stores_only_changed_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $page = Page::create(['title' => 'Original Title', 'slug' => 'original-slug', 'status' => 'draft']);

        AuditLog::record(
            'updated',
            $page,
            ['status' => 'draft'],
            ['status' => 'published']
        );

        $log = AuditLog::latest('id')->first();
        $this->assertSame(['status' => 'draft'], $log->old_values);
        $this->assertSame(['status' => 'published'], $log->new_values);
    }

    // -------------------------------------------------------------------------
    // C3 — Admin route middleware: Phase 1–3 routes require auth + admin
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_to_login_on_all_phase_1_3_admin_routes(): void
    {
        $routes = [
            route('admin.pages.index'),
            route('admin.themes.index'),
            route('admin.menus.index'),
            route('admin.products.index'),
            route('admin.audit-logs.index'),
        ];

        foreach ($routes as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_non_admin_user_gets_403_on_all_phase_1_3_admin_routes(): void
    {
        $user = User::factory()->create(); // is_admin = false

        $routes = [
            route('admin.pages.index'),
            route('admin.themes.index'),
            route('admin.menus.index'),
            route('admin.products.index'),
            route('admin.audit-logs.index'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }

    public function test_admin_can_reach_all_phase_1_3_admin_index_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $routes = [
            route('admin.pages.index'),
            route('admin.themes.index'),
            route('admin.menus.index'),
            route('admin.products.index'),
            route('admin.audit-logs.index'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    // -------------------------------------------------------------------------
    // C4 — Cache key isolation
    // -------------------------------------------------------------------------

    public function test_phase_4_plugin_cache_keys_do_not_collide_with_phase_1_3_keys(): void
    {
        $existingKeys = [
            GlobalSettingsService::SETTINGS_CACHE_KEY,  // 'global_settings.public.v1'
            GlobalSettingsService::ASSETS_CACHE_KEY,    // 'global_assets.public.v1'
            ThemeService::CACHE_KEY,                    // 'theme.active.v1'
        ];

        $phase4Keys = [
            'active_plugins',       // PluginManager bootstrap cache
            'plugin.registry.v1',   // PluginRegistry discovery cache
        ];

        foreach ($phase4Keys as $key) {
            $this->assertNotContains(
                $key,
                $existingKeys,
                "Phase 4 cache key '{$key}' collides with an existing Phase 1–3 cache key."
            );
        }
    }

    public function test_phase_4_cache_keys_follow_consistent_naming_convention(): void
    {
        // All Phase 1–3 versioned keys use the pattern: scope.name.v1
        // Phase 4 plugin cache keys must follow the same pattern or use a reserved prefix.
        $phase4VersionedKeys = [
            'plugin.registry.v1',
        ];

        foreach ($phase4VersionedKeys as $key) {
            $this->assertMatchesRegularExpression(
                '/^[a-z_]+\.[a-z_]+\.v\d+$/',
                $key,
                "Phase 4 cache key '{$key}' does not follow the 'scope.name.vN' naming convention."
            );
        }
    }

    // -------------------------------------------------------------------------
    // C5 — Plugin table schema
    // -------------------------------------------------------------------------

    public function test_plugins_table_exists_after_migration(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasTable('plugins'),
            'plugins table must exist — run: php artisan migrate'
        );
    }

    public function test_plugins_table_has_all_required_columns(): void
    {
        $required = ['id', 'name', 'slug', 'version', 'author', 'description', 'is_active', 'config', 'installed_at', 'activated_at', 'created_at', 'updated_at'];

        foreach ($required as $column) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Schema::hasColumn('plugins', $column),
                "plugins table is missing column: {$column}"
            );
        }
    }

    public function test_plugin_model_defaults_is_active_to_false(): void
    {
        $plugin = Plugin::create([
            'name'    => 'Test Plugin',
            'slug'    => 'test-plugin',
            'version' => '1.0.0',
        ]);

        $this->assertFalse($plugin->fresh()->is_active);
    }

    public function test_plugin_slug_is_unique(): void
    {
        Plugin::create(['name' => 'First', 'slug' => 'same-slug', 'version' => '1.0.0']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Plugin::create(['name' => 'Second', 'slug' => 'same-slug', 'version' => '1.0.0']);
    }

    public function test_plugin_active_scope_returns_only_active_plugins(): void
    {
        Plugin::create(['name' => 'Active Plugin',   'slug' => 'active-plugin',   'version' => '1.0.0', 'is_active' => true]);
        Plugin::create(['name' => 'Inactive Plugin', 'slug' => 'inactive-plugin', 'version' => '1.0.0', 'is_active' => false]);

        $active = Plugin::active()->get();

        $this->assertCount(1, $active);
        $this->assertSame('active-plugin', $active->first()->slug);
    }

    public function test_plugin_config_is_cast_to_array(): void
    {
        $plugin = Plugin::create([
            'name'    => 'Config Plugin',
            'slug'    => 'config-plugin',
            'version' => '1.0.0',
            'config'  => ['api_key' => 'abc123', 'enabled' => true],
        ]);

        $this->assertIsArray($plugin->fresh()->config);
        $this->assertSame('abc123', $plugin->fresh()->config['api_key']);
    }

    // -------------------------------------------------------------------------
    // C6 — Regression baseline: Phase 1–3 test file count
    // -------------------------------------------------------------------------

    public function test_phase_1_3_test_file_count_is_at_least_45(): void
    {
        $testRoot = base_path('tests/Feature');

        $files = array_merge(
            glob($testRoot . '/*.php') ?: [],
            glob($testRoot . '/Admin/*.php') ?: [],
            glob($testRoot . '/Frontend/*.php') ?: [],
            glob($testRoot . '/Database/*.php') ?: [],
            glob($testRoot . '/Security/*.php') ?: [],
            glob($testRoot . '/Performance/*.php') ?: [],
            glob($testRoot . '/Console/*.php') ?: [],
            glob($testRoot . '/Auth/*.php') ?: [],
            glob($testRoot . '/Phase3/*.php') ?: [],
        );

        $testFiles = array_filter($files, fn ($f) => str_ends_with($f, 'Test.php'));

        $this->assertGreaterThanOrEqual(
            45,
            count($testFiles),
            'Expected at least 45 Phase 1–3 test files. Found ' . count($testFiles) . '. '
            . 'A lower count indicates tests were deleted or a directory was missed.'
        );
    }

    public function test_phase_4_test_directory_exists(): void
    {
        $this->assertDirectoryExists(
            base_path('tests/Feature/Phase4'),
            'tests/Feature/Phase4/ directory must exist for Phase 4 test suite.'
        );
    }
}
