<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Faq;
use App\Models\Product;
use App\Services\PageSectionService;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __construct(
        protected PageSectionService $pageSectionService,
    ) {}

    public function index()
    {
        $heroBackgroundUrl = null;
        $heroSettings = [
            'animation' => 'ken-burns',
            'slide_duration' => 6500,
            'image_fit' => 'cover',
            'image_position' => 'center center',
            'overlay_opacity' => 0.72,
            'text_alignment' => 'left',
        ];

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

        $sections = $this->pageSectionService->getHomeSections();

        $faqs = collect();

        if (Schema::hasTable('faqs')) {
            $faqs = Faq::query()
                ->where('page_key', 'home')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
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
                'sections',
                'faqs'
            )
        );
    }
}
