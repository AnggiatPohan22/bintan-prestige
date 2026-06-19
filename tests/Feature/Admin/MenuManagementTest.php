<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use App\Services\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_admin_routes_require_an_authenticated_admin(): void
    {
        $this->get(route('admin.menus.index'))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.menus.index'))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('admin.menus.index'))
            ->assertOk();
    }

    public function test_admin_can_create_url_and_page_menu_items(): void
    {
        $menu = $this->menu();
        $page = Page::create([
            'title' => 'About Us',
            'slug' => 'about-us',
            'status' => 'published',
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.menu-items.store', $menu), [
            'label' => 'External Partner',
            'link_type' => 'url',
            'url' => 'https://example.com/partner',
            'target' => '_blank',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.menu-items.store', $menu), [
            'label' => 'About Us',
            'link_type' => 'page',
            'linkable_id' => $page->id,
            'target' => '_self',
        ])->assertRedirect();

        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'label' => 'External Partner',
            'link_type' => 'url',
            'url' => 'https://example.com/partner',
            'target' => '_blank',
        ]);
        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'label' => 'About Us',
            'link_type' => 'page',
            'linkable_type' => Page::class,
            'linkable_id' => $page->id,
        ]);
    }

    public function test_parent_selection_is_limited_to_root_items_in_the_same_menu(): void
    {
        $menu = $this->menu();
        $otherMenu = $this->menu('Footer Menu', 'footer_quick');
        $foreignParent = $this->item($otherMenu, 'Foreign parent');

        $this->actingAs($this->admin())
            ->from(route('admin.menus.edit', $menu))
            ->post(route('admin.menu-items.store', $menu), [
                'label' => 'Candidate child',
                'link_type' => 'anchor',
                'url' => '#candidate',
                'parent_id' => $foreignParent->id,
            ])->assertRedirect(route('admin.menus.edit', $menu))
            ->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('menu_items', ['label' => 'Candidate child']);
    }

    public function test_hierarchy_rejects_grandchildren_self_parenting_cycles_and_footer_children(): void
    {
        $menu = $this->menu();
        $parent = $this->item($menu, 'Parent');
        $child = $this->item($menu, 'Child', '#child', 0, $parent->id);
        $otherRoot = $this->item($menu, 'Other root', '#other-root', 1);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.menu-items.store', $menu), [
                'label' => 'Grandchild',
                'link_type' => 'anchor',
                'url' => '#grandchild',
                'parent_id' => $child->id,
            ])->assertSessionHasErrors('parent_id');

        $this->actingAs($admin)
            ->put(route('admin.menu-items.update', [$menu, $parent]), [
                'label' => 'Parent',
                'link_type' => 'anchor',
                'url' => '#parent',
                'parent_id' => $parent->id,
            ])->assertSessionHasErrors('parent_id');

        $this->actingAs($admin)
            ->put(route('admin.menu-items.update', [$menu, $parent]), [
                'label' => 'Parent',
                'link_type' => 'anchor',
                'url' => '#parent',
                'parent_id' => $child->id,
            ])->assertSessionHasErrors('parent_id');

        $this->actingAs($admin)
            ->put(route('admin.menu-items.update', [$menu, $parent]), [
                'label' => 'Parent',
                'link_type' => 'anchor',
                'url' => '#parent',
                'parent_id' => $otherRoot->id,
            ])->assertSessionHasErrors('parent_id');

        $footer = $this->menu('Footer Menu', 'footer_quick');
        $footerParent = $this->item($footer, 'Footer parent');
        $this->actingAs($admin)
            ->post(route('admin.menu-items.store', $footer), [
                'label' => 'Footer child',
                'link_type' => 'anchor',
                'url' => '#footer-child',
                'parent_id' => $footerParent->id,
            ])->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('menu_items', ['label' => 'Grandchild']);
        $this->assertDatabaseMissing('menu_items', ['label' => 'Footer child']);
    }

    public function test_internal_targets_must_be_publicly_available(): void
    {
        $menu = $this->menu();
        $draftPage = Page::create([
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'status' => 'draft',
        ]);
        $inactiveCategory = Category::factory()->create([
            'slug' => 'inactive-menu-category',
            'is_active' => false,
        ]);
        $inactiveDestination = Destination::factory()->create([
            'slug' => 'inactive-menu-destination',
            'is_active' => false,
        ]);
        $activeCategory = Category::factory()->create([
            'slug' => 'active-menu-category',
            'is_active' => true,
        ]);
        $activeDestination = Destination::factory()->create([
            'slug' => 'active-menu-destination',
            'is_active' => true,
        ]);
        $draftProduct = Product::factory()->create([
            'category_id' => $activeCategory->id,
            'destination_id' => $activeDestination->id,
            'status' => 'draft',
        ]);
        $admin = $this->admin();

        foreach ([
            ['page', $draftPage->id],
            ['product', $draftProduct->id],
            ['category', $inactiveCategory->id],
            ['destination', $inactiveDestination->id],
        ] as [$type, $targetId]) {
            $this->actingAs($admin)
                ->post(route('admin.menu-items.store', $menu), [
                    'label' => 'Unavailable '.$type,
                    'link_type' => $type,
                    'linkable_id' => $targetId,
                ])->assertSessionHasErrors('linkable_id');
        }

        $this->assertDatabaseCount('menu_items', 0);
    }

    public function test_cross_menu_item_mutations_return_not_found(): void
    {
        $routeMenu = $this->menu();
        $ownerMenu = $this->menu('Footer Menu', 'footer_quick');
        $foreignItem = $this->item($ownerMenu, 'Footer item');

        $this->actingAs($this->admin())
            ->put(route('admin.menu-items.update', [$routeMenu, $foreignItem]), [
                'label' => 'Mutated item',
                'link_type' => 'anchor',
                'url' => '#mutated',
            ])->assertNotFound();

        $this->assertSame('Footer item', $foreignItem->fresh()->label);
    }

    public function test_menu_tree_normalizes_active_items_and_cache_is_invalidated_after_write(): void
    {
        $menu = $this->menu();
        $parent = $this->item($menu, 'Packages', '/products', 0);
        $this->item($menu, 'Private Trips', '#private', 0, $parent->id);
        $inactive = $this->item($menu, 'Hidden Link', '#hidden', 1);
        $inactive->update(['is_active' => false]);

        $service = app(MenuService::class);
        $initial = $service->tree('header');

        $this->assertSame('Packages', $initial[0]['label']);
        $this->assertSame('/products', $initial[0]['url']);
        $this->assertSame('Private Trips', $initial[0]['children'][0]['label']);
        $this->assertCount(1, $initial);

        $this->actingAs($this->admin())->post(route('admin.menu-items.store', $menu), [
            'label' => 'Contact',
            'link_type' => 'anchor',
            'url' => '#contact',
        ])->assertRedirect();

        $refreshed = $service->tree('header');
        $this->assertSame(['Packages', 'Contact'], array_column($refreshed, 'label'));
    }

    public function test_target_status_changes_invalidate_cache_and_remove_all_stale_internal_links(): void
    {
        $menu = $this->menu();
        $page = Page::create([
            'title' => 'Published Page',
            'slug' => 'published-page',
            'status' => 'published',
        ]);
        $category = Category::factory()->create([
            'slug' => 'cached-menu-category',
            'is_active' => true,
        ]);
        $destination = Destination::factory()->create([
            'slug' => 'cached-menu-destination',
            'is_active' => true,
        ]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'destination_id' => $destination->id,
            'status' => 'published',
        ]);

        foreach ([
            ['Published Page', 'page', Page::class, $page->id],
            ['Published Product', 'product', Product::class, $product->id],
            ['Active Category', 'category', Category::class, $category->id],
            ['Active Destination', 'destination', Destination::class, $destination->id],
        ] as $sortOrder => [$label, $linkType, $linkableType, $linkableId]) {
            MenuItem::create([
                'menu_id' => $menu->id,
                'label' => $label,
                'link_type' => $linkType,
                'linkable_type' => $linkableType,
                'linkable_id' => $linkableId,
                'target' => '_self',
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]);
        }

        $service = app(MenuService::class);

        $this->assertSame([
            'Published Page',
            'Published Product',
            'Active Category',
            'Active Destination',
        ], array_column($service->tree('header'), 'label'));

        $page->update(['status' => 'draft']);
        $this->assertSame([
            'Published Product',
            'Active Category',
            'Active Destination',
        ], array_column($service->tree('header'), 'label'));

        $product->update(['status' => 'draft']);
        $this->assertSame([
            'Active Category',
            'Active Destination',
        ], array_column($service->tree('header'), 'label'));

        $category->update(['is_active' => false]);
        $this->assertSame([
            'Active Destination',
        ], array_column($service->tree('header'), 'label'));

        $destination->update(['is_active' => false]);
        $this->assertSame([], $service->tree('header'));
    }

    public function test_page_menu_link_follows_slug_changes_and_returns_after_republishing(): void
    {
        $menu = $this->menu();
        $page = Page::create([
            'title' => 'Workflow Page',
            'slug' => 'workflow-page',
            'status' => 'published',
        ]);
        MenuItem::create([
            'menu_id' => $menu->id,
            'label' => 'Workflow Page',
            'link_type' => 'page',
            'linkable_type' => Page::class,
            'linkable_id' => $page->id,
            'target' => '_self',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $service = app(MenuService::class);

        $this->assertSame('/pages/workflow-page', $service->tree('header')[0]['url']);

        $page->update(['slug' => 'workflow-page-updated']);
        $this->assertSame('/pages/workflow-page-updated', $service->tree('header')[0]['url']);

        $page->update(['status' => 'draft']);
        $this->assertSame([], $service->tree('header'));

        $page->update(['status' => 'published']);
        $this->assertSame('/pages/workflow-page-updated', $service->tree('header')[0]['url']);
    }

    public function test_reorder_persists_complete_sibling_groups_and_rejects_foreign_items(): void
    {
        $menu = $this->menu();
        $first = $this->item($menu, 'First', '#first', 0);
        $second = $this->item($menu, 'Second', '#second', 1);
        $otherMenu = $this->menu('Footer Menu', 'footer_quick');
        $foreign = $this->item($otherMenu, 'Foreign', '#foreign', 7);

        $admin = $this->admin();
        $this->actingAs($admin)->postJson(route('admin.menu-items.reorder', $menu), [
            'ids' => [$second->id, $first->id],
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);

        $this->actingAs($admin)->postJson(route('admin.menu-items.reorder', $menu), [
            'ids' => [$second->id, $first->id, $foreign->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('ids');

        $this->assertSame(7, $foreign->fresh()->sort_order);
    }

    public function test_reorder_rejects_partial_duplicate_and_mixed_parent_groups(): void
    {
        $menu = $this->menu();
        $first = $this->item($menu, 'First', '#first', 0);
        $second = $this->item($menu, 'Second', '#second', 1);
        $child = $this->item($menu, 'Child', '#child', 0, $first->id);
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('admin.menu-items.reorder', $menu), [
            'ids' => [$first->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('ids');

        $this->actingAs($admin)->postJson(route('admin.menu-items.reorder', $menu), [
            'ids' => [$first->id, $first->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('ids.1');

        $this->actingAs($admin)->postJson(route('admin.menu-items.reorder', $menu), [
            'ids' => [$first->id, $child->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('ids');

        $this->assertSame(0, $first->fresh()->sort_order);
        $this->assertSame(1, $second->fresh()->sort_order);
        $this->assertSame(0, $child->fresh()->sort_order);
    }

    public function test_moving_and_deleting_items_keeps_each_sibling_order_dense(): void
    {
        $menu = $this->menu();
        $first = $this->item($menu, 'First', '#first', 0);
        $second = $this->item($menu, 'Second', '#second', 4);
        $parent = $this->item($menu, 'Parent', '#parent', 8);
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.menu-items.update', [$menu, $second]), [
            'label' => 'Second',
            'link_type' => 'anchor',
            'url' => '#second',
            'parent_id' => $parent->id,
        ])->assertRedirect();

        $this->assertSame(0, $first->fresh()->sort_order);
        $this->assertSame(1, $parent->fresh()->sort_order);
        $this->assertSame(0, $second->fresh()->sort_order);

        $this->actingAs($admin)->delete(route('admin.menu-items.destroy', [$menu, $first]))
            ->assertRedirect();

        $this->assertSame(0, $parent->fresh()->sort_order);
    }

    public function test_bulk_menu_resolution_uses_one_cold_query_and_then_serves_cache_hits(): void
    {
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $service = app(MenuService::class);
        $coldTrees = $service->trees();

        $this->assertSame(MenuService::LOCATIONS, array_keys($coldTrees));
        $this->assertSame(1, $this->countMenuTableQueries());

        DB::flushQueryLog();

        $warmTrees = $service->trees();

        $this->assertSame($coldTrees, $warmTrees);
        $this->assertSame(0, $this->countMenuTableQueries());
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function menu(string $name = 'Header Menu', string $location = 'header'): Menu
    {
        return Menu::create([
            'name' => $name,
            'location' => $location,
            'is_active' => true,
        ]);
    }

    private function item(
        Menu $menu,
        string $label,
        string $url = '#link',
        int $sortOrder = 0,
        ?int $parentId = null,
    ): MenuItem {
        return MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $parentId,
            'label' => $label,
            'link_type' => str_starts_with($url, '#') ? 'anchor' : 'url',
            'url' => $url,
            'target' => '_self',
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }

    private function countMenuTableQueries(): int
    {
        return collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains($query['query'], 'from "menus"'))
            ->count();
    }
}
