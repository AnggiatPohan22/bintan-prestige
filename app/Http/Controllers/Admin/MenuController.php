<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::query()
            ->withCount('items')
            ->with(['rootItems:id,menu_id,parent_id,label,sort_order'])
            ->orderBy('id')
            ->get();

        return view('backend.menus.index', [
            'menus' => $menus,
        ]);
    }

    public function edit(Menu $menu)
    {
        $menu->load(['rootItems' => fn ($query) => $query->with([
            'linkable',
            'children' => fn ($child) => $child->with('linkable'),
        ])]);

        return view('backend.menus.edit', [
            'menu' => $menu,
            'linkTypes' => MenuItem::LINK_TYPES,
            'pages' => Page::published()->orderBy('title')->get(['id', 'title', 'slug']),
            'products' => Product::publiclyVisible()->orderBy('name')->get(['id', 'name', 'slug']),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'destinations' => Destination::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'parentOptions' => $menu->rootItems,
        ]);
    }
}
