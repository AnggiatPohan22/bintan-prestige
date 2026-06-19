<?php

namespace Tests\Feature\Phase3;

use App\Models\Page;
use App\Models\Theme;
use App\Models\Widget;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * STEP 5 — Theme Template Hierarchy
 *
 * Verifies that Blade files placed inside themes/{slug}/layouts/ and
 * themes/{slug}/partials/ are correctly picked up by ThemeService, and
 * that the Phase 2 fallbacks remain intact when no theme files are present.
 */
class ThemeTemplateHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ThemeService::class)->forget();
    }

    // -------------------------------------------------------------------------
    // Layout hierarchy
    // -------------------------------------------------------------------------

    public function test_resolve_layout_returns_theme_namespaced_view_when_layout_file_exists_on_disk(): void
    {
        $this->theme('bintan-prestige-luxury', 'themes/bintan-prestige-luxury', true);

        $result = app(ThemeService::class)->resolveLayout('default');

        $this->assertSame('theme-active::layouts.default', $result);
    }

    public function test_resolve_layout_falls_back_to_phase_2_when_theme_has_no_layout_file(): void
    {
        $this->theme('ghost-theme', 'themes/ghost-theme', true);

        $this->assertSame('frontend.templates.default', app(ThemeService::class)->resolveLayout('default'));
    }

    // -------------------------------------------------------------------------
    // Partial hierarchy — header & footer
    // -------------------------------------------------------------------------

    public function test_resolve_partial_returns_theme_namespaced_header_when_file_exists_on_disk(): void
    {
        $this->theme('bintan-prestige-luxury', 'themes/bintan-prestige-luxury', true);

        $this->assertSame('theme-active::partials.header', app(ThemeService::class)->resolvePartial('header'));
    }

    public function test_resolve_partial_returns_theme_namespaced_footer_when_file_exists_on_disk(): void
    {
        $this->theme('bintan-prestige-luxury', 'themes/bintan-prestige-luxury', true);

        $this->assertSame('theme-active::partials.footer', app(ThemeService::class)->resolvePartial('footer'));
    }

    public function test_resolve_partial_falls_back_to_phase_2_when_theme_has_no_partial_files(): void
    {
        $this->theme('ghost-theme', 'themes/ghost-theme', true);

        $this->assertSame('frontend.partials.header', app(ThemeService::class)->resolvePartial('header'));
        $this->assertSame('frontend.partials.footer', app(ThemeService::class)->resolvePartial('footer'));
    }

    // -------------------------------------------------------------------------
    // Widget areas rendered by theme footer
    // -------------------------------------------------------------------------

    public function test_widgets_for_footer_col_1_are_served_when_active_theme_has_widget(): void
    {
        $theme = $this->theme('bintan-prestige-luxury', 'themes/bintan-prestige-luxury', true);

        Widget::create([
            'theme_id'    => $theme->id,
            'area'        => 'footer-col-1',
            'widget_type' => 'text',
            'title'       => 'Footer Column 1 Widget',
            'data'        => ['heading' => 'Contact Us', 'content' => 'Widget body text.'],
            'is_visible'  => true,
            'sort_order'  => 1,
        ]);

        // Reset per-request widget cache so the new widget is picked up.
        app(ThemeService::class)->forget();

        $widgets = app(ThemeService::class)->widgetsForArea('footer-col-1');

        $this->assertCount(1, $widgets);
        $this->assertSame('footer-col-1', $widgets->first()->area);
        $this->assertSame('text', $widgets->first()->widget_type);
    }

    public function test_widget_areas_are_empty_when_no_widgets_are_configured(): void
    {
        $this->theme('bintan-prestige-luxury', 'themes/bintan-prestige-luxury', true);

        $this->assertCount(0, app(ThemeService::class)->widgetsForArea('footer-col-1'));
        $this->assertCount(0, app(ThemeService::class)->widgetsForArea('footer-col-2'));
        $this->assertCount(0, app(ThemeService::class)->widgetsForArea('footer-col-3'));
        $this->assertCount(0, app(ThemeService::class)->widgetsForArea('before-footer'));
    }

    // -------------------------------------------------------------------------
    // Full page render (integration)
    // -------------------------------------------------------------------------

    public function test_full_page_render_returns_200_with_bintan_prestige_luxury_theme_active(): void
    {
        $this->theme('bintan-prestige-luxury', 'themes/bintan-prestige-luxury', true);

        $page = Page::create([
            'title'  => 'Theme Hierarchy Test Page',
            'slug'   => 'theme-hierarchy-test',
            'status' => 'published',
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('<header', false)
            ->assertSee('<footer', false);
    }

    public function test_full_page_render_returns_200_without_active_theme_as_regression_guard(): void
    {
        $page = Page::create([
            'title'  => 'No-Theme Regression Page',
            'slug'   => 'no-theme-regression',
            'status' => 'published',
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('<header', false)
            ->assertSee('<footer', false);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function theme(string $slug, string $directory, bool $active): Theme
    {
        return Theme::create([
            'name'      => ucwords(str_replace('-', ' ', $slug)),
            'slug'      => $slug,
            'directory' => $directory,
            'is_active' => $active,
        ]);
    }
}
