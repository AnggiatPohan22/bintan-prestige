<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Faq;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\ProductImage;

class DashboardController extends Controller
{
    public function index()
    {
        return view('backend.dashboard', [
            'productCount' => Product::count(),
            'publishedProductCount' => Product::where('status', 'published')->count(),
            'draftProductCount' => Product::where('status', 'draft')->count(),
            'categoryCount' => Category::count(),
            'destinationCount' => Destination::count(),
            'faqCount' => Faq::count(),
            'pageSectionCount' => PageSection::count(),
            'productImageCount' => ProductImage::count(),
            'bookingCount' => Booking::count(),
            'recentProducts' => Product::with(['category', 'destination'])
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}
