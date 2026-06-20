<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class MenuService
{
    public const CACHE_PREFIX = 'menu.tree.v2.';

    public const CACHE_TTL_MINUTES = 30;

    public const LOCATIONS = ['header', 'footer_quick', 'footer_utility'];

    public function tree(string $location): array
    {
        return $this->trees([$location])[$location] ?? [];
    }

    /**
     * @param  array<int, string>  $locations
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function trees(array $locations = self::LOCATIONS): array
    {
        return collect($this->sources($locations))
            ->mapWithKeys(fn (array $source, string $location): array => [
                $location => $source['items'],
            ])
            ->all();
    }

    /**
     * Preserve whether a Menu Manager location exists separately from its
     * items, so an intentionally empty/disabled menu does not resurrect
     * legacy Global Assets links.
     *
     * @param  array<int, string>  $locations
     * @return array<string, array{managed: bool, items: array<int, array<string, mixed>>}>
     */
    public function sources(array $locations = self::LOCATIONS): array
    {
        $locations = collect($locations)
            ->filter(fn (string $location) => $location !== '')
            ->unique()
            ->values()
            ->all();

        $sources = [];
        $missingLocations = [];

        foreach ($locations as $location) {
            $cacheKey = self::CACHE_PREFIX.$location;

            if (Cache::has($cacheKey)) {
                $sources[$location] = Cache::get($cacheKey);
            } else {
                $missingLocations[] = $location;
            }
        }

        if ($missingLocations !== []) {
            $resolved = $this->buildMany($missingLocations);

            foreach ($missingLocations as $location) {
                $source = $resolved[$location] ?? ['managed' => false, 'items' => []];
                $sources[$location] = $source;
                Cache::put(
                    self::CACHE_PREFIX.$location,
                    $source,
                    now()->addMinutes(self::CACHE_TTL_MINUTES),
                );
            }
        }

        return collect($locations)
            ->mapWithKeys(fn (string $location): array => [
                $location => $sources[$location] ?? ['managed' => false, 'items' => []],
            ])
            ->all();
    }

    public function forget(?string $location = null): void
    {
        foreach ($location ? [$location] : self::LOCATIONS as $loc) {
            Cache::forget(self::CACHE_PREFIX.$loc);
        }
    }

    public function nextSortOrder(Menu $menu, ?int $parentId): int
    {
        $max = $this->siblingQuery($menu, $parentId)->max('sort_order');

        return $max === null ? 0 : ((int) $max + 1);
    }

    /**
     * Persist one complete sibling group as a dense zero-based sequence.
     *
     * @param  array<int, int|string>  $ids
     */
    public function reorder(Menu $menu, array $ids): void
    {
        $ids = collect($ids)->map(fn ($id): int => (int) $id)->all();

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['ids' => 'Menu order contains duplicate items.']);
        }

        $items = $menu->items()->whereIn('id', $ids)->get(['id', 'parent_id']);

        if ($items->count() !== count($ids)) {
            throw ValidationException::withMessages(['ids' => 'Every reordered item must belong to this menu.']);
        }

        $parentIds = $items->pluck('parent_id')->uniqueStrict();
        if ($parentIds->count() !== 1) {
            throw ValidationException::withMessages(['ids' => 'Only items with the same parent can be reordered together.']);
        }

        $parentId = $items->first()?->parent_id;
        $expected = $this->siblingQuery($menu, $parentId)->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all();
        $submitted = collect($ids)->sort()->values()->all();

        if ($expected !== $submitted) {
            throw ValidationException::withMessages(['ids' => 'Submit the complete sibling group when reordering menu items.']);
        }

        DB::transaction(function () use ($menu, $ids): void {
            foreach ($ids as $sortOrder => $id) {
                $menu->items()->whereKey($id)->update(['sort_order' => $sortOrder]);
            }
        });

        $this->forget($menu->location);
    }

    /** @param  array<int, int|null>  $parentIds */
    public function normalizeSiblingOrders(Menu $menu, array $parentIds): void
    {
        foreach (collect($parentIds)->uniqueStrict() as $parentId) {
            $this->siblingQuery($menu, $parentId === null ? null : (int) $parentId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('id')
                ->each(fn ($id, int $sortOrder) => $menu->items()->whereKey($id)->update(['sort_order' => $sortOrder]));
        }
    }

    /**
     * @param  array<int, string>  $locations
     * @return array<string, array{managed: bool, items: array<int, array<string, mixed>>}>
     */
    private function buildMany(array $locations): array
    {
        if (! Schema::hasTable('menus') || ! Schema::hasTable('menu_items')) {
            return collect($locations)
                ->mapWithKeys(fn (string $location): array => [
                    $location => ['managed' => false, 'items' => []],
                ])
                ->all();
        }

        $menus = Menu::query()
            ->whereIn('location', $locations)
            ->with(['rootItems' => fn ($query) => $query
                ->active()
                ->with([
                    'linkable' => fn (MorphTo $linkable) => $this->constrainLinkable($linkable),
                    'children' => fn ($children) => $children
                        ->active()
                        ->with(['linkable' => fn (MorphTo $linkable) => $this->constrainLinkable($linkable)]),
                ]),
            ])
            ->get()
            ->keyBy('location');

        return collect($locations)
            ->mapWithKeys(function (string $location) use ($menus): array {
                $menu = $menus->get($location);

                return [$location => [
                    'managed' => $menu !== null,
                    'items' => $menu?->is_active ? $this->normalizeTree($menu->rootItems) : [],
                ]];
            })
            ->all();
    }

    private function constrainLinkable(MorphTo $linkable): void
    {
        $linkable->constrain([
            Page::class => fn ($query) => $query->published(),
            Product::class => fn ($query) => $query->publiclyVisible(),
            Category::class => fn ($query) => $query->where('is_active', true),
            Destination::class => fn ($query) => $query->where('is_active', true),
        ]);
    }

    private function normalizeTree($rootItems): array
    {
        return $rootItems
            ->filter(fn (MenuItem $item): bool => $this->isRenderable($item))
            ->map(fn (MenuItem $item) => [
                'label' => $item->label,
                'url' => $item->resolveUrl(),
                'is_external' => $item->isExternal(),
                'children' => $item->children
                    ->filter(fn (MenuItem $child): bool => $this->isRenderable($child))
                    ->map(fn (MenuItem $child) => [
                        'label' => $child->label,
                        'url' => $child->resolveUrl(),
                        'is_external' => $child->isExternal(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    private function isRenderable(MenuItem $item): bool
    {
        return ! in_array($item->link_type, ['page', 'product', 'category', 'destination'], true)
            || $item->linkable !== null;
    }

    private function siblingQuery(Menu $menu, ?int $parentId)
    {
        return $menu->items()
            ->when(
                $parentId === null,
                fn ($query) => $query->whereNull('parent_id'),
                fn ($query) => $query->where('parent_id', $parentId),
            );
    }
}
