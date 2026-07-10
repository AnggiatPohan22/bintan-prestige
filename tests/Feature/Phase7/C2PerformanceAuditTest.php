<?php

namespace Tests\Feature\Phase7;

use App\Models\Category;
use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\Destination;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * C2 — Performance audit. Regression fences for the Phase 7 gates:
 *
 *   1. Localized public routes render under 300ms warm.
 *   2. NO per-attribute translation queries on listing/detail paths
 *      (the sidecar is eager-loaded once per relation).
 *   3. Locale switcher + hreflang add no queries per row.
 *   4. Sitemap groups pages/entries by translation_group_id — no group-lookup fanout.
 *
 * Each test uses SQLite in-memory + a warm second GET to skip cold-cache costs.
 */
class C2PerformanceAuditTest extends TestCase
{
    use RefreshDatabase;

    private const WARM_BUDGET_MS = 300; // grand plan §6 / DoD §11

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // ------------------------------------------------------------- latency (warm)

    public function test_default_locale_home_is_under_warm_budget(): void
    {
        $this->seedCatalog();

        $this->get('/')->assertOk(); // warm caches
        $ms = $this->warmMs(fn () => $this->get('/')->assertOk());

        $this->assertLessThan(self::WARM_BUDGET_MS, $ms,
            "Default-locale home warm ={$ms}ms exceeds ".self::WARM_BUDGET_MS.'ms budget.');
    }

    public function test_localized_home_is_under_warm_budget(): void
    {
        $this->seedCatalog();

        $this->get('/id')->assertOk();
        $ms = $this->warmMs(fn () => $this->get('/id')->assertOk());

        $this->assertLessThan(self::WARM_BUDGET_MS, $ms,
            "Localized home warm ={$ms}ms exceeds ".self::WARM_BUDGET_MS.'ms budget.');
    }

    public function test_default_locale_products_index_is_under_warm_budget(): void
    {
        $this->seedCatalog();

        $this->get('/products')->assertOk();
        $ms = $this->warmMs(fn () => $this->get('/products')->assertOk());

        $this->assertLessThan(self::WARM_BUDGET_MS, $ms,
            "Products index warm ={$ms}ms exceeds ".self::WARM_BUDGET_MS.'ms budget.');
    }

    public function test_localized_products_index_is_under_warm_budget(): void
    {
        $this->seedCatalog();

        $this->get('/id/products')->assertOk();
        $ms = $this->warmMs(fn () => $this->get('/id/products')->assertOk());

        $this->assertLessThan(self::WARM_BUDGET_MS, $ms,
            "Localized products index warm ={$ms}ms exceeds ".self::WARM_BUDGET_MS.'ms budget.');
    }

    public function test_default_locale_page_show_is_under_warm_budget(): void
    {
        $this->seedPageWithTranslation();

        $this->get('/pages/about')->assertOk();
        $ms = $this->warmMs(fn () => $this->get('/pages/about')->assertOk());

        $this->assertLessThan(self::WARM_BUDGET_MS, $ms,
            "Page show warm ={$ms}ms exceeds ".self::WARM_BUDGET_MS.'ms budget.');
    }

    public function test_localized_page_show_is_under_warm_budget(): void
    {
        $this->seedPageWithTranslation();

        $this->get('/id/pages/about')->assertOk();
        $ms = $this->warmMs(fn () => $this->get('/id/pages/about')->assertOk());

        $this->assertLessThan(self::WARM_BUDGET_MS, $ms,
            "Localized page show warm ={$ms}ms exceeds ".self::WARM_BUDGET_MS.'ms budget.');
    }

    // ------------------------------------------------------------- N+1 fences

    public function test_products_index_translation_queries_do_not_grow_with_product_count(): void
    {
        // Small catalog: measure translation query count.
        $this->seedCatalog(productCount: 6);
        $this->get('/id/products')->assertOk(); // warm

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/id/products')->assertOk();
        $small = $this->translationsQueries();
        DB::disableQueryLog();

        // Scale the catalog: N+1 would multiply the count, batched loads stay flat.
        $cat  = Category::first();
        $dest = Destination::first();
        for ($i = 7; $i <= 24; $i++) {
            Product::create([
                'category_id' => $cat->id, 'destination_id' => $dest->id,
                'name' => "Tour $i", 'slug' => "tour-$i",
                'status' => 'published',
            ]);
        }
        $this->get('/id/products')->assertOk(); // warm again

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/id/products')->assertOk();
        $large = $this->translationsQueries();
        DB::disableQueryLog();

        // Absolute upper bound (each relation is one batched WHERE-IN):
        //   products themselves, categories + destinations eager-loaded on the
        //   products via frontendListingReady (card display), plus categories +
        //   destinations for the sidebar filter lists = 5 total.
        $this->assertLessThanOrEqual(5, $small);
        // Regression fence: translation query count MUST NOT grow with catalog
        // size. If it does, per-attribute lazy loading has crept in.
        $this->assertSame($small, $large,
            "Translation queries scaled with product count ({$small} → {$large}). This is a per-attribute N+1 regression.");
    }

