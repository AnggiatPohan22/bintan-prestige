<?php

namespace App\Support;

class SocialMediaLinkSettings
{
    public const GROUP = 'social_media_links';
    public const CUSTOM_LINKS_KEY = 'social.custom_links';

    public static function fields(): array
    {
        return [
            ['key' => 'social.instagram.url', 'slug' => 'instagram', 'label' => 'Instagram', 'abbr' => 'IG', 'default' => ''],
            ['key' => 'social.facebook.url', 'slug' => 'facebook', 'label' => 'Facebook', 'abbr' => 'FB', 'default' => ''],
            ['key' => 'social.tiktok.url', 'slug' => 'tiktok', 'label' => 'TikTok', 'abbr' => 'TT', 'default' => ''],
            ['key' => 'social.youtube.url', 'slug' => 'youtube', 'label' => 'YouTube', 'abbr' => 'YT', 'default' => ''],
            ['key' => 'social.linkedin.url', 'slug' => 'linkedin', 'label' => 'LinkedIn', 'abbr' => 'IN', 'default' => ''],
            ['key' => 'social.tripadvisor.url', 'slug' => 'tripadvisor', 'label' => 'TripAdvisor', 'abbr' => 'TA', 'default' => ''],
            ['key' => 'social.google_review.url', 'slug' => 'google_review', 'label' => 'Google Review', 'abbr' => 'GR', 'default' => ''],
        ];
    }

    public static function valuesFromSettings($settings): array
    {
        $values = collect(self::fields())
            ->mapWithKeys(function (array $field) use ($settings) {
                $setting = $settings[$field['key']] ?? null;

                return [$field['slug'] => $setting?->value ?: $field['default']];
            })
            ->all();

        $customLinks = $settings[self::CUSTOM_LINKS_KEY] ?? null;
        $decodedCustomLinks = json_decode($customLinks?->value ?: '[]', true);

        $values['custom_links'] = is_array($decodedCustomLinks) ? $decodedCustomLinks : [];

        return $values;
    }

    public static function activeLinks(array $values): array
    {
        $presetLinks = collect(self::fields())
            ->map(function (array $field) use ($values) {
                return [
                    ...$field,
                    'url' => $values[$field['slug']] ?? '',
                ];
            })
            ->filter(fn (array $field) => ($field['url'] ?? '') !== '')
            ->values()
            ->all();

        $customLinks = collect($values['custom_links'] ?? [])
            ->map(function (array $link) {
                $label = trim($link['label'] ?? '');

                return [
                    'key' => 'social.custom_links',
                    'slug' => 'custom',
                    'label' => $label,
                    'abbr' => trim($link['abbr'] ?? '') ?: mb_strtoupper(mb_substr($label, 0, 2)),
                    'url' => trim($link['url'] ?? ''),
                ];
            })
            ->filter(fn (array $link) => $link['label'] !== '' && $link['url'] !== '')
            ->values()
            ->all();

        return array_values([...$presetLinks, ...$customLinks]);
    }
}
