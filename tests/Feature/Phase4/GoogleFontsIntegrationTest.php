<?php

namespace Tests\Feature\Phase4;

use App\Models\Theme;
use App\Services\GoogleFontsService;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleFontsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private GoogleFontsService $fontService;
    private ThemeService $themeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fontService  = $this->app->make(GoogleFontsService::class);
        $this->themeService = $this->app->make(ThemeService::class);
    }

    // =========================================================================
    // I1–I5 — GoogleFontsService
    // =========================================================================

    /** @test */
    public function test_get_font_list_returns_empty_when_api_key_not_configured(): void // I1
    {
        Config::set('services.google_fonts.key', null);

        $fonts = $this->fontService->getFontList();

        $this->assertSame([], $fonts);
    }

    /** @test */
    public function test_get_font_list_returns_family_names_from_api(): void // I2
    {
        Config::set('services.google_fonts.key', 'fake-api-key');

        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['family' => 'Roboto', 'category' => 'sans-serif'],
                    ['family' => 'Open Sans', 'category' => 'sans-serif'],
                    ['family' => 'Lato', 'category' => 'sans-serif'],
                ],
            ], 200),
        ]);

        // Forget any cached value so the HTTP call is made.
        $this->fontService->forget();

        $fonts = $this->fontService->getFontList();

        $this->assertSame(['Roboto', 'Open Sans', 'Lato'], $fonts);
    }

    /** @test */
    public function test_get_font_list_returns_empty_on_api_failure(): void // I3
    {
        Config::set('services.google_fonts.key', 'fake-api-key');

        Http::fake([
            'googleapis.com/*' => Http::response([], 500),
        ]);

        $this->fontService->forget();

        $fonts = $this->fontService->getFontList();

        $this->assertSame([], $fonts);
    }

    /** @test */
    public function test_get_font_list_caches_result(): void // I4
    {
        Config::set('services.google_fonts.key', 'fake-api-key');

        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [['family' => 'Roboto']],
            ], 200),
        ]);

        $this->fontService->forget();

        // First call — populates cache.
        $this->fontService->getFontList();

        $this->assertTrue(Cache::has(GoogleFontsService::CACHE_KEY));
    }

    /** @test */
    public function test_forget_clears_font_list_cache(): void // I5
    {
        Cache::put(GoogleFontsService::CACHE_KEY, ['Roboto', 'Lato'], 3600);

        $this->fontService->forget();

        $this->assertFalse(Cache::has(GoogleFontsService::CACHE_KEY));
    }

    // =========================================================================
    // I6–I8 — ThemeService::getGoogleFont()
    // =========================================================================

    /** @test */
    public function test_get_google_font_returns_null_when_no_active_theme(): void // I6
    {
        $this->assertNull($this->themeService->getGoogleFont());
    }

    /** @test */
    public function test_get_google_font_returns_null_when_not_set_in_customization(): void // I7
    {
        $this->createActiveLuxuryTheme(['customization' => ['--frontend-gold' => '#AABBCC']]);

        $this->assertNull($this->themeService->getGoogleFont());
    }

    /** @test */
    public function test_get_google_font_returns_stored_font_name(): void // I8
    {
        $this->createActiveLuxuryTheme([
            'customization' => ['_google_font' => 'Roboto'],
        ]);

        $this->assertSame('Roboto', $this->themeService->getGoogleFont());
    }

    // =========================================================================
    // I9–I10 — resolvedTokens() excludes _google_font
    // =========================================================================

    /** @test */
    public function test_resolved_tokens_excludes_google_font_metadata_key(): void // I9
    {
        $this->createActiveLuxuryTheme([
            'customization' => [
                '_google_font'     => 'Roboto',
                '--frontend-gold'  => '#FFAA00',
            ],
        ]);

        $tokens = $this->themeService->resolvedTokens();

        $this->assertArrayNotHasKey('_google_font', $tokens);
    }

    /** @test */
    public function test_resolved_tokens_still_includes_css_vars_when_google_font_stored(): void // I10
    {
        $this->createActiveLuxuryTheme([
            'customization' => [
                '_google_font'    => 'Roboto',
                '--frontend-gold' => '#FFAA00',
            ],
        ]);

        $tokens = $this->themeService->resolvedTokens();

        $this->assertArrayHasKey('--frontend-gold', $tokens);
        $this->assertSame('#FFAA00', $tokens['--frontend-gold']);
        $this->assertArrayHasKey('--bp-space-md', $tokens); // extended token default
    }

    // =========================================================================
    // Helper
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
}
