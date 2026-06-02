<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $heroBackgroundUrl = null;

        $featuredProducts = Product::query()
            ->published()
            ->frontendReady()
            ->where('is_featured', true)
            ->latest()
            ->take(6)
            ->get();

        $homeProducts = Product::query()
            ->published()
            ->frontendReady()
            ->latest()
            ->take(12)
            ->get();

        $homeProductCategories = $homeProducts
            ->pluck('category')
            ->filter()
            ->unique('id')
            ->values();

        $categories = Category::query()
            ->where('is_active', true)
            ->withCount([
                'products' => fn ($query) => $query->published()
            ])
            ->orderBy('name')
            ->get();

        $destinations = Destination::query()
            ->where('is_active', true)
            ->withCount([
                'products' => fn ($query) => $query->published()
            ])
            ->orderBy('name')
            ->get();

        return view(
            'frontend.home',
            compact(
                'featuredProducts',
                'homeProducts',
                'homeProductCategories',
                'categories',
                'destinations',
                'heroBackgroundUrl'
            )
        );
    }
}
