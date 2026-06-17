<?php

namespace App\Support;

class HomepageSectionMedia
{
    public const SITE_LOGO_KEY = 'site.logo';

    public static function slotsFor(?string $sectionKey): array
    {
        return self::config()[$sectionKey]['media_slots'] ?? [];
    }

    public static function usesLogo(?string $sectionKey): bool
    {
        return in_array(self::SITE_LOGO_KEY, self::config()[$sectionKey]['global_assets'] ?? [], true);
    }

    public static function allowsGallery(?string $sectionKey): bool
    {
        return (bool) (self::config()[$sectionKey]['gallery'] ?? false);
    }

    public static function supportsLegacyImages(?string $sectionKey): bool
    {
        return (bool) (self::config()[$sectionKey]['legacy_images'] ?? false);
    }

    public static function displayOrder(?string $sectionKey): int
    {
        return self::config()[$sectionKey]['display_order'] ?? 9999;
    }

    public static function orderedSectionKeys(): array
    {
        return collect(self::config())
            ->sortBy(fn (array $config) => $config['display_order'] ?? 9999)
            ->keys()
            ->all();
    }

    public static function slotLabel(string $sectionKey, string $role, string $slotKey): string
    {
        foreach (self::slotsFor($sectionKey) as $slot) {
            if (($slot['role'] ?? null) === $role && ($slot['slot_key'] ?? null) === $slotKey) {
                return $slot['label'] ?? $slotKey;
            }
        }

        return $slotKey;
    }

    public static function config(): array
    {
        return [
            'home.hero' => [
                'display_order' => 0,
                'legacy_images' => true,
                'media_slots' => [
                    [
                        'role' => 'background',
                        'slot_key' => 'desktop_background',
                        'label' => 'Hero background desktop',
                        'hint' => 'Background utama hero di desktop.',
                    ],
                    [
                        'role' => 'background',
                        'slot_key' => 'mobile_background',
                        'label' => 'Hero background mobile',
                        'hint' => 'Background hero khusus layar mobile.',
                    ],
                ],
                'gallery' => true,
            ],
            'home.popular_tour' => [
                'display_order' => 10,
                'global_assets' => [self::SITE_LOGO_KEY],
                'media_slots' => [
                    [
                        'role' => 'frame',
                        'slot_key' => 'left_wide',
                        'label' => 'Frame kiri atas',
                        'hint' => 'Gambar landscape besar di kolom kiri atas.',
                    ],
                    [
                        'role' => 'frame',
                        'slot_key' => 'left_small',
                        'label' => 'Frame kiri bawah',
                        'hint' => 'Gambar kecil di kolom kiri bawah.',
                    ],
                    [
                        'role' => 'frame',
                        'slot_key' => 'right_wide',
                        'label' => 'Frame kanan atas',
                        'hint' => 'Gambar landscape besar di kolom kanan atas.',
                    ],
                    [
                        'role' => 'frame',
                        'slot_key' => 'right_small',
                        'label' => 'Frame kanan bawah',
                        'hint' => 'Gambar kecil di kolom kanan bawah.',
                    ],
                ],
                'gallery' => false,
            ],
            'home.popular_products_intro' => [
                'display_order' => 20,
                'media_slots' => [],
                'gallery' => false,
            ],
            'home.manual_ads' => [
                'display_order' => 30,
                'legacy_images' => true,
                'media_slots' => [
                    [
                        'role' => 'frame',
                        'slot_key' => 'main_visual',
                        'label' => 'Gambar promo utama',
                        'hint' => 'Gambar di panel kanan section promo.',
                    ],
                ],
                'gallery' => false,
            ],
            'home.explore_banner' => [
                'display_order' => 60,
                'legacy_images' => true,
                'media_slots' => [
                    [
                        'role' => 'background',
                        'slot_key' => 'desktop_background',
                        'label' => 'Background banner desktop',
                        'hint' => 'Background full-width untuk section explore banner.',
                    ],
                    [
                        'role' => 'background',
                        'slot_key' => 'mobile_background',
                        'label' => 'Background banner mobile',
                        'hint' => 'Opsional untuk background khusus mobile.',
                    ],
                ],
                'gallery' => false,
            ],
            'home.about_journey' => [
                'display_order' => 40,
                'media_slots' => [
                    [
                        'role' => 'frame',
                        'slot_key' => 'main_visual',
                        'label' => 'Journey main visual',
                        'hint' => 'Gambar besar di area kanan section journey.',
                    ],
                    [
                        'role' => 'frame',
                        'slot_key' => 'secondary_visual',
                        'label' => 'Journey secondary visual',
                        'hint' => 'Gambar kecil overlay di bawah kanan section journey.',
                    ],
                ],
                'gallery' => false,
            ],
            'home.categories_intro' => [
                'display_order' => 50,
                'media_slots' => [],
                'gallery' => false,
            ],
            'home.testimonials' => [
                'display_order' => 70,
                'media_slots' => [],
                'gallery' => false,
            ],
            'home.faq' => [
                'display_order' => 80,
                'media_slots' => [
                    [
                        'role' => 'frame',
                        'slot_key' => 'main_visual',
                        'label' => 'FAQ preview image',
                        'hint' => 'Gambar di kolom kiri section FAQ preview.',
                    ],
                ],
                'gallery' => false,
            ],
            'home.footer_cta' => [
                'display_order' => 90,
                'media_slots' => [
                    [
                        'role' => 'frame',
                        'slot_key' => 'main_visual',
                        'label' => 'Footer CTA visual',
                        'hint' => 'Gambar di panel kanan CTA besar sebelum footer.',
                    ],
                ],
                'gallery' => false,
            ],
            'products.index.hero' => [
                'display_order' => 100,
                'legacy_images' => true,
                'media_slots' => [],
                'gallery' => false,
            ],
        ];
    }
}
