<?php

namespace App\Support;

class StructuredDataSettings
{
    public const GROUP = 'structured_data_settings';

    public static function fields(): array
    {
        return [
            ['key' => 'structured_data.enabled', 'slug' => 'enabled', 'label' => 'Enable structured data', 'type' => 'boolean', 'default' => '1', 'section' => 'General', 'hint' => 'Master switch for all JSON-LD structured data rendered on the public frontend.'],
            ['key' => 'structured_data.business.enabled', 'slug' => 'business_enabled', 'label' => 'Enable business schema', 'type' => 'boolean', 'default' => '1', 'section' => 'Business Identity Schema', 'hint' => 'Render one global business identity schema without duplicating SEO Default organization schema.'],
            ['key' => 'structured_data.business.type', 'slug' => 'business_type', 'label' => 'Business schema type', 'type' => 'select', 'default' => 'Organization', 'section' => 'Business Identity Schema', 'hint' => 'Choose the most accurate Schema.org type for the business.', 'options' => [
                'Organization' => 'Organization',
                'LocalBusiness' => 'LocalBusiness',
                'TravelAgency' => 'TravelAgency',
                'TouristInformationCenter' => 'Tourist Information Center',
            ]],
            ['key' => 'structured_data.business.name_override', 'slug' => 'business_name_override', 'label' => 'Business name override', 'type' => 'text', 'default' => '', 'section' => 'Business Identity Schema', 'hint' => 'Optional. Empty uses Business Identity brand name.'],
            ['key' => 'structured_data.business.legal_name_override', 'slug' => 'legal_name_override', 'label' => 'Legal name override', 'type' => 'text', 'default' => '', 'section' => 'Business Identity Schema', 'hint' => 'Optional. Empty uses Business Identity legal company name.'],
            ['key' => 'structured_data.business.description_override', 'slug' => 'description_override', 'label' => 'Description override', 'type' => 'textarea', 'default' => '', 'section' => 'Business Identity Schema', 'hint' => 'Optional. Empty uses Business Identity short description.'],
            ['key' => 'structured_data.business.price_range', 'slug' => 'price_range', 'label' => 'Price range', 'type' => 'text', 'default' => '$$', 'section' => 'Business Identity Schema', 'hint' => 'Optional schema hint, for example $, $$, or $$$.'],
            ['key' => 'structured_data.business.currencies', 'slug' => 'currencies', 'label' => 'Accepted currencies', 'type' => 'text', 'default' => 'IDR, SGD', 'section' => 'Business Identity Schema', 'hint' => 'Comma-separated currency codes.'],
            ['key' => 'structured_data.business.area_served', 'slug' => 'area_served', 'label' => 'Area served', 'type' => 'text', 'default' => 'Bintan Island, Indonesia', 'section' => 'Business Identity Schema', 'hint' => 'Primary service area shown to search engines.'],
            ['key' => 'structured_data.business.service_type', 'slug' => 'service_type', 'label' => 'Service type', 'type' => 'text', 'default' => 'Private tours, transfers, and island experiences', 'section' => 'Business Identity Schema', 'hint' => 'Short service category for business offers.'],
            ['key' => 'structured_data.business.opening_hours_source', 'slug' => 'opening_hours_source', 'label' => 'Opening hours source', 'type' => 'select', 'default' => 'contact_information', 'section' => 'Business Identity Schema', 'hint' => 'Choose where schema opening hours text comes from.', 'options' => [
                'contact_information' => 'Contact Information',
                'custom' => 'Custom text',
                'disabled' => 'Do not render opening hours',
            ]],
            ['key' => 'structured_data.business.custom_opening_hours', 'slug' => 'custom_opening_hours', 'label' => 'Custom opening hours', 'type' => 'text', 'default' => '', 'section' => 'Business Identity Schema', 'hint' => 'Used only when Opening hours source is Custom text.'],
            ['key' => 'structured_data.website.enabled', 'slug' => 'website_enabled', 'label' => 'Enable website schema', 'type' => 'boolean', 'default' => '1', 'section' => 'Website Schema', 'hint' => 'Render WebSite JSON-LD using SEO Default site name and canonical base URL.'],
            ['key' => 'structured_data.breadcrumbs.enabled', 'slug' => 'breadcrumbs_enabled', 'label' => 'Enable breadcrumb schema', 'type' => 'boolean', 'default' => '1', 'section' => 'Breadcrumb Schema', 'hint' => 'Render BreadcrumbList JSON-LD for home, product listing, and product detail pages.'],
            ['key' => 'structured_data.product.enabled', 'slug' => 'product_enabled', 'label' => 'Enable product/tour schema', 'type' => 'boolean', 'default' => '1', 'section' => 'Product / Tour Schema', 'hint' => 'Render product schema on product detail pages using product data and global fallback data.'],
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

        foreach (self::booleanSlugs() as $slug) {
            $values[$slug] = filter_var($values[$slug] ?? false, FILTER_VALIDATE_BOOL);
        }

        return $values;
    }

    public static function booleanSlugs(): array
    {
        return collect(self::fields())
            ->filter(fn (array $field) => $field['type'] === 'boolean')
            ->pluck('slug')
            ->all();
    }
}
