<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Destination;
use App\Models\PageSection;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function index()
    {
        $heroBackgroundUrl = null;
        $heroSettings = [
            'section_id' => 'frontend-hero-section',
            'media_id' => 'frontend-hero-media',
            'label' => 'Luxury Bintan Travel',
            'title' => 'BINTAN PRESTIGE',
            'subtitle' => 'Private tours, island transfers, and curated experiences designed for a smoother premium escape.',
            'animation' => 'ken-burns',
            'slide_duration' => 6500,
            'image_fit' => 'cover',
            'image_position' => 'center center',
            'overlay_opacity' => 0.72,
            'text_alignment' => 'left',
        ];
        $heroSlides = [];

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

        $sections = collect();

        if (Schema::hasTable('page_sections')) {
            $sections = PageSection::where('page_key', 'home')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->keyBy('section_key');
        }

        return view(
            'frontend.home',
            compact(
                'featuredProducts',
                'homeProducts',
                'homeProductCategories',
                'categories',
                'destinations',
                'heroBackgroundUrl',
                'heroSettings',
                'heroSlides',
                'sections'
            )
        );
    }
}
