<?php

namespace App\Support;

use App\Models\PageSection;
use Illuminate\Support\Collection;

class HomepageSectionData
{
    public static function fromSections(Collection $sections): array
    {
        return collect(PageSectionRegistry::sections()['home'] ?? [])
            ->mapWithKeys(function (array $defaults) use ($sections) {
                $sectionKey = $defaults['section_key'];

                return [
                    $sectionKey => self::fromSection(
                        $sections->get($sectionKey),
                        $defaults
                    ),
                ];
            })
            ->all();
    }

    private static function fromSection(?PageSection $section, array $defaults): array
    {
        $extraData = is_array($section?->extra_data)
            ? $section->extra_data
            : ($defaults['extra_data'] ?? []);

        $buttonText = self::text($section?->button_text, $defaults['button_text'] ?? '');
        $buttonUrl = PageSectionCta::safeUrl($section?->button_url, $defaults['button_url'] ?? null);

        return [
            'key' => $defaults['section_key'],
            'model' => $section,
            'label' => self::text($section?->label, $defaults['label'] ?? ''),
            'title' => self::text($section?->title, $defaults['title'] ?? ''),
            'subtitle' => self::text($section?->subtitle, $defaults['subtitle'] ?? ''),
            'description' => self::text($section?->description, $defaults['description'] ?? ''),
            'button_text' => $buttonText,
            'button_url' => $buttonUrl,
            'has_button' => PageSectionCta::hasButton($buttonText, $buttonUrl),
            'extra' => $extraData,
            'animation' => self::text($extraData['animation'] ?? null, 'ken-burns'),
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
}
