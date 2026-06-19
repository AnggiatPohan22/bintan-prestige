<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMenuItemRequest;
use App\Http\Requests\Admin\UpdateMenuItemRequest;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use App\Services\MenuService;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    private const LINKABLE_MAP = [
        'page' => Page::class,
        'product' => Product::class,
        'category' => Category::class,
        'destination' => Destination::class,
    ];

    public function __construct(
        protected MenuService $menuService,
    ) {}

    public function store(StoreMenuItemRequest $request, Menu $menu)
    {
        $data = $this->payload($request, $menu);
        $data['is_active'] = true;
        $data['sort_order'] = $this->menuService->nextSortOrder($menu, $data['parent_id']);

        $menu->items()->create($data);
        $this->menuService->forget($menu->location);

        return back()->with('success', 'Menu item added.');
    }

    public function update(UpdateMenuItemRequest $request, Menu $menu, MenuItem $item)
    {
        abort_if($item->menu_id !== $menu->id, 404);

        $oldParentId = $item->parent_id;
        $data = $this->payload($request, $menu, $item);

        if ($oldParentId !== $data['parent_id']) {
            $data['sort_order'] = $this->menuService->nextSortOrder($menu, $data['parent_id']);
        }

        $item->update($data);
        $this->menuService->normalizeSiblingOrders($menu, [$oldParentId, $data['parent_id']]);
        $this->menuService->forget($menu->location);

        return back()->with('success', 'Menu item updated.');
    }

    public function destroy(Menu $menu, MenuItem $item)
    {
        abort_if($item->menu_id !== $menu->id, 404);

        $parentId = $item->parent_id;
        $item->delete(); // children cascade via FK
        $this->menuService->normalizeSiblingOrders($menu, [$parentId]);
        $this->menuService->forget($menu->location);

        return back()->with('success', 'Menu item deleted.');
    }

    public function reorder(Request $request, Menu $menu)
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $this->menuService->reorder($menu, $request->ids);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Menu order saved.');
    }

    public function toggleActive(Menu $menu, MenuItem $item)
    {
        abort_if($item->menu_id !== $menu->id, 404);

        $item->update(['is_active' => ! $item->is_active]);
        $this->menuService->forget($menu->location);

        return back()->with('success', $item->is_active ? 'Item is now visible.' : 'Item is now hidden.');
    }

    /**
     * Build the persisted attributes from the request, enforcing the
     * one-level depth rule and per-menu parent scoping.
     */
    private function payload(
        StoreMenuItemRequest|UpdateMenuItemRequest $request,
        Menu $menu,
        ?MenuItem $item = null,
    ): array {
        $linkType = $request->input('link_type');
        $isLinkable = array_key_exists($linkType, self::LINKABLE_MAP);

        // Parent must belong to this menu and be a root item (max one level).
        // An item that already has children cannot itself become a child.
        $parentId = $request->input('parent_id') ?: null;

        if ($parentId) {
            $itemHasChildren = $item && $item->children()->exists();
            $parent = $menu->items()->whereNull('parent_id')->find($parentId);
            $parentId = ($parent && (! $item || $parent->id !== $item->id) && ! $itemHasChildren)
                ? $parent->id
                : null;
        }

        return [
            'parent_id' => $parentId,
            'label' => $request->input('label'),
            'link_type' => $linkType,
            'linkable_type' => $isLinkable ? self::LINKABLE_MAP[$linkType] : null,
            'linkable_id' => $isLinkable ? $request->input('linkable_id') : null,
            'url' => in_array($linkType, ['url', 'anchor'], true) ? $request->input('url') : null,
            'target' => $request->input('target', '_self'),
        ];
    }
}
