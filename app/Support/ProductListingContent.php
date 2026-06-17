<?php

namespace App\Support;

use App\Models\PageSection;
use Illuminate\Support\Collection;

class ProductListingContent
{
    public static function fromSections(Collection $sections): array
    {
        return [
            'hero' => self::hero($sections->get('products.index.hero')),
            'catalog' => self::catalog($sections->get('products.index.catalog')),
        ];
    }

    private static function hero(?PageSection $section): array
    {
        $title = self::text(
            $section?->title,
            'Explore Tours, Taxi & Activities in Bintan'
        );
        $description = self::text(
            $section?->description,
            'Choose curated island tours, private transfers, and activities with easy WhatsApp booking support.'
        );

        return [
            'key' => 'products.index.hero',
            'label' => self::optionalText($section?->label),
            'title' => $title,
            'subtitle' => self::optionalText($section?->subtitle),
            'description' => $description,
            'image_url' => $section?->image_url,
            'mobile_image_url' => $section?->mobile_image_url,
            'image_alt' => $title,
        ];
    }

    private static function catalog(?PageSection $section): array
    {
        $buttonText = self::optionalText($section?->button_text);
        $buttonUrl = PageSectionCta::safeUrl($section?->button_url);

        return [
            'key' => 'products.index.catalog',
            'title' => self::text($section?->title, 'Available Products'),
            'description' => self::optionalText($section?->description),
            'subtitle' => self::optionalText($section?->subtitle),
            'cta_text' => $buttonText,
            'cta_url' => $buttonUrl,
            'has_cta' => PageSectionCta::hasButton($buttonText, $buttonUrl),
        ];
    }

    private static function text(mixed $value, string $fallback): string
    {
        if (! is_string($value)) {
            return $fallback;
        }

        $value = trim($value);

        return $value !== '' ? $value : $fallback;
    }

    private static function optionalText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
