<?php

namespace Tests\Feature\Phase3;

use App\Models\Theme;
use App\Models\User;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ThemeSwitcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Reset cache and any in-memory singleton state between tests.
        app(ThemeService::class)->forget();
    }

    // -------------------------------------------------------------------------
    // Activate action — DB state
    // -------------------------------------------------------------------------

    public function test_activating_a_theme_marks_it_active_and_deactivates_all_others(): void
    {
        $alpha = $this->theme('alpha', false);
        $beta  = $this->theme('beta', true);   // currently active

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.themes.activate', $alpha))
            ->assertRedirect(route('admin.themes.index'));

        $this->assertDatabaseHas('themes', ['slug' => 'alpha', 'is_active' => true]);
        $this->assertDatabaseHas('themes', ['slug' => 'beta',  'is_active' => false]);
    }

    public function test_activate_redirects_to_index_with_success_flash(): void
    {
        $theme = $this->theme('flash-test', false);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.themes.activate', $theme))
            ->assertRedirect(route('admin.themes.index'))
            ->assertSessionHas('success');
    }

    // -------------------------------------------------------------------------
    // Activate route — security
    // -------------------------------------------------------------------------

    public function test_activate_route_redirects_unauthenticated_users_to_login(): void
    {
        $theme = $this->theme('unauth-test', false);

        $this->patch(route('admin.themes.activate', $theme))
            ->assertRedirect(route('login'));
    }

    public function test_activate_route_is_forbidden_for_non_admin_users(): void
    {
        $theme = $this->theme('nonadmin-test', false);
        $user  = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('admin.themes.activate', $theme))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // ThemeService — resolveLayout
    // -------------------------------------------------------------------------

    public function test_resolve_layout_returns_default_template_when_no_theme_is_active(): void
    {
        $this->theme('inactive', false);

        $result = app(ThemeService::class)->resolveLayout('default');

        $this->assertSame('frontend.templates.default', $result);
    }

    public function test_resolve_layout_returns_default_template_when_active_theme_has_no_layout_file(): void
    {
        // Active theme exists in DB but no blade file on disk.
        $this->theme('no-layout', true);

        $result = app(ThemeService::class)->resolveLayout('default');

        $this->assertSame('frontend.templates.default', $result);
    }

    public function test_resolve_layout_returns_theme_namespaced_view_when_layout_file_exists(): void
    {
        $slug      = 'test-layout-' . uniqid();
        $layoutDir = base_path("themes/{$slug}/layouts");
        mkdir($layoutDir, 0755, true);
        file_put_contents($layoutDir . '/default.blade.php', '<html><body>@yield("content")</body></html>');

        $this->theme($slug, true);

        $result = app(ThemeService::class)->resolveLayout('default');

        $this->assertSame('theme-active::layouts.default', $result);

        $this->cleanDir(base_path("themes/{$slug}"));
    }

    // -------------------------------------------------------------------------
    // ThemeService — resolvePartial
    // -------------------------------------------------------------------------

    public function test_resolve_partial_returns_default_when_no_theme_is_active(): void
    {
        $result = app(ThemeService::class)->resolvePartial('header');

        $this->assertSame('frontend.partials.header', $result);
    }

    public function test_resolve_partial_returns_default_when_active_theme_has_no_partial_file(): void
    {
        $this->theme('no-partial', true);

        $this->assertSame('frontend.partials.header', app(ThemeService::class)->resolvePartial('header'));
        $this->assertSame('frontend.partials.footer', app(ThemeService::class)->resolvePartial('footer'));
    }

    public function test_resolve_partial_returns_theme_namespaced_view_when_partial_file_exists(): void
    {
        $slug       = 'test-partial-' . uniqid();
        $partialDir = base_path("themes/{$slug}/partials");
        mkdir($partialDir, 0755, true);
        file_put_contents($partialDir . '/header.blade.php', '<header>Theme Header</header>');

        $this->theme($slug, true);

        $result = app(ThemeService::class)->resolvePartial('header');

        $this->assertSame('theme-active::partials.header', $result);

        $this->cleanDir(base_path("themes/{$slug}"));
    }

    // -------------------------------------------------------------------------
    // ThemeService — cache invalidation
    // -------------------------------------------------------------------------

    public function test_cache_is_populated_after_get_active_theme(): void
    {
        $this->theme('cached', true);

        app(ThemeService::class)->getActiveTheme();

        $this->assertTrue(Cache::has(ThemeService::CACHE_KEY));
    }

    public function test_cache_is_cleared_when_a_theme_is_saved(): void
    {
        $theme = $this->theme('save-clear', true);

        app(ThemeService::class)->getActiveTheme();
        $this->assertTrue(Cache::has(ThemeService::CACHE_KEY));

        $theme->touch();

        $this->assertFalse(Cache::has(ThemeService::CACHE_KEY));
    }

    public function test_cache_is_cleared_when_a_theme_is_deleted(): void
    {
        $theme = $this->theme('delete-clear', true);

        app(ThemeService::class)->getActiveTheme();
        $this->assertTrue(Cache::has(ThemeService::CACHE_KEY));

        $theme->delete();

        $this->assertFalse(Cache::has(ThemeService::CACHE_KEY));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function theme(string $slug, bool $active): Theme
    {
        return Theme::create([
            'name'      => ucwords(str_replace('-', ' ', $slug)),
            'slug'      => $slug,
            'directory' => "themes/{$slug}",
            'is_active' => $active,
        ]);
    }

    private function cleanDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        ) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($dir);
    }
}
