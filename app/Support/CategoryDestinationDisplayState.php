<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CategoryDestinationDisplayState
{
    public static function activeCategoryBySlug(string $slug): Category
    {
        return Category::query()
            ->where('slug', trim($slug))
            ->where('is_active', true)
            ->firstOrFail();
    }

    public static function activeDestinationBySlug(string $slug): Destination
    {
        return Destination::query()
            ->where('slug', trim($slug))
            ->where('is_active', true)
            ->firstOrFail();
    }

    public static function categoryProductsQuery(Category $category): Builder
    {
        return Product::query()
            ->publiclyVisible()
            ->where('category_id', $category->id)
            ->frontendListingReady();
    }

    public static function destinationProductsQuery(Destination $destination): Builder
    {
        return Product::query()
            ->publiclyVisible()
            ->where('destination_id', $destination->id)
            ->frontendListingReady();
    }

    public static function category(
        Category $category,
        LengthAwarePaginator $products,
        array $filters = [],
        string $sort = 'newest',
        array $options = []
    ): array {
        $name = self::displayText($category->name) ?: 'Category';
        $description = self::displayText($category->description);
        $media = self::categoryMediaState($category);
        $baseParameters = [
            'category' => [(string) $category->id],
        ];
        $baseUrl = route('products.index', $baseParameters);
        $filterState = self::filterState($filters, $sort, $baseParameters, ['category']);

        return [
            'category' => $category,
            'entity' => [
                'type' => 'category',
                'id' => $category->id,
                'name' => $name,
                'slug' => $category->slug,
                'is_active' => (bool) $category->is_active,
            ],
            'pageTitle' => $name,
            'description' => $description,
            'descriptionState' => self::descriptionState($description),
            'media' => $media,
            'mediaStrategy' => [
                'type' => $media['available'] ? 'image_first' : 'text_first',
                'available' => $media['available'],
                'reason' => $media['available'] ? 'Category image set via Media Library.' : 'Category has no image yet.',
            ],
            'productCount' => $products->total(),
            'products' => $products,
            'filters' => $filterState,
            'sort' => [
                'selected' => $sort,
            ],
            'baseUrl' => $baseUrl,
            'resetUrl' => $baseUrl,
            'breadcrumbs' => self::breadcrumbs($name, 'Categories'),
            'emptyState' => self::emptyState($products, $filterState, $baseUrl, 'category'),
            'cmsIntro' => self::optionalContentState($options['cmsIntro'] ?? []),
            'finalCta' => self::optionalContentState($options['finalCta'] ?? []),
            'metadataReady' => self::metadataReady($name, $description, $media['available'] ? $media : null),
        ];
    }

    /**
     * Category media parity with destinations (Phase 6.1 S5): image only,
     * no site-asset fallback — categories render text-first when unset.
     */
    private static function categoryMediaState(Category $category): array
    {
        $name = self::displayText($category->name) ?: 'Category';

        if (filled($category->image)) {
            return [
                'available' => true,
                'url' => asset('storage/' . ltrim($category->image, '/')),
                'alt' => $name . ' category image',
                'fit' => null,
                'is_fallback' => false,
                'source' => 'category',
            ];
        }

        return [
            'available' => false,
            'url' => null,
            'alt' => $name . ' category image',
            'fit' => null,
            'is_fallback' => false,
            'source' => 'none',
        ];
    }

    public static function destination(
        Destination $destination,
        LengthAwarePaginator $products,
        mixed $siteAssets,
        array $defaultMediaSettings,
        array $filters = [],
        string $sort = 'newest',
        array $options = []
    ): array {
        $name = self::displayText($destination->name) ?: 'Destination';
        $description = self::displayText($destination->description);
        $media = self::destinationMediaState($destination, $siteAssets, $defaultMediaSettings);
        $baseParameters = [
            'destination' => [(string) $destination->id],
        ];
        $baseUrl = route('products.index', $baseParameters);
        $filterState = self::filterState($filters, $sort, $baseParameters, ['destination']);

        return [
            'destination' => $destination,
            'entity' => [
                'type' => 'destination',
                'id' => $destination->id,
                'name' => $name,
                'slug' => $destination->slug,
                'is_active' => (bool) $destination->is_active,
            ],
            'pageTitle' => $name,
            'description' => $description,
            'descriptionState' => self::descriptionState($description),
            'media' => $media,
            'productCount' => $products->total(),
            'products' => $products,
            'filters' => $filterState,
            'sort' => [
                'selected' => $sort,
            ],
            'baseUrl' => $baseUrl,
            'resetUrl' => $baseUrl,
            'breadcrumbs' => self::breadcrumbs($name, 'Destinations'),
            'emptyState' => self::emptyState($products, $filterState, $baseUrl, 'destination'),
            'cmsIntro' => self::optionalContentState($options['cmsIntro'] ?? []),
            'finalCta' => self::optionalContentState($options['finalCta'] ?? []),
            'metadataReady' => self::metadataReady($name, $description, $media),
        ];
    }

    private static function destinationMediaState(
        Destination $destination,
        mixed $siteAssets,
        array $defaultMediaSettings
    ): array {
        $name = self::displayText($destination->name) ?: 'Destination';

        if (filled($destination->image)) {
            return [
                'available' => true,
                'url' => asset('storage/' . ltrim($destination->image, '/')),
                'alt' => $name . ' destination image',
                'fit' => null,
                'is_fallback' => false,
                'source' => 'destination',
            ];
        }

        $fallbackAsset = DefaultMediaAssets::asset($siteAssets, 'destination');
        $fallbackFit = DefaultMediaAssets::fit($defaultMediaSettings, 'destination');

        return [
            'available' => (bool) $fallbackAsset?->url,
            'url' => $fallbackAsset?->url,
            'alt' => $fallbackAsset?->alt ?: $name . ' destination image',
            'fit' => $fallbackFit,
            'is_fallback' => (bool) $fallbackAsset?->url,
            'source' => $fallbackAsset?->url ? 'fallback' : 'none',
        ];
    }

    private static function filterState(
        array $filters,
        string $sort,
        array $baseParameters,
        array $fixedKeys
    ): array {
        $query = collect($filters['query'] ?? $filters)
            ->reject(fn ($value, string $key) => $key === 'page' || in_array($key, $fixedKeys, true))
            ->filter(fn ($value) => self::filledFilterValue($value))
            ->all();

        if ($sort !== 'newest') {
            $query['sort'] = $sort;
        } else {
            unset($query['sort']);
        }

        return [
            'selected' => $filters['selected'] ?? [],
            'summary' => $filters['summary'] ?? [],
            'query' => $query,
            'pagination' => array_merge($baseParameters, $query),
            'has_active' => ! empty($query),
        ];
    }

    private static function emptyState(
        LengthAwarePaginator $products,
        array $filterState,
        string $baseUrl,
        string $entityType
    ): array {
        $type = 'has_results';

        if ($products->total() > 0 && count($products->items()) === 0) {
            $type = 'high_page_empty';
        } elseif ($products->total() === 0 && $filterState['has_active']) {
            $type = 'filtered_empty';
        } elseif ($products->total() === 0) {
            $type = 'entity_empty';
        }

        $entityLabel = $entityType === 'destination' ? 'destination' : 'category';

        return [
            'type' => $type,
            'has_results' => $type === 'has_results',
            'is_empty' => $type !== 'has_results',
            'title' => match ($type) {
                'filtered_empty' => 'No packages matched the selected filters',
                'high_page_empty' => 'This page does not have any packages',
                'entity_empty' => 'No packages are currently available for this ' . $entityLabel,
                default => null,
            },
            'action_label' => $type === 'has_results' ? null : 'Reset filters',
            'action_url' => $baseUrl,
        ];
    }

    private static function breadcrumbs(string $currentLabel, string $sectionLabel): Collection
    {
        return collect([
            ['label' => 'Home', 'url' => route('home'), 'current' => false],
            ['label' => 'Products', 'url' => route('products.index'), 'current' => false],
            ['label' => $sectionLabel, 'url' => null, 'current' => false],
            ['label' => $currentLabel, 'url' => null, 'current' => true],
        ]);
    }

    private static function descriptionState(?string $description): array
    {
        return [
            'text' => $description,
            'has_description' => $description !== null,
            'render_mode' => 'escaped_plain_text_with_line_breaks',
        ];
    }

    private static function metadataReady(string $title, ?string $description, ?array $media): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'image' => $media['url'] ?? null,
            'image_alt' => $media['alt'] ?? $title,
            'is_final_seo' => false,
        ];
    }

    private static function optionalContentState(array $content): array
    {
        $title = self::displayText($content['title'] ?? null);
        $description = self::displayText($content['description'] ?? null);
        $ctaText = self::displayText($content['cta_text'] ?? null);
        $ctaUrl = $content['cta_url'] ?? null;

        return [
            'available' => $title !== null || $description !== null,
            'title' => $title,
            'description' => $description,
            'cta_text' => $ctaText,
            'cta_url' => is_string($ctaUrl) && $ctaUrl !== '' ? $ctaUrl : null,
            'has_cta' => $ctaText !== null && is_string($ctaUrl) && $ctaUrl !== '',
        ];
    }

    private static function displayText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private static function filledFilterValue(mixed $value): bool
    {
        if (is_array($value)) {
            return collect($value)->filter(fn ($item) => filled($item))->isNotEmpty();
        }

        return filled($value);
    }
}
