<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Faq;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\SiteAsset;

class HomeController extends Controller
{
    public function index()
    {
        $heroBackgroundUrl = null;

        $sections = PageSection::query()
            ->where('page_key', 'home')
            ->where('is_active', true)
            ->with('media')
            ->orderBy('sort_order')
            ->get()
            ->keyBy('section_key');

        $siteAssets = SiteAsset::query()
            ->where('is_active', true)
            ->get()
            ->keyBy('key');

        $faqs = Faq::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->take(6)
            ->get();

        $featuredProducts = Product::query()
            ->published()
            ->frontendReady()
            ->with(['category', 'destination', 'images'])
            ->where('is_featured', true)
            ->latest()
            ->take(6)
            ->get();

        $homeProducts = Product::query()
            ->published()
            ->frontendReady()
            ->with(['category', 'destination', 'images'])
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
                'heroBackgroundUrl',
                'sections',
                'siteAssets',
                'faqs'
            )
        );
    }
}
