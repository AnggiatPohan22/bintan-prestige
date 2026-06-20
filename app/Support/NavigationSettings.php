<?php

namespace App\Support;

class NavigationSettings
{
    public const GROUP = 'navigation_settings';
    public const ITEMS_KEY = 'navigation.header.items';

    public static function fields(): array
    {
        return [
            [
                'key' => 'navigation.header.cta_label',
                'slug' => 'cta_label',
                'label' => 'Header CTA label',
                'type' => 'text',
                'default' => 'Plan Trip',
                'hint' => 'Short text shown in the header action button.',
            ],
            [
                'key' => 'navigation.header.cta_url',
                'slug' => 'cta_url',
                'label' => 'Header CTA URL',
                'type' => 'text',
                'default' => '/#whatsapp-cta',
                'hint' => 'Internal path, anchor, or full external URL for the header CTA.',
            ],
            [
                'key' => 'navigation.header.is_sticky',
                'slug' => 'is_sticky',
                'label' => 'Sticky header',
                'type' => 'boolean',
                'default' => '1',
                'hint' => 'When enabled, the public header floats and reacts while visitors scroll.',
            ],
            [
                'key' => 'navigation.header.menu_text_color',
                'slug' => 'menu_text_color',
                'label' => 'Menu text',
                'type' => 'color',
                'default' => '#FFFFFF',
                'hint' => 'Menu link color when the header is transparent.',
            ],
            [
                'key' => 'navigation.header.menu_hover_color',
                'slug' => 'menu_hover_color',
                'label' => 'Menu hover / active',
                'type' => 'color',
                'default' => '#C8A24A',
                'hint' => 'Menu hover and active color when the header is transparent.',
            ],
            [
                'key' => 'navigation.header.scrolled_menu_text_color',
                'slug' => 'scrolled_menu_text_color',
                'label' => 'Scrolled menu text',
                'type' => 'color',
                'default' => '#17130C',
                'hint' => 'Menu link color after the header changes to its scrolled or inline state.',
            ],
            [
                'key' => 'navigation.header.scrolled_menu_hover_color',
                'slug' => 'scrolled_menu_hover_color',
                'label' => 'Scrolled hover / active',
                'type' => 'color',
                'default' => '#17130C',
                'hint' => 'Menu hover and active color after the header changes to its scrolled or inline state.',
            ],
            [
                'key' => 'navigation.header.dropdown_text_color',
                'slug' => 'dropdown_text_color',
                'label' => 'Dropdown text',
                'type' => 'color',
                'default' => '#17130C',
                'hint' => 'Text color for dropdown menu items.',
            ],
            [
                'key' => 'navigation.header.dropdown_hover_background',
                'slug' => 'dropdown_hover_background',
                'label' => 'Dropdown hover background',
                'type' => 'color',
                'default' => '#E8D9A7',
                'hint' => 'Background color for hovered or active dropdown items.',
            ],
        ];
    }

    public static function defaultItems(): array
    {
        return [
            ['label' => 'Home', 'url' => '/', 'is_external' => false, 'children' => []],
            ['label' => 'Packages', 'url' => '/products', 'is_external' => false, 'children' => []],
            ['label' => 'Destinations', 'url' => '/#destinations', 'is_external' => false, 'children' => []],
            ['label' => 'Contact', 'url' => '/#whatsapp-cta', 'is_external' => false, 'children' => []],
        ];
    }

    public static function valuesFromSettings($settings): array
    {
        $values = collect(self::fields())
            ->mapWithKeys(function (array $field) use ($settings) {
                $setting = $settings[$field['key']] ?? null;

                return [$field['slug'] => $setting->value ?? $field['default']];
            })
            ->all();

        $itemsSetting = $settings[self::ITEMS_KEY] ?? null;
        $decodedItems = json_decode($itemsSetting?->value ?: '[]', true);

        $values['items'] = self::normalizeItems(is_array($decodedItems) && $decodedItems !== []
            ? $decodedItems
            : self::defaultItems());
        $values['is_sticky'] = filter_var($values['is_sticky'] ?? true, FILTER_VALIDATE_BOOL);

        return $values;
    }

    public static function normalizeItems(array $items): array
    {
        return collect($items)
            ->map(function (array $item) {
                $children = isset($item['children']) && is_array($item['children'])
                    ? self::normalizeChildren($item['children'])
                    : [];

                return [
                    'label' => trim($item['label'] ?? ''),
                    'url' => trim($item['url'] ?? ''),
                    'is_external' => (bool) ($item['is_external'] ?? false),
                    'children' => $children,
                ];
            })
            ->filter(fn (array $item) => $item['label'] !== '' && $item['url'] !== '')
            ->values()
            ->all();
    }

    public static function normalizeChildren(array $children): array
    {
        return collect($children)
            ->map(fn (array $child) => [
                'label' => trim($child['label'] ?? ''),
                'url' => trim($child['url'] ?? ''),
                'is_external' => (bool) ($child['is_external'] ?? false),
            ])
            ->filter(fn (array $child) => $child['label'] !== '' && $child['url'] !== '')
            ->values()
            ->all();
    }

    public static function resolveUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '#';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '#')) {
            return $url;
        }

        return url($url);
    }

    public static function isActiveUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $path = '/' . ltrim($path, '/');
        $currentPath = '/' . trim(request()->path(), '/');

        if ($path === '/') {
            return request()->routeIs('home') || $currentPath === '/';
        }

        return $currentPath === $path || str_starts_with($currentPath, rtrim($path, '/') . '/');
    }
}
