<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Destination;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\GlobalSettingsService;
use App\Support\CategoryDestinationDisplayState;
use App\Support\PageSectionRegistry;
use App\Support\ProductDetailDisplayState;
use App\Support\ProductListingContent;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request, GlobalSettingsService $globalSettings)
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
        $hasUnsupportedQueryParameters = $this->hasUnsupportedQueryParameters($request);
        $filterQueryParameters = Arr::except($validQueryParameters, 'sort');
        $resetListingUrl = route('products.index');
        $categoryContext = count($selectedCategories) === 1 && count($selectedDestinations) === 0
            ? $categories->firstWhere('id', (int) $selectedCategories[0])
            : null;
        $destinationContext = count($selectedDestinations) === 1 && count($selectedCategories) === 0
            ? $destinations->firstWhere('id', (int) $selectedDestinations[0])
            : null;
        $activeFilterSummary = $this->activeFilterSummary(
            $minPrice,
            $maxPrice,
            $selectedDurations,
            $selectedDestinations,
            $selectedCategories,
            $selectedVehicleTypes,
            $destinations,
            $categories,
            $sort,
            $sortOptions,
            $priceCurrency,
            $validQueryParameters
        );

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
            ->paginate(9)
            ->appends($validQueryParameters);
        $hasHighPageEmptyState = $products->total() > 0 && $products->count() === 0;
        $highPageRecoveryUrl = route(
            'products.index',
            $this->highPageRecoveryParameters($validQueryParameters)
        );

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
        $hasActiveListingConstraints = $activeFilterCount > 0 || $hasInvalidFilter;
        $resultSummary = $this->resultSummary($products->total());
        $emptyState = $this->emptyState(
            $hasActiveListingConstraints,
            $hasInvalidFilter,
            $hasHighPageEmptyState,
            $resetListingUrl,
            $highPageRecoveryUrl
        );
        $entityContext = null;

        if ($categoryContext) {
            $activeFilterSummary = $this->summaryWithoutFixedEntity($activeFilterSummary, 'Category');
            $activeFilterCount = max(0, $activeFilterCount - 1);
            $entityContext = CategoryDestinationDisplayState::category(
                $categoryContext,
                $products,
                [
                    'query' => $validQueryParameters,
                    'selected' => [
                        'durations' => $selectedDurations,
                        'destinations' => $selectedDestinations,
                        'categories' => $selectedCategories,
                        'vehicleTypes' => $selectedVehicleTypes,
                        'minPrice' => $minPrice,
                        'maxPrice' => $maxPrice,
                    ],
                    'summary' => $activeFilterSummary,
                ],
                $sort
            );
            $resetListingUrl = $entityContext['resetUrl'];
            $emptyState = $this->listingEmptyStateFromEntity($entityContext, $emptyState);
        } elseif ($destinationContext) {
            $activeFilterSummary = $this->summaryWithoutFixedEntity($activeFilterSummary, 'Destination');
            $activeFilterCount = max(0, $activeFilterCount - 1);
            $globalViewData = $globalSettings->viewData();
            $entityContext = CategoryDestinationDisplayState::destination(
                $destinationContext,
                $products,
                $globalViewData['siteAssets'] ?? collect(),
                $globalViewData['defaultMediaSettings'] ?? [],
                [
                    'query' => $validQueryParameters,
                    'selected' => [
                        'durations' => $selectedDurations,
                        'destinations' => $selectedDestinations,
                        'categories' => $selectedCategories,
                        'vehicleTypes' => $selectedVehicleTypes,
                        'minPrice' => $minPrice,
                        'maxPrice' => $maxPrice,
                    ],
                    'summary' => $activeFilterSummary,
                ],
                $sort
            );
            $resetListingUrl = $entityContext['resetUrl'];
            $emptyState = $this->listingEmptyStateFromEntity($entityContext, $emptyState);
        }

        $seoState = $this->listingSeoState(
            $listingContent,
            $selectedDestinations,
            $selectedCategories,
            $destinations,
            $categories,
            $sort,
            $minPrice,
            $maxPrice,
            $hasActiveListingConstraints,
            $hasInvalidFilter,
            $hasUnsupportedQueryParameters,
            $hasHighPageEmptyState,
            $products->currentPage()
        );
        $seoTitle = $seoState['title'];
        $seoDescription = $seoState['description'];
        $canonicalUrl = $seoState['canonical'];
        $seoRobots = $seoState['robots'];
        $structuredDataListingProducts = $products;

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
                'listingContent',
                'resetListingUrl',
                'activeFilterSummary',
                'hasInvalidFilter',
                'hasActiveListingConstraints',
                'resultSummary',
                'emptyState',
                'hasHighPageEmptyState',
                'hasUnsupportedQueryParameters',
                'entityContext',
                'seoTitle',
                'seoDescription',
                'canonicalUrl',
                'seoRobots',
                'structuredDataListingProducts'
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

    private function hasUnsupportedQueryParameters(Request $request): bool
    {
        $allowedParameters = [
            'category',
            'destination',
            'duration',
            'vehicle_type',
            'min_price',
            'max_price',
            'sort',
            'page',
        ];

        return collect(array_keys($request->query()))
            ->contains(fn (string $key) => ! in_array($key, $allowedParameters, true));
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

    private function activeFilterSummary(
        ?int $minPrice,
        ?int $maxPrice,
        array $selectedDurations,
        array $selectedDestinations,
        array $selectedCategories,
        array $selectedVehicleTypes,
        $destinations,
        $categories,
        string $sort,
        array $sortOptions,
        string $priceCurrency,
        array $validQueryParameters
    ): array {
        $summary = [];
        $destinationNames = $destinations
            ->keyBy(fn ($destination) => (string) $destination->id);
        $categoryNames = $categories
            ->keyBy(fn ($category) => (string) $category->id);

        if ($selectedCategories) {
            $summary[] = [
                'label' => 'Category',
                'value' => collect($selectedCategories)
                    ->map(fn ($id) => $categoryNames->get($id)?->name)
                    ->filter()
                    ->implode(', '),
                'remove_url' => $this->listingUrlWithout($validQueryParameters, ['category']),
                'remove_label' => 'Remove category filter',
            ];
        }

        if ($selectedDestinations) {
            $summary[] = [
                'label' => 'Destination',
                'value' => collect($selectedDestinations)
                    ->map(fn ($id) => $destinationNames->get($id)?->name)
                    ->filter()
                    ->implode(', '),
                'remove_url' => $this->listingUrlWithout($validQueryParameters, ['destination']),
                'remove_label' => 'Remove destination filter',
            ];
        }

        if ($selectedDurations) {
            $summary[] = [
                'label' => 'Duration',
                'value' => implode(', ', $selectedDurations),
                'remove_url' => $this->listingUrlWithout($validQueryParameters, ['duration']),
                'remove_label' => 'Remove duration filter',
            ];
        }

        if ($selectedVehicleTypes) {
            $summary[] = [
                'label' => 'Vehicle',
                'value' => implode(', ', $selectedVehicleTypes),
                'remove_url' => $this->listingUrlWithout($validQueryParameters, ['vehicle_type']),
                'remove_label' => 'Remove vehicle filter',
            ];
        }

        if ($minPrice !== null || $maxPrice !== null) {
            $summary[] = [
                'label' => 'Price',
                'value' => $this->priceRangeLabel($minPrice, $maxPrice, $priceCurrency),
                'remove_url' => $this->listingUrlWithout($validQueryParameters, ['min_price', 'max_price']),
                'remove_label' => 'Remove price filter',
            ];
        }

        if ($sort !== 'newest') {
            $summary[] = [
                'label' => 'Sort',
                'value' => $sortOptions[$sort] ?? $sortOptions['newest'],
                'remove_url' => $this->listingUrlWithout($validQueryParameters, ['sort']),
                'remove_label' => 'Reset sorting to newest',
            ];
        }

        return collect($summary)
            ->filter(fn ($item) => filled($item['value']))
            ->values()
            ->all();
    }

    private function listingUrlWithout(array $parameters, array $keys): string
    {
        foreach ($keys as $key) {
            unset($parameters[$key]);
        }

        if (($parameters['sort'] ?? null) === 'newest') {
            unset($parameters['sort']);
        }

        return route('products.index', $parameters);
    }

    private function summaryWithoutFixedEntity(array $summary, string $fixedLabel): array
    {
        return collect($summary)
            ->reject(fn (array $item) => ($item['label'] ?? null) === $fixedLabel)
            ->values()
            ->all();
    }

    private function listingEmptyStateFromEntity(array $entityContext, array $fallback): array
    {
        $state = $entityContext['emptyState'] ?? [];

        if (($state['type'] ?? null) === 'has_results') {
            return $fallback;
        }

        $type = $state['type'] ?? null;
        $entityType = $entityContext['entity']['type'] ?? 'category';

        return [
            'title' => $state['title'] ?? $fallback['title'],
            'description' => match ($type) {
                'entity_empty' => $entityType === 'destination'
                    ? 'This destination is active, but there are no public packages available yet.'
                    : 'This category is active, but there are no public packages available yet.',
                'filtered_empty' => 'Try removing one or more filters while keeping this ' . $entityType . ' context.',
                'high_page_empty' => 'The current ' . $entityType . ' context has fewer pages for the selected criteria.',
                default => $fallback['description'],
            },
            'action' => match ($type) {
                'high_page_empty' => 'Back to first page',
                default => 'Reset filters',
            },
            'action_url' => $state['action_url'] ?? $entityContext['resetUrl'] ?? $fallback['action_url'],
        ];
    }

    private function listingSeoState(
        array $listingContent,
        array $selectedDestinations,
        array $selectedCategories,
        $destinations,
        $categories,
        string $sort,
        ?int $minPrice,
        ?int $maxPrice,
        bool $hasActiveListingConstraints,
        bool $hasInvalidFilter,
        bool $hasUnsupportedQueryParameters,
        bool $hasHighPageEmptyState,
        int $currentPage
    ): array {
        $hero = $listingContent['hero'] ?? [];
        $baseTitle = $this->plainSeoText(
            $hero['title'] ?? null,
            'Bintan Tour Packages'
        );
        $contextTitle = $this->listingContextTitle(
            $selectedDestinations,
            $selectedCategories,
            $destinations,
            $categories,
            $sort,
            $minPrice,
            $maxPrice
        );
        $hasQueryIndexRisk = $hasActiveListingConstraints
            || $sort !== 'newest'
            || $hasInvalidFilter
            || $hasUnsupportedQueryParameters;

        $title = $hasInvalidFilter || $hasUnsupportedQueryParameters
            ? 'Bintan Product Listing'
            : ($contextTitle ?: $baseTitle);
        $description = $contextTitle && ! $hasInvalidFilter && ! $hasUnsupportedQueryParameters
            ? $this->plainSeoText(
                $contextTitle . '. Browse public Bintan Prestige packages with visible destination, duration, price state, and crawlable detail links.',
                'Browse curated Bintan tours, private transfers, activities, and travel packages with clear destination, duration, and price information.'
            )
            : $this->plainSeoText(
                $hero['description'] ?? null,
                'Browse curated Bintan tours, private transfers, activities, and travel packages with clear destination, duration, and price information.'
            );
        $canonicalParameters = [];

        if (! $hasQueryIndexRisk && ! $hasHighPageEmptyState && $currentPage > 1) {
            $canonicalParameters['page'] = $currentPage;
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => route('products.index', $canonicalParameters),
            'robots' => $hasQueryIndexRisk || $hasHighPageEmptyState
                ? 'noindex, follow'
                : 'index, follow',
        ];
    }

    private function listingContextTitle(
        array $selectedDestinations,
        array $selectedCategories,
        $destinations,
        $categories,
        string $sort,
        ?int $minPrice,
        ?int $maxPrice
    ): ?string {
        $destinationName = count($selectedDestinations) === 1
            ? $destinations->firstWhere('id', (int) $selectedDestinations[0])?->name
            : null;
        $categoryName = count($selectedCategories) === 1
            ? $categories->firstWhere('id', (int) $selectedCategories[0])?->name
            : null;

        if ($categoryName && $destinationName) {
            return $categoryName . ' Packages in ' . $destinationName;
        }

        if ($destinationName) {
            return 'Packages in ' . $destinationName;
        }

        if ($categoryName) {
            return $categoryName . ' Packages in Bintan';
        }

        if ($selectedDestinations || $selectedCategories || $minPrice !== null || $maxPrice !== null || $sort !== 'newest') {
            return 'Filtered Bintan Packages';
        }

        return null;
    }

    private function plainSeoText(?string $value, string $fallback, int $limit = 160): string
    {
        $text = Str::of((string) ($value ?: $fallback))
            ->stripTags()
            ->squish()
            ->limit($limit, '')
            ->toString();

        return $text !== '' ? $text : $fallback;
    }

    private function priceRangeLabel(?int $minPrice, ?int $maxPrice, string $priceCurrency): string
    {
        $prefix = $priceCurrency === ProductPrice::CURRENCY_IDR ? 'Rp ' : $priceCurrency . ' ';

        if ($minPrice !== null && $maxPrice !== null) {
            return $prefix . number_format($minPrice, 0, ',', '.')
                . ' - '
                . $prefix . number_format($maxPrice, 0, ',', '.');
        }

        if ($minPrice !== null) {
            return 'From ' . $prefix . number_format($minPrice, 0, ',', '.');
        }

        return 'Up to ' . $prefix . number_format((int) $maxPrice, 0, ',', '.');
    }

    private function resultSummary(int $total): string
    {
        return match ($total) {
            0 => 'No packages found',
            1 => '1 package found',
            default => number_format($total) . ' packages found',
        };
    }

    private function highPageRecoveryParameters(array $validQueryParameters): array
    {
        if (($validQueryParameters['sort'] ?? null) === 'newest') {
            unset($validQueryParameters['sort']);
        }

        return $validQueryParameters;
    }

    private function emptyState(
        bool $hasActiveListingConstraints,
        bool $hasInvalidFilter,
        bool $hasHighPageEmptyState,
        string $resetListingUrl,
        string $highPageRecoveryUrl
    ): array {
        if ($hasHighPageEmptyState) {
            return [
                'title' => 'This page is empty',
                'description' => 'The listing has fewer pages for the current criteria. Return to the first page to continue browsing.',
                'action' => 'Back to first page',
                'action_url' => $highPageRecoveryUrl,
            ];
        }

        if ($hasInvalidFilter) {
            return [
                'title' => 'No packages matched the selected filters',
                'description' => 'Some filter values could not be used. Clear filters and choose from the available options.',
                'action' => 'Clear filters',
                'action_url' => $resetListingUrl,
            ];
        }

        if ($hasActiveListingConstraints) {
            return [
                'title' => 'No packages matched the selected filters',
                'description' => 'Try removing one or more filters to see more Bintan experiences.',
                'action' => 'Clear filters',
                'action_url' => $resetListingUrl,
            ];
        }

        return [
            'title' => 'No packages are currently available',
            'description' => 'Published packages will appear here once they are ready for guests.',
            'action' => 'Back to all packages',
            'action_url' => $resetListingUrl,
        ];
    }

    public function show(string $product, GlobalSettingsService $globalSettings)
    {
        $slug = $product;

        $product = Product::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->with([
                'category',
                'destination',
                'prices',
                'images' => fn ($query) => $query
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'highlights',
                'features',
                'faqs',
                'itineraries',
                'notes',
            ])
            ->firstOrFail();
        $globalViewData = $globalSettings->viewData();
        $displayState = ProductDetailDisplayState::make($product, $globalViewData);
        $metadataState = $displayState['metadataState'];

        return view(
            'frontend.products.show',
            [
                'product' => $product,
                ...$displayState,

                'seoTitle' =>
                    $metadataState['title'],

                'seoDescription' =>
                    $metadataState['description'],

                'seoKeywords' =>
                    $metadataState['keywords'],

                'canonicalUrl' =>
                    $metadataState['canonical'],

                'seoImage' =>
                    $metadataState['image'],

                'seoImageAlt' =>
                    $metadataState['image_alt'],

                'seoRobots' =>
                    $metadataState['robots'],

                'socialShareType' =>
                    $metadataState['social_share_type'],
            ]
        );
    }
}
