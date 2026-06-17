<?php

namespace App\Support;

class FooterSettings
{
    public const GROUP = 'footer_settings';
    public const QUICK_LINKS_KEY = 'footer.menu.quick_links';
    public const UTILITY_LINKS_KEY = 'footer.menu.utility_links';
    public const LAYOUT_BLOCKS_KEY = 'footer.layout.blocks';

    public static function fields(): array
    {
        return [
            [
                'key' => 'footer.logo_source',
                'slug' => 'logo_source',
                'label' => 'Footer logo source',
                'type' => 'select',
                'default' => 'light',
                'hint' => 'Choose which global logo variant is used in the footer.',
                'options' => [
                    'light' => 'Light logo',
                    'main' => 'Main logo',
                    'dark' => 'Dark logo',
                    'icon' => 'Icon logo',
                    'none' => 'Text only',
                ],
            ],
            [
                'key' => 'footer.bottom_note',
                'slug' => 'bottom_note',
                'label' => 'Footer bottom note',
                'type' => 'text',
                'default' => 'Designed for premium island travel.',
                'hint' => 'Short secondary text shown beside copyright.',
            ],
            [
                'key' => 'footer.show_cta',
                'slug' => 'show_cta',
                'label' => 'Show WhatsApp CTA',
                'type' => 'boolean',
                'default' => '1',
                'hint' => 'Display the large floating WhatsApp call-to-action above the footer.',
            ],
            [
                'key' => 'footer.show_newsletter',
                'slug' => 'show_newsletter',
                'label' => 'Show newsletter form',
                'type' => 'boolean',
                'default' => '1',
                'hint' => 'Display the email form below the footer brand text.',
            ],
            [
                'key' => 'footer.show_social_links',
                'slug' => 'show_social_links',
                'label' => 'Show social links',
                'type' => 'boolean',
                'default' => '1',
                'hint' => 'Render social links from the global Social Media Links settings.',
            ],
            [
                'key' => 'footer.show_contact_column',
                'slug' => 'show_contact_column',
                'label' => 'Show contact column',
                'type' => 'boolean',
                'default' => '1',
                'hint' => 'Render email, phone, address, WhatsApp, and opening hours from global Contact Information.',
            ],
        ];
    }

    public static function defaultQuickLinks(): array
    {
        return [
            ['label' => 'Home', 'url' => '/', 'is_external' => false],
            ['label' => 'About Us', 'url' => '/#why-choose-us', 'is_external' => false],
            ['label' => 'Packages', 'url' => '/products', 'is_external' => false],
            ['label' => 'Destinations', 'url' => '/#destinations', 'is_external' => false],
            ['label' => 'Contact Us', 'url' => '/#whatsapp-cta', 'is_external' => false],
        ];
    }

    public static function defaultUtilityLinks(): array
    {
        return [
            ['label' => 'FAQs', 'url' => '/#faq-preview', 'is_external' => false],
            ['label' => 'Blog', 'url' => '#', 'is_external' => false],
            ['label' => 'Privacy Policy', 'url' => '#', 'is_external' => false],
            ['label' => 'Terms & Conditions', 'url' => '#', 'is_external' => false],
            ['label' => '404 Page', 'url' => '#', 'is_external' => false],
        ];
    }

    public static function blockTypes(): array
    {
        return [
            'quick_links' => 'Quick Links',
            'contact_info' => 'Information',
            'utility_links' => 'Utility Links',
            'maps' => 'Maps',
            'custom_text' => 'Custom Text / Ads',
        ];
    }

    public static function widthOptions(): array
    {
        return [
            '1' => '1 column',
            '2' => '2 columns',
            'full' => 'Full width',
        ];
    }

    public static function defaultLayoutBlocks(): array
    {
        return [
            [
                'type' => 'quick_links',
                'title' => 'Quick Links',
                'width' => '1',
                'is_active' => true,
                'settings' => [],
            ],
            [
                'type' => 'contact_info',
                'title' => 'Information',
                'width' => '1',
                'is_active' => true,
                'settings' => [],
            ],
            [
                'type' => 'utility_links',
                'title' => 'Utility Pages',
                'width' => '1',
                'is_active' => true,
                'settings' => [],
            ],
        ];
    }

