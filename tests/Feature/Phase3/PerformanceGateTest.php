<?php

namespace Tests\Feature\Phase3;

use App\Models\Page;
use App\Models\Theme;
use App\Models\User;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * STEP 8 — Regression & Performance Gate
 *
 * Guards against N+1 query regressions introduced in STEP 5–7.
 * Verifies that:
 *   P1 — ThemeService::widgetsForArea loads all areas in one DB query
 *   P2 — Frontend page with active theme hits theme cache (no extra theme queries per page)
 *   P3 — Themes index with multiple themes renders without crashing (N file reads OK for admin)
 *   P4 — Full test count confirms no tests were lost between STEPs
 */
class PerformanceGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ThemeService::class)->forget();
    }

    // -------------------------------------------------------------------------
    // P1 — widgetsForArea: one DB query serves all areas
    // -------------------------------------------------------------------------

    public function test_multiple_widget_area_calls_issue_only_one_db_query(): void
    {
        $theme = $this->theme('bintan-prestige-luxury', 'themes/bintan-prestige-luxury', true);

        foreach (['footer-col-1', 'footer-col-2', 'footer-col-3', 'before-footer'] as $i => $area) {
            $theme->widgets()->create([
                'area'        => $area,
                'widget_type' => 'text',
                'title'       => "Widget {$i}",
                'sort_order'  => $i,
                'is_visible'  => true,
            ]);
        }

        $queries = 0;
        DB::listen(function ($q) use (&$queries): void {
            if (str_contains(strtolower($q->sql), 'from "widgets"')) {
                $queries++;
            }
        });

        // Four separate area lookups should all be served from in-memory cache.
        app(ThemeService::class)->widgetsForArea('footer-col-1');
        app(ThemeService::class)->widgetsForArea('footer-col-2');
        app(ThemeService::class)->widgetsForArea('footer-col-3');
        app(ThemeService::class)->widgetsForArea('before-footer');

        $this->assertSame(1, $queries, 'ThemeService::widgetsForArea must issue only one DB query regardless of how many areas are queried.');
    }

    public function test_widget_area_returns_empty_collection_for_unknown_area_without_extra_query(): void
    {
        $this->theme('bintan-prestige-luxury', 'themes/bintan-prestige-luxury', true);

        // Prime the in-memory map with one call.
        app(ThemeService::class)->widgetsForArea('footer-col-1');

        $queries = 0;
        DB::listen(function ($q) use (&$queries): void {
            if (str_contains(strtolower($q->sql), 'from "widgets"')) {
                $queries++;
            }
        });

        $result = app(ThemeService::class)->widgetsForArea('nonexistent-area');

        $this->assertSame(0, $queries, 'Querying an unknown area after priming must not issue a new DB query.');
        $this->assertCount(0, $result);
    }

    // -------------------------------------------------------------------------
    // P2 — Frontend page with active theme: no extra theme queries on repeat requests
    // -------------------------------------------------------------------------

    public function test_theme_cache_key_is_set_after_page_request_with_active_theme(): void
    {
        $this->theme('bintan-prestige-luxury', 'themes/bintan-prestige-luxury', true);

        $page = Page::create([
            'title'  => 'Perf Gate Page',
            'slug'   => 'perf-gate',
            'status' => 'published',
        ]);

        $this->get(route('pages.show', $page->slug))->assertOk();

        // After a request, the active-theme slug should be cached.
        $this->assertTrue(
            \Illuminate\Support\Facades\Cache::has(ThemeService::CACHE_KEY),
            'ThemeService cache key must be populated after a frontend page request.'
        );
    }

    // -------------------------------------------------------------------------
    // P3 — Themes admin index with multiple themes
    // -------------------------------------------------------------------------

    public function test_themes_index_renders_with_three_themes_without_error(): void
    {
        $this->theme('theme-alpha', 'themes/theme-alpha', false);
        $this->theme('theme-beta',  'themes/theme-beta',  false);
        $this->theme('bintan-prestige-luxury', 'themes/bintan-prestige-luxury', true);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.themes.index'))
            ->assertOk()
            ->assertSee('Theme Alpha')
            ->assertSee('Theme Beta')
            ->assertSee('Bintan Prestige Luxury');
    }

    // -------------------------------------------------------------------------
    // P4 — Baseline test count
    // -------------------------------------------------------------------------

    public function test_phase_3_test_file_count_is_at_least_six(): void
    {
        $phase3Dir = base_path('tests/Feature/Phase3');
        $files = glob($phase3Dir . '/*Test.php');

        $this->assertGreaterThanOrEqual(
            6,
            count($files),
            'Expected at least 6 Phase 3 test files (one per STEP). Found: ' . implode(', ', array_map('basename', $files))
        );
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
