<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\SiteSetting;
use App\Support\FooterSettings;
use App\Support\NavigationSettings;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Seed the 3 fixed menu locations and migrate existing Global Assets
     * navigation/footer links into menu_items. Idempotent — safe to re-run.
     */
    public function run(): void
    {
        $header        = Menu::firstOrCreate(['location' => 'header'], ['name' => 'Header Menu', 'is_active' => true]);
        $footerQuick   = Menu::firstOrCreate(['location' => 'footer_quick'], ['name' => 'Footer — Quick Links', 'is_active' => true]);
        $footerUtility = Menu::firstOrCreate(['location' => 'footer_utility'], ['name' => 'Footer — Utility Links', 'is_active' => true]);

        $navValues    = NavigationSettings::valuesFromSettings($this->settingsFor(NavigationSettings::GROUP));
        $footerValues = FooterSettings::valuesFromSettings($this->settingsFor(FooterSettings::GROUP));

        $this->migrateTree($header, $navValues['items'] ?? []);
        $this->migrateFlat($footerQuick, $footerValues['quick_links'] ?? []);
        $this->migrateFlat($footerUtility, $footerValues['utility_links'] ?? []);
    }

    private function settingsFor(string $group)
    {
        return SiteSetting::query()->where('group', $group)->get()->keyBy('key');
    }

    /** Header items: top-level + one level of children. */
    private function migrateTree(Menu $menu, array $items): void
    {
        if ($menu->items()->exists()) {
            return; // already populated
        }

        foreach (array_values($items) as $order => $item) {
            $parent = $menu->items()->create($this->mapLink($item) + [
                'sort_order' => $order,
            ]);

            foreach (array_values($item['children'] ?? []) as $childOrder => $child) {
                $menu->items()->create($this->mapLink($child) + [
                    'parent_id'  => $parent->id,
                    'sort_order' => $childOrder,
                ]);
            }
        }
    }

    /** Footer link lists: flat, no children. */
    private function migrateFlat(Menu $menu, array $links): void
    {
        if ($menu->items()->exists()) {
            return;
        }

        foreach (array_values($links) as $order => $link) {
            $menu->items()->create($this->mapLink($link) + [
                'sort_order' => $order,
            ]);
        }
    }

    /** Map a legacy {label,url,is_external} link to menu_item attributes. */
    private function mapLink(array $link): array
    {
        $url        = trim($link['url'] ?? '');
        $isExternal = (bool) ($link['is_external'] ?? false);
        $linkType   = str_contains($url, '#') ? 'anchor' : 'url';

        return [
            'label'         => $link['label'] ?? '',
            'link_type'     => $linkType,
            'linkable_type' => null,
            'linkable_id'   => null,
            'url'           => $url,
            'target'        => $isExternal ? '_blank' : '_self',
            'is_active'     => true,
        ];
    }
}
