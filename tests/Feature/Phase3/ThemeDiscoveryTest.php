<?php

namespace Tests\Feature\Phase3;

use App\Models\Theme;
use App\Models\User;
use App\Services\ThemeDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // ThemeDiscoveryService unit-level tests
    // -------------------------------------------------------------------------

    public function test_scan_discovers_bintan_prestige_luxury_theme_from_real_themes_directory(): void
    {
        $service    = new ThemeDiscoveryService();
        $discovered = $service->scan();

        $this->assertContains('bintan-prestige-luxury', $discovered);
        $this->assertDatabaseHas('themes', [
            'slug'      => 'bintan-prestige-luxury',
            'name'      => 'Bintan Prestige Luxury',
            'directory' => 'themes/bintan-prestige-luxury',
        ]);
    }

    public function test_scan_returns_empty_array_when_themes_directory_does_not_exist(): void
    {
        $service    = new ThemeDiscoveryService();
        $discovered = $service->scan('/nonexistent/themes/path');

        $this->assertSame([], $discovered);
        $this->assertDatabaseCount('themes', 0);
    }

    public function test_scan_skips_manifest_with_invalid_json(): void
    {
        $tempDir = $this->makeTempThemeDir('bad-json-theme', '{ not valid json }');

        $service    = new ThemeDiscoveryService();
        $discovered = $service->scan($tempDir);

        $this->assertSame([], $discovered);
        $this->assertDatabaseCount('themes', 0);

        $this->cleanTempDir($tempDir);
    }

    public function test_scan_skips_manifest_missing_required_name_field(): void
    {
        $tempDir = $this->makeTempThemeDir('no-name', json_encode(['slug' => 'no-name']));

        $service    = new ThemeDiscoveryService();
        $discovered = $service->scan($tempDir);

        $this->assertSame([], $discovered);

        $this->cleanTempDir($tempDir);
    }

    public function test_scan_skips_manifest_missing_required_slug_field(): void
    {
        $tempDir = $this->makeTempThemeDir('no-slug', json_encode(['name' => 'No Slug Theme']));

        $service    = new ThemeDiscoveryService();
        $discovered = $service->scan($tempDir);

        $this->assertSame([], $discovered);

        $this->cleanTempDir($tempDir);
    }

    public function test_scan_updates_existing_theme_record_without_creating_duplicate(): void
    {
        $tempDir = $this->makeTempThemeDir('update-test', json_encode([
            'name' => 'Update Test v1',
            'slug' => 'update-test',
        ]));

        $service = new ThemeDiscoveryService();
        $service->scan($tempDir);

        $this->assertDatabaseCount('themes', 1);

        $this->overwriteManifest($tempDir, 'update-test', json_encode([
            'name'    => 'Update Test v2',
            'slug'    => 'update-test',
            'version' => '2.0.0',
        ]));

        $service->scan($tempDir);

        $this->assertDatabaseCount('themes', 1);
        $this->assertDatabaseHas('themes', [
            'slug'    => 'update-test',
            'name'    => 'Update Test v2',
            'version' => '2.0.0',
        ]);

        $this->cleanTempDir($tempDir);
    }

    public function test_scan_does_not_activate_discovered_themes(): void
    {
        $service = new ThemeDiscoveryService();
        $service->scan();

        foreach (Theme::all() as $theme) {
            $this->assertFalse($theme->is_active, "Theme '{$theme->slug}' was unexpectedly activated during scan.");
        }
    }

    public function test_scan_discovers_multiple_themes_from_a_directory(): void
    {
        $tempDir = sys_get_temp_dir() . '/test-themes-multi-' . uniqid();
        mkdir($tempDir);

        foreach (['alpha-theme', 'beta-theme'] as $slug) {
            $themeDir = $tempDir . '/' . $slug;
            mkdir($themeDir);
            file_put_contents($themeDir . '/theme.json', json_encode([
                'name' => ucfirst($slug),
                'slug' => $slug,
            ]));
        }

        $service    = new ThemeDiscoveryService();
        $discovered = $service->scan($tempDir);

        $this->assertCount(2, $discovered);
        $this->assertContains('alpha-theme', $discovered);
        $this->assertContains('beta-theme', $discovered);

        $this->cleanTempDir($tempDir);
    }

    // -------------------------------------------------------------------------
    // Theme model tests
    // -------------------------------------------------------------------------

    public function test_theme_model_ordered_scope_places_active_theme_first(): void
    {
        Theme::create(['name' => 'Inactive Theme', 'slug' => 'inactive', 'directory' => 'themes/inactive', 'is_active' => false, 'sort_order' => 0]);
        Theme::create(['name' => 'Active Theme', 'slug' => 'active', 'directory' => 'themes/active', 'is_active' => true, 'sort_order' => 1]);

        $first = Theme::ordered()->first();

        $this->assertSame('active', $first->slug);
    }

    public function test_theme_model_active_scope_returns_only_active_themes(): void
    {
        Theme::create(['name' => 'Active',   'slug' => 'active',   'directory' => 'themes/active',   'is_active' => true,  'sort_order' => 0]);
        Theme::create(['name' => 'Inactive', 'slug' => 'inactive', 'directory' => 'themes/inactive', 'is_active' => false, 'sort_order' => 0]);

        $active = Theme::active()->get();

        $this->assertCount(1, $active);
        $this->assertSame('active', $active->first()->slug);
    }

    public function test_theme_model_base_path_returns_absolute_path(): void
    {
        $theme = new Theme(['directory' => 'themes/bintan-prestige-luxury']);

        $this->assertSame(base_path('themes/bintan-prestige-luxury'), $theme->basePath());
    }

    public function test_theme_model_has_screenshot_returns_false_when_screenshot_is_null(): void
    {
        $theme = new Theme(['directory' => 'themes/bintan-prestige-luxury', 'screenshot' => null]);

        $this->assertFalse($theme->hasScreenshot());
    }

    // -------------------------------------------------------------------------
    // Admin controller / route tests
    // -------------------------------------------------------------------------

    public function test_admin_can_access_themes_index_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.themes.index'))
            ->assertOk()
            ->assertSee('Themes')
            ->assertSee('Scan for Themes');
    }

    public function test_themes_index_shows_empty_state_when_no_themes_are_registered(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.themes.index'))
            ->assertOk()
            ->assertSee('No themes registered yet');
    }

    public function test_themes_index_lists_registered_themes(): void
    {
        Theme::create([
            'name'      => 'Luxury Gold',
            'slug'      => 'luxury-gold',
            'directory' => 'themes/luxury-gold',
            'version'   => '1.2.0',
            'is_active' => false,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.themes.index'))
            ->assertOk()
            ->assertSee('Luxury Gold')
            ->assertSee('v1.2.0')
            ->assertSee('Inactive');
    }

    public function test_themes_index_shows_active_badge_for_the_active_theme(): void
    {
        Theme::create([
            'name'      => 'Live Theme',
            'slug'      => 'live-theme',
            'directory' => 'themes/live-theme',
            'is_active' => true,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.themes.index'))
            ->assertOk()
            ->assertSee('Active');
    }

    public function test_admin_scan_route_triggers_discovery_and_redirects(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.themes.scan'));

        $response->assertRedirect(route('admin.themes.index'));
    }

    public function test_admin_scan_registers_bintan_prestige_luxury_theme(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.themes.scan'));

        $this->assertDatabaseHas('themes', ['slug' => 'bintan-prestige-luxury']);
    }

    public function test_unauthenticated_user_cannot_access_themes_routes(): void
    {
        $this->get(route('admin.themes.index'))->assertRedirect(route('login'));
        $this->post(route('admin.themes.scan'))->assertRedirect(route('login'));
    }

    public function test_non_admin_user_cannot_access_themes_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.themes.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.themes.scan'))->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeTempThemeDir(string $slug, string $manifestJson): string
    {
        $root     = sys_get_temp_dir() . '/test-themes-' . uniqid();
        $themeDir = $root . '/' . $slug;

        mkdir($themeDir, 0755, true);
        file_put_contents($themeDir . '/theme.json', $manifestJson);

        return $root;
    }

    private function overwriteManifest(string $root, string $slug, string $manifestJson): void
    {
        file_put_contents($root . '/' . $slug . '/theme.json', $manifestJson);
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
