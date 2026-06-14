<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Destination;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Support\PageSectionRegistry;
use App\Support\ProductListingContent;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $priceCurrency = ProductPrice::CURRENCY_IDR;

        $sortOptions = [
            'price_low' => 'Harga IDR terendah',
            'price_high' => 'Harga IDR tertinggi',
            'newest' => 'Tour Terbaru',
        ];
        $listingSectionKeys = collect(PageSectionRegistry::sections()['products.index'] ?? [])
            ->pluck('section_key');
        $listingSections = PageSection::query()
            ->where('page_key', 'products.index')
            ->whereIn('section_key', $listingSectionKeys)
            ->where('is_active', true)
            ->with('media')
            ->get()
            ->keyBy('section_key');
        $listingContent = ProductListingContent::fromSections($listingSections);

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $durations = Product::query()
            ->publiclyVisible()
            ->whereNotNull('duration')
            ->where('duration', '!=', '')
            ->distinct()
            ->orderBy('duration')
            ->pluck('duration');

        $vehicleTypes = Product::query()
            ->publiclyVisible()
            ->whereNotNull('pickup_type')
            ->where('pickup_type', '!=', '')
            ->distinct()
            ->orderBy('pickup_type')
            ->pluck('pickup_type');

        [$selectedDurations, $hasInvalidDurationFilter] = $this->normalizedAllowedArrayInput(
            $request,
            'duration',
            $durations->all()
        );
        [$selectedDestinations, $hasInvalidDestinationFilter] = $this->normalizedAllowedArrayInput(
            $request,
            'destination',
            $destinations->pluck('id')->map(fn ($id) => (string) $id)->all()
        );
        [$selectedCategories, $hasInvalidCategoryFilter] = $this->normalizedAllowedArrayInput(
            $request,
            'category',
            $categories->pluck('id')->map(fn ($id) => (string) $id)->all()
        );
        [$selectedVehicleTypes, $hasInvalidVehicleTypeFilter] = $this->normalizedAllowedArrayInput(
            $request,
            'vehicle_type',
            $vehicleTypes->all()
        );
        [$minPrice, $hasInvalidMinPriceFilter] = $this->normalizedNonNegativeIntegerInput(
            $request,
            'min_price'
        );
        [$maxPrice, $hasInvalidMaxPriceFilter] = $this->normalizedNonNegativeIntegerInput(
            $request,
            'max_price'
        );
        $hasInvalidPriceRange = $minPrice !== null
            && $maxPrice !== null
            && $minPrice > $maxPrice;

        $hasInvalidFilter = $hasInvalidDurationFilter
            || $hasInvalidDestinationFilter
            || $hasInvalidCategoryFilter
            || $hasInvalidVehicleTypeFilter
            || $hasInvalidMinPriceFilter
            || $hasInvalidMaxPriceFilter
            || $hasInvalidPriceRange;

        $sort = $this->normalizedSortInput($request, $sortOptions);
        $validQueryParameters = $this->listingQueryParameters(
            $sort,
            $minPrice,
            $maxPrice,
            $selectedDurations,
            $selectedDestinations,
            $selectedCategories,
            $selectedVehicleTypes
        );
        $filterQueryParameters = Arr::except($validQueryParameters, 'sort');

        $productsQuery = Product::query()
            ->publiclyVisible()
            ->frontendListingReady()
            ->when($hasInvalidFilter, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->when($minPrice !== null, function ($query) use ($minPrice) {
                $query->whereHas('prices', function ($priceQuery) use ($minPrice) {
                    $priceQuery
                        ->where('currency', ProductPrice::CURRENCY_IDR)
                        ->where('price', '>=', $minPrice);
                });
            })
            ->when($maxPrice !== null, function ($query) use ($maxPrice) {
                $query->whereHas('prices', function ($priceQuery) use ($maxPrice) {
                    $priceQuery
                        ->where('currency', ProductPrice::CURRENCY_IDR)
                        ->where('price', '<=', $maxPrice);
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

        $products = $productsQuery
            ->withMin([
                'prices as idr_price_sort' => fn ($query) => $query->where('currency', $priceCurrency)
            ], 'price')
            ->when($sort === 'price_low', function ($query) {
                $query
                    ->orderByRaw('CASE WHEN idr_price_sort IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('idr_price_sort')
                    ->orderByDesc('products.created_at')
                    ->orderByDesc('products.id');
            })
            ->when($sort === 'price_high', function ($query) {
                $query
                    ->orderByRaw('CASE WHEN idr_price_sort IS NULL THEN 1 ELSE 0 END')
                    ->orderByDesc('idr_price_sort')
                    ->orderByDesc('products.created_at')
                    ->orderByDesc('products.id');
            })
            ->when($sort === 'newest', function ($query) {
                $query
                    ->orderByDesc('products.created_at')
                    ->orderByDesc('products.id');
            })
            ->paginate(8)
            ->appends($validQueryParameters);

        $priceRange = [
            'min' => ProductPrice::query()
                ->where('currency', $priceCurrency)
                ->whereHas('product', function ($query) {
                    $query->publiclyVisible();
                })
                ->min('price'),
            'max' => ProductPrice::query()
                ->where('currency', $priceCurrency)
                ->whereHas('product', function ($query) {
                    $query->publiclyVisible();
                })
                ->max('price'),
        ];

        $activeFilterCount = collect([
            $minPrice !== null,
            $maxPrice !== null,
            count($selectedDurations),
            count($selectedDestinations),
            count($selectedCategories),
            count($selectedVehicleTypes),
        ])->filter()->count();

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
                'sortOptions',
                'selectedDurations',
                'selectedDestinations',
                'selectedCategories',
                'selectedVehicleTypes',
                'minPrice',
                'maxPrice',
                'filterQueryParameters',
                'priceCurrency',
                'listingContent'
            )
        );
    }

    private function normalizedAllowedArrayInput(
        Request $request,
        string $key,
        array $allowedValues
    ): array {
        [$values, $hasInvalidInput] = $this->normalizedArrayInput($request, $key);
        $allowedValues = array_map('strval', $allowedValues);
        $selectedValues = array_values(
            array_intersect($values, $allowedValues)
        );

        return [
            $selectedValues,
            $hasInvalidInput || count($values) !== count($selectedValues),
        ];
    }

    private function normalizedArrayInput(Request $request, string $key): array
    {
        $input = $request->query($key, []);
        $items = is_array($input) ? $input : [$input];
        $hasInvalidInput = false;
        $values = [];

        foreach ($items as $item) {
            if (! is_scalar($item)) {
                $hasInvalidInput = true;

                continue;
            }

            $value = trim((string) $item);

            if ($value === '') {
                continue;
            }

            $values[] = $value;
        }

        return [
            array_values(array_unique($values)),
            $hasInvalidInput,
        ];
    }

    private function normalizedNonNegativeIntegerInput(
        Request $request,
        string $key
    ): array {
        if (! $request->query->has($key)) {
            return [null, false];
        }

        $value = $request->query($key);

        if ($value === null || $value === '') {
            return [null, false];
        }

        if (! is_scalar($value) || filter_var($value, FILTER_VALIDATE_INT) === false) {
            return [null, true];
        }

        $value = (int) $value;

        if ($value < 0) {
            return [null, true];
        }

        return [$value, false];
    }

    private function normalizedSortInput(Request $request, array $sortOptions): string
    {
        $sort = $request->query('sort', 'newest');

        if (! is_string($sort) || ! array_key_exists($sort, $sortOptions)) {
            return 'newest';
        }

        return $sort;
    }

    private function listingQueryParameters(
        string $sort,
        ?int $minPrice,
        ?int $maxPrice,
        array $selectedDurations,
        array $selectedDestinations,
        array $selectedCategories,
        array $selectedVehicleTypes
    ): array {
        $parameters = [
            'sort' => $sort,
        ];

        if ($minPrice !== null) {
            $parameters['min_price'] = $minPrice;
        }

        if ($maxPrice !== null) {
            $parameters['max_price'] = $maxPrice;
        }

        if ($selectedDurations) {
            $parameters['duration'] = $selectedDurations;
        }

        if ($selectedDestinations) {
            $parameters['destination'] = $selectedDestinations;
        }

        if ($selectedCategories) {
            $parameters['category'] = $selectedCategories;
        }

        if ($selectedVehicleTypes) {
            $parameters['vehicle_type'] = $selectedVehicleTypes;
        }

        return $parameters;
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
