<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $selectedDurations = array_filter((array) $request->input('duration', []));
        $selectedDestinations = array_filter((array) $request->input('destination', []));
        $selectedCategories = array_filter((array) $request->input('category', []));
        $selectedVehicleTypes = array_filter((array) $request->input('vehicle_type', []));

        $productsQuery = Product::query()
            ->published()
            ->frontendReady()
            ->when($request->filled('min_price'), function ($query) use ($request) {
                $query->whereHas('prices', function ($priceQuery) use ($request) {
                    $priceQuery
                        ->where('currency', 'IDR')
                        ->where('price', '>=', (int) $request->input('min_price'));
                });
            })
            ->when($request->filled('max_price'), function ($query) use ($request) {
                $query->whereHas('prices', function ($priceQuery) use ($request) {
                    $priceQuery
                        ->where('currency', 'IDR')
                        ->where('price', '<=', (int) $request->input('max_price'));
                });
            })
            ->when($selectedDurations, function ($query) use ($selectedDurations) {
                $query->whereIn('duration', $selectedDurations);
            })
            ->when($selectedDestinations, function ($query) use ($selectedDestinations) {
                $query->whereIn('destination_id', $selectedDestinations);
            })
            ->when($selectedCategories, function ($query) use ($selectedCategories) {
                $query->whereIn('category_id', $selectedCategories);
            })
            ->when($selectedVehicleTypes, function ($query) use ($selectedVehicleTypes) {
                $query->whereIn('pickup_type', $selectedVehicleTypes);
            });

        $filteredPackageCount = (clone $productsQuery)->count();

        $sort = $request->input('sort', 'newest');

        $products = $productsQuery
            ->withMin([
                'prices as idr_price_sort' => fn ($query) => $query->where('currency', 'IDR')
            ], 'price')
            ->when($sort === 'price_low', function ($query) {
                $query->orderBy('idr_price_sort');
            })
            ->when($sort === 'price_high', function ($query) {
                $query->orderByDesc('idr_price_sort');
            })
            ->when($sort === 'duration_short', function ($query) {
                $query->orderByRaw('CAST(duration AS UNSIGNED) ASC');
            })
            ->when($sort === 'duration_long', function ($query) {
                $query->orderByRaw('CAST(duration AS UNSIGNED) DESC');
            })
            ->when(! in_array($sort, [
                'price_low',
                'price_high',
                'duration_short',
                'duration_long',
            ], true), function ($query) {
                $query->latest();
            })
            ->paginate(8)
            ->withQueryString();

        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        $destinations = Destination::where('is_active', true)
            ->orderBy('name')
            ->get();

        $durations = Product::query()
            ->published()
            ->whereNotNull('duration')
            ->where('duration', '!=', '')
            ->distinct()
            ->orderBy('duration')
            ->pluck('duration');

        $vehicleTypes = Product::query()
            ->published()
            ->whereNotNull('pickup_type')
            ->where('pickup_type', '!=', '')
            ->distinct()
            ->orderBy('pickup_type')
            ->pluck('pickup_type');

        $priceRange = [
            'min' => ProductPrice::query()
                ->where('currency', 'IDR')
                ->min('price'),
            'max' => ProductPrice::query()
                ->where('currency', 'IDR')
                ->max('price'),
        ];

        $activeFilterCount = collect([
            $request->filled('min_price'),
            $request->filled('max_price'),
            count($selectedDurations),
            count($selectedDestinations),
            count($selectedCategories),
            count($selectedVehicleTypes),
        ])->filter()->count();

        $sortOptions = [
            'price_low' => 'Harga terendah',
            'price_high' => 'Harga Tertinggi',
            'duration_short' => 'Durasi Tersingkat',
            'duration_long' => 'Durasi Terlama',
            'newest' => 'Tour Terbaru',
        ];

        return view(
            'frontend.products.index',
            compact(
                'products',
                'categories',
                'destinations',
                'durations',
                'vehicleTypes',
                'priceRange',
                'activeFilterCount',
                'filteredPackageCount',
                'sort',
                'sortOptions'
            )
        );
    }

    public function show(Product $product)
    {
        abort_if(
            $product->status !== 'published',
            404
        );

        $product->load([
            'category',
            'destination',
            'prices',
            'images',
            'highlights',
            'features',
            'faqs',
            'itineraries',
            'notes',
        ]);

        return view(
            'frontend.products.show',
            [
                'product' => $product,

                'seoTitle' =>
                    $product->meta_title
                    ?: $product->name,

                'seoDescription' =>
                    $product->meta_description
                    ?: $product->short_description,

                'seoKeywords' =>
                    $product->meta_keywords,

                'canonicalUrl' =>
                    $product->canonical_url
                    ?: route('products.show', $product),

                'seoImage' =>
                    $product->og_image_url
                    ?: $product->thumbnail_url,

                'socialShareType' => 'product',
            ]
        );
    }
}
