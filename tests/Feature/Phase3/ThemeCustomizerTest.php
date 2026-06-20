<?php

namespace Tests\Feature\Phase3;

use App\Models\Page;
use App\Models\Theme;
use App\Models\User;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ThemeCustomizerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ThemeService::class)->forget();
    }

    // -------------------------------------------------------------------------
    // Customize page — access
    // -------------------------------------------------------------------------

    public function test_admin_can_access_customize_page(): void
    {
        $theme = $this->theme('access-test');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.themes.customize', $theme))
            ->assertOk()
            ->assertSee('Customize: Access Test');
    }

    public function test_customize_page_requires_admin_auth(): void
    {
        $theme = $this->theme('auth-test');

        $this->get(route('admin.themes.customize', $theme))
            ->assertRedirect(route('login'));
    }

    public function test_customize_page_is_forbidden_for_non_admin(): void
    {
        $theme = $this->theme('forbidden-test');
        $user  = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.themes.customize', $theme))
            ->assertForbidden();
    }

    public function test_customize_page_shows_empty_state_when_schema_is_missing(): void
    {
        // Theme with no schema in its manifest (directory doesn't exist on disk)
        $theme = $this->theme('no-schema-theme');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.themes.customize', $theme))
            ->assertOk()
            ->assertSee('No customizable tokens defined');
    }

    public function test_customize_page_renders_token_groups_from_schema(): void
    {
        // The real bintan-prestige-luxury theme has a schema.
        $theme = Theme::create([
            'name'      => 'Bintan Prestige Luxury',
            'slug'      => 'bintan-prestige-luxury',
            'directory' => 'themes/bintan-prestige-luxury',
            'is_active' => false,
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.themes.customize', $theme))
            ->assertOk()
            ->assertSee('Colors')
            ->assertSee('Typography')
            ->assertSee('Brand Gold')
            ->assertSee('--frontend-gold')
            ->assertSee('Heading Font');
    }

    // -------------------------------------------------------------------------
    // updateCustomization — saves tokens
    // -------------------------------------------------------------------------

    public function test_save_customization_stores_valid_tokens_in_db(): void
    {
        $theme = Theme::create([
            'name'      => 'Bintan Prestige Luxury',
            'slug'      => 'bintan-prestige-luxury',
            'directory' => 'themes/bintan-prestige-luxury',
            'is_active' => false,
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.themes.customization.update', $theme), [
                'tokens' => [
                    '--frontend-gold'  => '#C4A44A',
                    '--frontend-black' => '#0F0F0F',
                ],
            ])
            ->assertRedirect(route('admin.themes.customize', $theme))
            ->assertSessionHas('success');

        $theme->refresh();

        $this->assertSame('#C4A44A', $theme->customization['--frontend-gold']);
        $this->assertSame('#0F0F0F', $theme->customization['--frontend-black']);
    }

    public function test_save_customization_rejects_keys_not_in_schema(): void
    {
        $theme = Theme::create([
            'name'      => 'Bintan Prestige Luxury',
            'slug'      => 'bintan-prestige-luxury',
            'directory' => 'themes/bintan-prestige-luxury',
            'is_active' => false,
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.themes.customization.update', $theme), [
                'tokens' => [
                    '--frontend-gold'           => '#C4A44A',  // valid key
                    '--injected-unknown-var'    => '#FF0000',  // not in schema → rejected
                ],
            ]);

        $theme->refresh();

        $this->assertArrayHasKey('--frontend-gold', $theme->customization ?? []);
        $this->assertArrayNotHasKey('--injected-unknown-var', $theme->customization ?? []);
    }

    public function test_save_customization_strips_css_injection_characters_from_values(): void
    {
        $theme = Theme::create([
            'name'      => 'Bintan Prestige Luxury',
            'slug'      => 'bintan-prestige-luxury',
            'directory' => 'themes/bintan-prestige-luxury',
            'is_active' => false,
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.themes.customization.update', $theme), [
                'tokens' => [
                    '--frontend-gold' => '#B8924A}body{color:red',
                ],
            ]);

        $theme->refresh();

        $stored = $theme->customization['--frontend-gold'] ?? '';
        $this->assertStringNotContainsString('}', $stored);
        $this->assertStringNotContainsString('{', $stored);
    }

    public function test_save_customization_stores_null_when_all_fields_are_empty(): void
    {
        $theme = Theme::create([
            'name'      => 'Bintan Prestige Luxury',
            'slug'      => 'bintan-prestige-luxury',
            'directory' => 'themes/bintan-prestige-luxury',
            'is_active' => false,
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.themes.customization.update', $theme), [
                'tokens' => [],
            ]);

        $this->assertNull($theme->fresh()->customization);
    }

    // -------------------------------------------------------------------------
    // resetCustomization
    // -------------------------------------------------------------------------

    public function test_reset_customization_clears_stored_tokens(): void
    {
        $theme = Theme::create([
            'name'          => 'Bintan Prestige Luxury',
            'slug'          => 'bintan-prestige-luxury',
            'directory'     => 'themes/bintan-prestige-luxury',
            'is_active'     => false,
            'customization' => ['--frontend-gold' => '#C4A44A'],
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('admin.themes.customization.destroy', $theme))
            ->assertRedirect(route('admin.themes.customize', $theme))
            ->assertSessionHas('success');

        $this->assertNull($theme->fresh()->customization);
    }

    // -------------------------------------------------------------------------
    // ThemeService::getTokenOverrides
    // -------------------------------------------------------------------------

    public function test_get_token_overrides_returns_empty_array_when_no_theme_is_active(): void
    {
        $this->assertSame([], app(ThemeService::class)->getTokenOverrides());
    }

    public function test_get_token_overrides_returns_stored_customization_of_active_theme(): void
    {
        Theme::create([
            'name'          => 'Custom Gold Theme',
            'slug'          => 'custom-gold',
            'directory'     => 'themes/custom-gold',
            'is_active'     => true,
            'customization' => ['--frontend-gold' => '#FFCC00'],
        ]);

        $overrides = app(ThemeService::class)->getTokenOverrides();

        $this->assertSame(['--frontend-gold' => '#FFCC00'], $overrides);
    }

    // -------------------------------------------------------------------------
    // Frontend token injection
    // -------------------------------------------------------------------------

    public function test_token_overrides_are_injected_into_frontend_head_when_active_theme_has_customization(): void
    {
        Theme::create([
            'name'          => 'Gold Override Theme',
            'slug'          => 'gold-override',
            'directory'     => 'themes/gold-override',
            'is_active'     => true,
            'customization' => ['--frontend-gold' => '#FFCC00'],
        ]);

        $page = Page::create([
            'title'  => 'Token Test Page',
            'slug'   => 'token-test-page',
            'status' => 'published',
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('--frontend-gold', false)
            ->assertSee('#FFCC00', false);
    }

    public function test_no_token_override_style_block_when_no_theme_is_active(): void
    {
        $page = Page::create([
            'title'  => 'No Token Page',
            'slug'   => 'no-token-page',
            'status' => 'published',
        ]);

        $html = $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->getContent();

        // The inline :root block for theme overrides must not appear when no theme is active.
        $this->assertStringNotContainsString('theme-token-override', $html);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function theme(string $slug, bool $active = false): Theme
    {
        return Theme::create([
            'name'      => ucwords(str_replace('-', ' ', $slug)),
            'slug'      => $slug,
            'directory' => "themes/{$slug}",
            'is_active' => $active,
        ]);
    }
}
