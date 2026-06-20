<?php

namespace Tests\Feature\Phase4;

use App\Facades\CmsHooks;
use App\Models\Theme;
use App\Services\ThemeService;
use App\Support\HookManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtendedDesignTokensTest extends TestCase
{
    use RefreshDatabase;

    private ThemeService $themeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->themeService = $this->app->make(ThemeService::class);
    }

    // =========================================================================
    // H1–H5 — resolvedTokens() behaviour
    // =========================================================================

    /** @test */
    public function test_resolved_tokens_returns_empty_when_no_active_theme(): void // H1
    {
        // No themes in DB at all.
        $this->assertSame([], $this->themeService->resolvedTokens());
    }

    /** @test */
    public function test_resolved_tokens_includes_schema_defaults_when_no_overrides_saved(): void // H2
    {
        $this->createActiveLuxuryTheme();

        $tokens = $this->themeService->resolvedTokens();

        // Spot-check a color default and an extended token default.
        $this->assertArrayHasKey('--frontend-gold', $tokens);
        $this->assertSame('#B8924A', $tokens['--frontend-gold']);

        $this->assertArrayHasKey('--bp-space-md', $tokens);
        $this->assertSame('16px', $tokens['--bp-space-md']);
    }

    /** @test */
    public function test_resolved_tokens_saved_override_wins_over_schema_default(): void // H3
    {
        $this->createActiveLuxuryTheme([
            'customization' => ['--bp-space-md' => '20px', '--frontend-gold' => '#FFAA00'],
        ]);

        $tokens = $this->themeService->resolvedTokens();

        $this->assertSame('20px',    $tokens['--bp-space-md']);
        $this->assertSame('#FFAA00', $tokens['--frontend-gold']);
    }

    /** @test */
    public function test_resolved_tokens_applies_theme_tokens_filter_hook(): void // H4
    {
        $this->createActiveLuxuryTheme();

        CmsHooks::addFilter('theme.tokens', function (array $tokens) {
            $tokens['--bp-space-md'] = '999px';
            return $tokens;
        });

        $tokens = $this->themeService->resolvedTokens();

        $this->assertSame('999px', $tokens['--bp-space-md']);
    }

    /** @test */
    public function test_theme_tokens_filter_receives_theme_as_second_argument(): void // H5
    {
        $theme = $this->createActiveLuxuryTheme();

        $receivedTheme = null;

        CmsHooks::addFilter('theme.tokens', function (array $tokens, $t) use (&$receivedTheme) {
            $receivedTheme = $t;
            return $tokens;
        });

        $this->themeService->resolvedTokens();

        $this->assertInstanceOf(Theme::class, $receivedTheme);
        $this->assertSame($theme->id, $receivedTheme->id);
    }

    // =========================================================================
    // H6–H9 — Luxury theme.json contains all extended token groups
    // =========================================================================

    /** @test */
    public function test_luxury_theme_json_contains_spacing_group(): void // H6
    {
        $schema = $this->getLuxurySchema();

        $this->assertArrayHasKey('spacing', $schema);

        $keys = collect($schema['spacing']['tokens'])->pluck('key')->all();

        $this->assertContains('--bp-space-xs',  $keys);
        $this->assertContains('--bp-space-sm',  $keys);
        $this->assertContains('--bp-space-md',  $keys);
        $this->assertContains('--bp-space-lg',  $keys);
        $this->assertContains('--bp-space-xl',  $keys);
        $this->assertContains('--bp-space-2xl', $keys);
    }

    /** @test */
    public function test_luxury_theme_json_contains_border_radius_group(): void // H7
    {
        $schema = $this->getLuxurySchema();

        $this->assertArrayHasKey('border_radius', $schema);

        $keys = collect($schema['border_radius']['tokens'])->pluck('key')->all();

        $this->assertContains('--bp-radius-sm',   $keys);
        $this->assertContains('--bp-radius-md',   $keys);
        $this->assertContains('--bp-radius-lg',   $keys);
        $this->assertContains('--bp-radius-full', $keys);
    }

    /** @test */
    public function test_luxury_theme_json_contains_box_shadow_group(): void // H8
    {
        $schema = $this->getLuxurySchema();

        $this->assertArrayHasKey('box_shadow', $schema);

        $keys = collect($schema['box_shadow']['tokens'])->pluck('key')->all();

        $this->assertContains('--bp-shadow-sm', $keys);
        $this->assertContains('--bp-shadow-md', $keys);
        $this->assertContains('--bp-shadow-lg', $keys);
        $this->assertContains('--bp-shadow-xl', $keys);
    }

    /** @test */
    public function test_luxury_theme_json_contains_font_sizes_group(): void // H9
    {
        $schema = $this->getLuxurySchema();

        $this->assertArrayHasKey('font_sizes', $schema);

        $keys = collect($schema['font_sizes']['tokens'])->pluck('key')->all();

        $this->assertContains('--bp-text-sm',   $keys);
        $this->assertContains('--bp-text-base', $keys);
        $this->assertContains('--bp-text-lg',   $keys);
        $this->assertContains('--bp-text-xl',   $keys);
        $this->assertContains('--bp-text-2xl',  $keys);
        $this->assertContains('--bp-text-3xl',  $keys);
        $this->assertContains('--bp-text-4xl',  $keys);
    }

    // =========================================================================
    // H10 — Plugin can inject a new token via the hook
    // =========================================================================

    /** @test */
    public function test_plugin_can_add_custom_token_via_theme_tokens_filter(): void // H10
    {
        $this->createActiveLuxuryTheme();

        CmsHooks::addFilter('theme.tokens', function (array $tokens) {
            $tokens['--plugin-custom-color'] = '#123456';
            return $tokens;
        });

        $tokens = $this->themeService->resolvedTokens();

        $this->assertArrayHasKey('--plugin-custom-color', $tokens);
        $this->assertSame('#123456', $tokens['--plugin-custom-color']);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function createActiveLuxuryTheme(array $overrides = []): Theme
    {
        return Theme::create(array_merge([
            'name'          => 'Bintan Prestige Luxury',
            'slug'          => 'bintan-prestige-luxury',
            'directory'     => 'themes/bintan-prestige-luxury',
            'version'       => '1.0.0',
            'is_active'     => true,
            'customization' => null,
        ], $overrides));
    }

    /** Read the luxury theme.json customization_schema directly from disk. */
    private function getLuxurySchema(): array
    {
        $path = base_path('themes/bintan-prestige-luxury/theme.json');
        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $data['customization_schema'] ?? [];
    }
}
