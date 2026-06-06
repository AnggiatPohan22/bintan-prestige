<?php

namespace App\Support;

class DefaultMediaAssets
{
    public const SETTINGS_GROUP = 'default_media_settings';

    public static function variants(): array
    {
        return [
            [
                'slug' => 'product',
                'key' => 'default_media.product',
                'label' => 'Product placeholder image',
                'hint' => 'Fallback for product cards, product listing, related products, and product detail gallery.',
            ],
            [
                'slug' => 'destination',
                'key' => 'default_media.destination',
                'label' => 'Destination placeholder image',
                'hint' => 'Fallback for destination cards, destination sections, and future destination detail pages.',
            ],
            [
                'slug' => 'section',
                'key' => 'default_media.section',
                'label' => 'Section placeholder image',
                'hint' => 'Fallback for homepage or page sections that do not have configured media.',
            ],
            [
                'slug' => 'hero',
                'key' => 'default_media.hero',
                'label' => 'Hero placeholder image',
                'hint' => 'Large visual fallback for hero sections and visual banners.',
            ],
            [
                'slug' => 'hero_mobile',
                'key' => 'default_media.hero_mobile',
                'label' => 'Mobile hero placeholder image',
                'hint' => 'Mobile-specific hero fallback when a different crop is needed.',
            ],
            [
                'slug' => 'avatar',
                'key' => 'default_media.avatar',
                'label' => 'Avatar placeholder image',
                'hint' => 'Fallback for future authors, guides, team members, testimonials, or user avatars.',
            ],
        ];
    }

    public static function keys(): array
    {
        return array_column(self::variants(), 'key');
    }

    public static function variantForSlug(string $slug): ?array
    {
        return collect(self::variants())->firstWhere('slug', $slug);
    }

    public static function asset($siteAssets, string $slug)
    {
        $variant = self::variantForSlug($slug);

        if (! $variant) {
            return null;
        }

        return $siteAssets[$variant['key']] ?? null;
    }

    public static function url($siteAssets, string $slug): ?string
    {
        return self::asset($siteAssets, $slug)?->url;
    }

    public static function fitOptions(): array
    {
        return [
            'cover' => 'Cover',
            'contain' => 'Contain',
            'fill' => 'Fill',
            'scale-down' => 'Scale down',
        ];
    }

    public static function defaultFit(string $slug): string
    {
        return $slug === 'avatar' ? 'cover' : 'cover';
    }

    public static function fitKey(string $slug): string
    {
        return 'default_media.' . $slug . '.fit';
    }

    public static function valuesFromSettings($settings): array
    {
        return collect(self::variants())
            ->mapWithKeys(function (array $variant) use ($settings) {
                $value = $settings[self::fitKey($variant['slug'])]?->value ?? self::defaultFit($variant['slug']);

                if (! array_key_exists($value, self::fitOptions())) {
                    $value = self::defaultFit($variant['slug']);
                }

                return [$variant['slug'] => ['fit' => $value]];
            })
            ->all();
    }

    public static function fit(array $settings, string $slug): string
    {
        $fit = $settings[$slug]['fit'] ?? self::defaultFit($slug);

        return array_key_exists($fit, self::fitOptions()) ? $fit : self::defaultFit($slug);
    }
}
