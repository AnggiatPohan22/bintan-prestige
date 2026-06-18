<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class MenuService
{
    public const CACHE_PREFIX = 'menu.tree.v1.';
    public const CACHE_TTL_MINUTES = 30;

    /** Fixed locations consumed by the frontend header/footer. */
    public const LOCATIONS = ['header', 'footer_quick', 'footer_utility'];

    /**
     * Return a normalized tree for a location, shaped to match what the
     * header/footer partials already consume:
     * [['label','url','is_external','children'=>[['label','url','is_external']]]]
     *
     * Returns [] when the menu is missing/inactive/empty so the views can
     * fall back to the legacy Global Assets settings.
     */
    public function tree(string $location): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . $location,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => $this->build($location)
        );
    }

    public function forget(?string $location = null): void
    {
        foreach ($location ? [$location] : self::LOCATIONS as $loc) {
            Cache::forget(self::CACHE_PREFIX . $loc);
        }
    }

    private function build(string $location): array
    {
        if (! Schema::hasTable('menus') || ! Schema::hasTable('menu_items')) {
            return [];
        }

        $menu = Menu::query()
            ->active()
            ->where('location', $location)
            ->with(['rootItems' => fn ($query) => $query
                ->active()
                ->with([
                    'linkable',
                    'children' => fn ($child) => $child->active()->with('linkable'),
                ]),
            ])
            ->first();

        if (! $menu) {
            return [];
        }

        return $menu->rootItems
            ->map(fn (MenuItem $item) => [
                'label'       => $item->label,
                'url'         => $item->resolveUrl(),
                'is_external' => $item->isExternal(),
                'children'    => $item->children
                    ->map(fn (MenuItem $child) => [
                        'label'       => $child->label,
                        'url'         => $child->resolveUrl(),
                        'is_external' => $child->isExternal(),
                    ])
                    ->all(),
            ])
            ->all();
    }
}
