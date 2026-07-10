<?php

namespace Tests\Feature\Phase7;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Taxonomy;
use App\Models\Term;
use App\Services\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * B7 — Menus & Terms localized. MenuItem labels + Term name/description translate
 * per locale via the B1 sidecar. Menu structure is shared (A0 §3.5 Option A).
 */
class B7MenusAndTermsLocalizedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // menu tree is cached per locale
    }

    private function makeMenu(string $location = 'header'): Menu
    {
        return Menu::create(['name' => 'Header', 'location' => $location, 'is_active' => true]);
    }

    private function makeItem(Menu $menu, string $label = 'Home', string $url = '/'): MenuItem
    {
        return MenuItem::create([
            'menu_id' => $menu->id, 'parent_id' => null,
            'label' => $label, 'link_type' => 'url', 'url' => $url,
            'target' => '_self', 'is_active' => true, 'sort_order' => 0,
        ]);
    }

    private function taxonomy(): Taxonomy
    {
        return Taxonomy::create([
            'slug' => 'topic', 'label_singular' => 'Topic', 'label_plural' => 'Topics',
            'is_hierarchical' => false, 'is_active' => true,
        ]);
    }

    // ------------------------------------------------------------- MenuItem model

    public function test_menu_item_label_accessor_localizes(): void
    {
        $menu = $this->makeMenu();
        $item = $this->makeItem($menu, 'Home');
        $item->setTranslation('label', 'id', 'Beranda');

        app()->setLocale('en');
        $this->assertSame('Home', $item->fresh()->label);

        app()->setLocale('id');
        $this->assertSame('Beranda', $item->fresh()->label);
    }

    public function test_menu_item_falls_back_to_base_when_untranslated(): void
    {
        $item = $this->makeItem($this->makeMenu(), 'Home');

        app()->setLocale('id');
        $this->assertSame('Home', $item->fresh()->label);
    }

    // ------------------------------------------------------------- MenuService tree

    public function test_menu_service_tree_returns_localized_labels(): void
    {
        $menu = $this->makeMenu();
        $item = $this->makeItem($menu, 'Home', '/');
        $item->setTranslation('label', 'id', 'Beranda');

        app()->setLocale('id');
        $tree = app(MenuService::class)->tree('header');
        $this->assertSame('Beranda', $tree[0]['label']);

        // Fresh service in EN reads the base column.
        app(MenuService::class)->forget('header');
        app()->setLocale('en');
        $tree = app(MenuService::class)->tree('header');
        $this->assertSame('Home', $tree[0]['label']);
    }

    public function test_menu_service_cache_is_per_locale(): void
    {
        $menu = $this->makeMenu();
        $item = $this->makeItem($menu, 'Home', '/');
        $item->setTranslation('label', 'id', 'Beranda');

        app()->setLocale('id');
        $id = app(MenuService::class)->tree('header');
        app()->setLocale('en');
        $en = app(MenuService::class)->tree('header');

        $this->assertNotSame($id[0]['label'], $en[0]['label']);
        $this->assertTrue(Cache::has(MenuService::CACHE_PREFIX.'header.id'));
        $this->assertTrue(Cache::has(MenuService::CACHE_PREFIX.'header.en'));
    }

    // ------------------------------------------------------------- Term model

    public function test_term_localizes_name_and_description(): void
    {
        $tax = $this->taxonomy();
        $term = Term::create([
            'taxonomy_id' => $tax->id, 'name' => 'News', 'slug' => 'news',
            'description' => 'Latest posts.',
        ]);
        $term->setTranslation('name', 'id', 'Berita');
        $term->setTranslation('description', 'id', 'Postingan terkini.');
        $term = $term->fresh();

        app()->setLocale('id');
        $this->assertSame('Berita', $term->name);
        $this->assertSame('Postingan terkini.', $term->description);

        app()->setLocale('en');
        $this->assertSame('News', $term->name);
    }
}