    public static function valuesFromSettings($settings): array
    {
        $values = collect(self::fields())
            ->mapWithKeys(function (array $field) use ($settings) {
                $setting = $settings[$field['key']] ?? null;

                return [$field['slug'] => $setting?->value ?? $field['default']];
            })
            ->all();

        foreach (self::fields() as $field) {
            if ($field['type'] === 'boolean') {
                $values[$field['slug']] = filter_var($values[$field['slug']] ?? true, FILTER_VALIDATE_BOOL);
            }
        }

        $quickLinks = json_decode(($settings[self::QUICK_LINKS_KEY] ?? null)?->value ?: '[]', true);
        $utilityLinks = json_decode(($settings[self::UTILITY_LINKS_KEY] ?? null)?->value ?: '[]', true);
        $layoutBlocks = json_decode(($settings[self::LAYOUT_BLOCKS_KEY] ?? null)?->value ?: '[]', true);

        $values['quick_links'] = self::normalizeLinks(is_array($quickLinks) && $quickLinks !== [] ? $quickLinks : self::defaultQuickLinks());
        $values['utility_links'] = self::normalizeLinks(is_array($utilityLinks) && $utilityLinks !== [] ? $utilityLinks : self::defaultUtilityLinks());
        $values['layout_blocks'] = self::normalizeLayoutBlocks(is_array($layoutBlocks) && $layoutBlocks !== [] ? $layoutBlocks : self::defaultLayoutBlocks());

        return $values;
    }

    public static function normalizeLinks(array $links): array
    {
        return collect($links)
            ->map(fn (array $link) => [
                'label' => trim($link['label'] ?? ''),
                'url' => trim($link['url'] ?? ''),
                'is_external' => (bool) ($link['is_external'] ?? false),
            ])
            ->filter(fn (array $link) => $link['label'] !== '' && $link['url'] !== '')
            ->values()
            ->all();
    }

    public static function normalizeLayoutBlocks(array $blocks): array
    {
        $allowedTypes = array_keys(self::blockTypes());
        $allowedWidths = array_map('strval', array_keys(self::widthOptions()));

        return collect($blocks)
            ->map(function (array $block) use ($allowedTypes, $allowedWidths) {
                $type = in_array($block['type'] ?? '', $allowedTypes, true) ? $block['type'] : 'quick_links';
                $title = trim($block['title'] ?? '') ?: self::blockTypes()[$type];
                $width = in_array((string) ($block['width'] ?? '1'), $allowedWidths, true) ? (string) $block['width'] : '1';
                $settings = is_array($block['settings'] ?? null) ? $block['settings'] : [];

                return [
                    'type' => $type,
                    'title' => $title,
                    'width' => $width,
                    'is_active' => (bool) ($block['is_active'] ?? false),
                    'settings' => [
                        'maps_embed_url' => trim($settings['maps_embed_url'] ?? ''),
                        'custom_body' => trim($settings['custom_body'] ?? ''),
                    ],
                ];
            })
            ->filter(fn (array $block) => $block['title'] !== '')
            ->values()
            ->all();
    }

    public static function layoutWidthTotal(array $blocks): int
    {
        return collect($blocks)
            ->filter(fn (array $block) => (bool) ($block['is_active'] ?? false))
            ->sum(fn (array $block) => match ((string) ($block['width'] ?? '1')) {
                '2' => 2,
                'full' => 3,
                default => 1,
            });
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

    public static function logoAssetKey(string $source): ?string
    {
        return match ($source) {
            'main' => HomepageSectionMedia::SITE_LOGO_KEY,
            'dark' => 'site.logo.dark',
            'icon' => 'site.logo.icon',
            'none' => null,
            default => 'site.logo.light',
        };
    }
}
