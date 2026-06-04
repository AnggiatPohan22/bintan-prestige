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
        return (bool) (self::config()[$sectionKey]['gallery'] ?? true);
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
            'home.manual_ads' => [
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
        ];
    }
}