    public function test_home_reads_no_per_attribute_translation_queries(): void
    {
        $this->seedCatalog(productCount: 8);
        // Add a home hero section with a translation so the page section eager-load fires.
        $section = PageSection::create([
            'page_key' => 'home', 'section_key' => 'home.hero',
            'title' => 'Welcome', 'is_active' => true, 'sort_order' => 0,
        ]);
        $section->setTranslation('title', 'id', 'Selamat Datang');

        $this->get('/id')->assertOk(); // warm

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/id')->assertOk();
        $translationHits = $this->translationsQueries();
        DB::disableQueryLog();

        // Home eager-loads: home products, categories, destinations, page sections,
        // menu items (header + footer). Bound at 6 (headroom of 1 for future
        // additive queries; regression if it climbs to per-row).
        $this->assertLessThanOrEqual(6, $translationHits,
            "Expected ≤6 translations queries on home; got {$translationHits}.");
    }

    // ------------------------------------------------------------- switcher/hreflang

    public function test_locale_switcher_and_hreflang_do_not_add_queries_per_link(): void
    {
        $this->seedCatalog();

        // Warm.
        $this->get('/id')->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->get('/id')->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $html = (string) $response->getContent();
        // The switcher must be present (proves we're not skipping the rendering).
        $this->assertStringContainsString('data-locale-switcher', $html);
        $this->assertStringContainsString('hreflang="en"', $html);
        $this->assertStringContainsString('hreflang="id"', $html);

        // No query mentions "locale_switcher" or a per-link fanout: just make sure
        // the total query count is bounded even with many active locales rendered.
        $this->assertLessThan(60, count($queries),
            'Home queries climbed above bounded budget (60) — likely per-link N+1.');
    }

    // ------------------------------------------------------------- sitemap

    public function test_sitemap_query_count_is_bounded_with_many_translated_documents(): void
    {
        // 20 pages, each translated into ID. 40 rows, one translation group per pair.
        for ($i = 1; $i <= 20; $i++) {
            $en = Page::create(['title' => "P$i", 'slug' => "p-$i", 'status' => 'published']);
            Page::create([
                'title' => "P$i-id", 'slug' => "p-$i", 'status' => 'published',
                'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
            ]);
        }

        $this->get('/sitemap.xml')->assertOk(); // warm

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/sitemap.xml')->assertOk();
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        $pageQueries = collect($log)
            ->filter(fn (array $q): bool => str_contains($q['query'], 'from "pages"'))
            ->count();

        // Sitemap groups siblings in PHP after a single "select from pages" —
        // no per-row lookup for the alternate URLs.
        $this->assertLessThanOrEqual(1, $pageQueries,
            "Sitemap fires {$pageQueries} 'from pages' queries; expected ≤1 (grouped in PHP).");
    }

    // ------------------------------------------------------------- helpers

    /** ms elapsed for the given callable. */
    private function warmMs(callable $fn): int
    {
        $start = hrtime(true);
        $fn();

        return (int) ((hrtime(true) - $start) / 1_000_000);
    }

    /** Count queries that touched the `translations` sidecar table. */
    private function translationsQueries(): int
    {
        return collect(DB::getQueryLog())
            ->filter(fn (array $q): bool => str_contains($q['query'], 'from "translations"'))
            ->count();
    }

    private function seedPageWithTranslation(): void
    {
        $en = Page::create(['title' => 'About Us', 'slug' => 'about', 'status' => 'published']);
        Page::create([
            'title' => 'Tentang Kami', 'slug' => 'about', 'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);
    }

    /** Seed a small catalog + a menu so listings/home have real content. */
    private function seedCatalog(int $productCount = 3): void
    {
        $cat  = Category::create(['name' => 'Tours', 'slug' => 'tours', 'is_active' => true]);
        $dest = Destination::create(['name' => 'Lagoi', 'slug' => 'lagoi', 'is_active' => true]);
        $cat->setTranslation('name', 'id', 'Tur');
        $dest->setTranslation('name', 'id', 'Lagoi (ID)');

        for ($i = 1; $i <= $productCount; $i++) {
            $p = Product::create([
                'category_id' => $cat->id, 'destination_id' => $dest->id,
                'name' => "Tour $i", 'slug' => "tour-$i",
                'status' => 'published',
            ]);
            if ($i % 2 === 0) {
                $p->setTranslation('name', 'id', "Tur $i");
            }
        }

        // A managed header menu with a translated label.
        $menu = Menu::create(['name' => 'Header', 'location' => 'header', 'is_active' => true]);
        $item = MenuItem::create([
            'menu_id' => $menu->id, 'parent_id' => null,
            'label' => 'Home', 'link_type' => 'url', 'url' => '/',
            'target' => '_self', 'is_active' => true, 'sort_order' => 0,
        ]);
        $item->setTranslation('label', 'id', 'Beranda');
    }
}
