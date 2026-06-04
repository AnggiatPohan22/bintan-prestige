<?php

namespace App\Support;

class BusinessIdentitySettings
{
    public const GROUP = 'business_identity';

    public static function fields(): array
    {
        return [
            [
                'key' => 'business.identity.brand_name',
                'slug' => 'brand_name',
                'label' => 'Brand name',
                'type' => 'text',
                'default' => 'Bintan Prestige',
                'hint' => 'Public-facing website and brand name.',
            ],
            [
                'key' => 'business.identity.legal_name',
                'slug' => 'legal_name',
                'label' => 'Legal company name',
                'type' => 'text',
                'default' => 'Bintan Prestige',
                'hint' => 'Formal company name for future legal, invoice, or schema usage.',
            ],
            [
                'key' => 'business.identity.tagline',
                'slug' => 'tagline',
                'label' => 'Tagline',
                'type' => 'text',
                'default' => 'Premium island travel experiences.',
                'hint' => 'Short brand line for metadata and future content blocks.',
            ],
            [
                'key' => 'business.identity.short_description',
                'slug' => 'short_description',
                'label' => 'Short description',
                'type' => 'textarea',
                'default' => 'Luxury Bintan tours, private transfers, and curated island experiences arranged with comfort, quality, and simple WhatsApp booking.',
                'hint' => 'Reusable brand description for footer, default metadata, and future structured data.',
            ],
            [
                'key' => 'business.identity.business_type',
                'slug' => 'business_type',
                'label' => 'Business type',
                'type' => 'text',
                'default' => 'Travel Agency',
                'hint' => 'Business category for future schema and admin context.',
            ],
            [
                'key' => 'business.identity.location_label',
                'slug' => 'location_label',
                'label' => 'Location label',
                'type' => 'text',
                'default' => 'Bintan Island, Indonesia',
                'hint' => 'Short location text shown in footer and future contact areas.',
            ],
            [
                'key' => 'business.identity.copyright_text',
                'slug' => 'copyright_text',
                'label' => 'Copyright text',
                'type' => 'text',
                'default' => 'All rights reserved.',
                'hint' => 'Footer copyright suffix after the year and brand name.',
            ],
        ];
    }

    public static function valuesFromSettings($settings): array
    {
        return collect(self::fields())
            ->mapWithKeys(function (array $field) use ($settings) {
                $setting = $settings[$field['key']] ?? null;

                return [$field['slug'] => $setting?->value ?: $field['default']];
            })
            ->all();
    }
}
