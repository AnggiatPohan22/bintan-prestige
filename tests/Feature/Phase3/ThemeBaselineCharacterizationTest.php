<?php

namespace Tests\Feature\Phase3;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Services\GlobalSettingsService;
use App\Support\BrandColorSettings;
use App\Support\PageTemplateRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * STEP 0 — Phase 3 Audit & Safety Baseline
 *
 * These tests lock the five contracts that Phase 3 (Theme System) will extend.
 * Any Phase 3 change that breaks these tests has broken backward compatibility.
 *
 * Contracts covered:
 *   C1 — CSS variable name completeness (all 26 --frontend-* vars)
 *   C2 — BrandColorSettings field catalog (21 slugs, stable names, hex defaults)
 *   C3 — CSS var → slug mapping (DB value renders into correct var)
 *   C4 — PageTemplateRegistry structure (3 keys, stable view paths, null fallback)
 *   C5 — GlobalSettingsService cache key constants (no Phase 3 key collision)
 *   C6 — Frontend layout assembly (brand colors + header + footer on every public page)
 */
class ThemeBaselineCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // -------------------------------------------------------------------------
    // C1 — CSS variable name completeness
    // -------------------------------------------------------------------------

    public function test_site_brand_colors_partial_renders_all_26_css_variable_names(): void
    {
        $allDefaults = collect(BrandColorSettings::fields())
            ->mapWithKeys(fn (array $field) => [$field['slug'] => $field['default']])
            ->all();

        $rendered = (string) $this->view('partials.site-brand-colors', [
            'brandColors' => $allDefaults,
        ]);

        foreach ($this->expectedCssVarNames() as $varName) {
            $this->assertStringContainsString(
                $varName . ':',
                $rendered,
                "CSS variable {$varName} is missing from partials.site-brand-colors. " .
                'Phase 3 customizer must preserve this variable name.'
            );
        }
    }

    public function test_expected_css_variable_count_is_26(): void
    {
        $this->assertCount(26, $this->expectedCssVarNames());
    }

    // -------------------------------------------------------------------------
    // C2 — BrandColorSettings field catalog
    // -------------------------------------------------------------------------

    public function test_brand_color_settings_exposes_exactly_21_fields(): void
    {
        $this->assertCount(21, BrandColorSettings::fields());
    }

    public function test_brand_color_settings_slugs_are_stable(): void
    {
        $actual = collect(BrandColorSettings::fields())
            ->pluck('slug')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([
            'button_cta_bg',
            'button_cta_text',
            'button_primary_bg',
            'button_primary_hover_bg',
            'button_primary_hover_text',
            'button_primary_text',
            'button_submit_bg',
            'button_submit_text',
            'palette_accent',
            'palette_primary',
            'palette_secondary',
            'surface_body',
            'surface_border',
            'surface_card',
            'surface_dark',
            'surface_soft',
            'text_body',
            'text_link',
            'text_muted',
            'text_on_dark',
            'text_title',
        ], $actual);
    }

    public function test_all_brand_color_defaults_are_valid_6_digit_hex(): void
    {
        foreach (BrandColorSettings::fields() as $field) {
            $this->assertMatchesRegularExpression(
                '/^#[0-9a-fA-F]{6}$/',
                $field['default'],
                "Brand color slug '{$field['slug']}' default '{$field['default']}' is not a valid 6-digit hex."
            );
        }
    }

    public function test_brand_color_group_constant_is_stable(): void
    {
        $this->assertSame('brand_colors', BrandColorSettings::GROUP);
    }

    // -------------------------------------------------------------------------
    // C3 — CSS var → slug mapping
    // -------------------------------------------------------------------------

    public function test_saved_palette_primary_renders_into_frontend_black_css_variable(): void
    {
        SiteSetting::create([
            'key'      => 'brand.palette.primary',
            'label'    => 'Primary',
            'value'    => '#aabbcc',
            'type'     => 'color',
            'group'    => BrandColorSettings::GROUP,
            'is_active' => true,
        ]);

        $brandColors = BrandColorSettings::valuesFromSettings(
            app(GlobalSettingsService::class)->settingsForGroup(BrandColorSettings::GROUP)
        );

        $rendered = (string) $this->view('partials.site-brand-colors', compact('brandColors'));

        $this->assertStringContainsString('--frontend-black: #aabbcc', $rendered);
    }

    public function test_saved_palette_secondary_renders_into_frontend_gold_css_variable(): void
    {
        SiteSetting::create([
            'key'      => 'brand.palette.secondary',
            'label'    => 'Secondary',
            'value'    => '#ddeeff',
            'type'     => 'color',
            'group'    => BrandColorSettings::GROUP,
            'is_active' => true,
        ]);

        $brandColors = BrandColorSettings::valuesFromSettings(
            app(GlobalSettingsService::class)->settingsForGroup(BrandColorSettings::GROUP)
        );

        $rendered = (string) $this->view('partials.site-brand-colors', compact('brandColors'));

        $this->assertStringContainsString('--frontend-gold: #ddeeff', $rendered);
    }

    // -------------------------------------------------------------------------
    // C4 — PageTemplateRegistry structure
    // -------------------------------------------------------------------------

    public function test_page_template_registry_exposes_exactly_three_keys(): void
    {
        $this->assertSame(['default', 'full-width', 'contained'], PageTemplateRegistry::keys());
    }

    public function test_page_template_registry_view_paths_are_stable(): void
    {
        $this->assertSame('frontend.templates.default', PageTemplateRegistry::viewFor('default'));
        $this->assertSame('frontend.templates.full-width', PageTemplateRegistry::viewFor('full-width'));
        $this->assertSame('frontend.templates.contained', PageTemplateRegistry::viewFor('contained'));
    }

    public function test_page_template_registry_falls_back_to_default_for_null_blank_and_unknown(): void
    {
        $this->assertSame('default', PageTemplateRegistry::keyFor(null));
        $this->assertSame('default', PageTemplateRegistry::keyFor(''));
        $this->assertSame('default', PageTemplateRegistry::keyFor('phase-3-theme-override'));
        $this->assertSame('frontend.templates.default', PageTemplateRegistry::viewFor(null));
    }

    public function test_all_three_template_view_files_exist_on_disk(): void
    {
        foreach (PageTemplateRegistry::keys() as $key) {
            $viewPath = PageTemplateRegistry::viewFor($key);
            $filePath = resource_path('views/' . str_replace('.', '/', $viewPath) . '.blade.php');
            $this->assertFileExists($filePath, "Template view file for key '{$key}' is missing: {$filePath}");
        }
    }

    // -------------------------------------------------------------------------
    // C5 — GlobalSettingsService cache key constants
    // -------------------------------------------------------------------------

    public function test_global_settings_service_cache_key_constants_are_stable(): void
    {
        $this->assertSame('global_settings.public.v1', GlobalSettingsService::SETTINGS_CACHE_KEY);
        $this->assertSame('global_assets.public.v1', GlobalSettingsService::ASSETS_CACHE_KEY);
        $this->assertSame(30, GlobalSettingsService::CACHE_TTL_MINUTES);
    }

    public function test_phase_3_theme_cache_keys_do_not_collide_with_global_settings_keys(): void
    {
        $reservedKeys = [
            GlobalSettingsService::SETTINGS_CACHE_KEY,
            GlobalSettingsService::ASSETS_CACHE_KEY,
        ];

        // Phase 3 will use keys prefixed with 'theme.' — assert they are distinct.
        $phase3Keys = [
            'theme.active.v1',
            'theme.customizations.v1',
            'theme.widgets.v1',
        ];

        foreach ($phase3Keys as $phase3Key) {
            $this->assertNotContains(
                $phase3Key,
                $reservedKeys,
                "Proposed Phase 3 cache key '{$phase3Key}' collides with an existing GlobalSettingsService key."
            );
        }
    }

    // -------------------------------------------------------------------------
    // C6 — Frontend layout assembly
    // -------------------------------------------------------------------------

    public function test_published_page_response_includes_css_root_block_header_and_footer(): void
    {
        $page = Page::create([
            'title'  => 'Theme Baseline Page',
            'slug'   => 'theme-baseline-page',
            'status' => 'published',
        ]);

        $response = $this->get(route('pages.show', $page->slug));

        $response->assertOk();
        $response->assertSee(':root {', false);
        $response->assertSee('--frontend-black', false);
        $response->assertSee('<header', false);
        $response->assertSee('class="frontend-header', false);
        $response->assertSee('<footer', false);
    }

    public function test_frontend_layout_view_file_includes_site_brand_colors_and_theme_service_partials(): void
    {
        $layoutPath = resource_path('views/layouts/frontend.blade.php');
        $contents   = file_get_contents($layoutPath);

        // Brand colors partial is still static.
        $this->assertStringContainsString("@include('partials.site-brand-colors')", $contents);

        // Header and footer are now resolved via ThemeService (STEP 2).
        // When no theme is active, ThemeService falls back to the same default partials.
        $this->assertStringContainsString('ThemeService', $contents);
        $this->assertStringContainsString("resolvePartial('header')", $contents);
        $this->assertStringContainsString("resolvePartial('footer')", $contents);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @return string[] */
    private function expectedCssVarNames(): array
    {
        return [
            '--frontend-black',
            '--frontend-gold',
            '--frontend-brand-accent',
            '--frontend-white',
            '--frontend-gold-pale',
            '--frontend-border',
            '--frontend-charcoal',
            '--frontend-gray',
            '--frontend-gold-soft',
            '--frontend-text-title',
            '--frontend-text-body',
            '--frontend-text-muted',
            '--frontend-text-link',
            '--frontend-text-on-dark',
            '--frontend-surface-body',
            '--frontend-surface-card',
            '--frontend-surface-soft',
            '--frontend-surface-dark',
            '--frontend-button-primary-bg',
            '--frontend-button-primary-text',
            '--frontend-button-primary-hover-bg',
            '--frontend-button-primary-hover-text',
            '--frontend-button-cta-bg',
            '--frontend-button-cta-text',
            '--frontend-button-submit-bg',
            '--frontend-button-submit-text',
        ];
    }
}
