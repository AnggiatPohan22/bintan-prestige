<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Arr;

class StructuredDataBuilder
{
    public static function graph(array $context): array
    {
        $settings = $context['structuredDataSettings'] ?? StructuredDataSettings::valuesFromSettings(collect());

        if (! ($settings['enabled'] ?? true)) {
            return [];
        }

        return collect([
            self::businessSchema($context),
            self::websiteSchema($context),
            self::breadcrumbSchema($context),
            self::productSchema($context),
        ])->filter()->values()->all();
    }

    public static function businessSchema(array $context): ?array
    {
        $settings = $context['structuredDataSettings'] ?? [];

        if (! ($settings['enabled'] ?? true) || ! ($settings['business_enabled'] ?? true)) {
            return null;
        }

        $seo = $context['seoDefaultSettings'] ?? [];

        if (($seo['enable_organization_schema'] ?? true) === false) {
            return null;
        }

        $identity = $context['businessIdentity'] ?? [];
        $contact = $context['contactInformation'] ?? [];
        $siteAssets = $context['siteAssets'] ?? collect();
        $socialLinks = $context['activeSocialMediaLinks'] ?? [];
        $siteName = $seo['site_name'] ?: ($identity['brand_name'] ?? config('app.name'));
        $businessName = trim($settings['business_name_override'] ?? '') ?: $siteName;
        $legalName = trim($settings['legal_name_override'] ?? '') ?: ($identity['legal_name'] ?? null);
        $description = trim($settings['description_override'] ?? '') ?: ($identity['short_description'] ?? null);
        $baseUrl = $seo['canonical_base_url'] ?: url('/');
        $logo = (($siteAssets['site.logo'] ?? null)?->url ?: ($siteAssets['site.logo.dark'] ?? null)?->url);
        $openingHours = self::openingHours($settings, $contact);
        $sameAs = collect($socialLinks)->pluck('url')->filter()->values()->all();

        return self::clean([
            '@type' => $settings['business_type'] ?? 'Organization',
            '@id' => rtrim($baseUrl, '/') . '#business',
            'name' => $businessName,
            'legalName' => $legalName,
            'url' => $baseUrl,
            'logo' => $logo,
            'description' => $description,
            'email' => $contact['email'] ?? null,
            'telephone' => $contact['phone'] ?? null,
            'address' => $contact['address'] ?? null,
            'priceRange' => $settings['price_range'] ?? null,
            'currenciesAccepted' => $settings['currencies'] ?? null,
            'areaServed' => $settings['area_served'] ?? null,
            'knowsAbout' => $settings['service_type'] ?? null,
            'openingHours' => $openingHours,
            'sameAs' => $sameAs ?: null,
        ]);
    }

    public static function websiteSchema(array $context): ?array
    {
        $settings = $context['structuredDataSettings'] ?? [];

        if (! ($settings['enabled'] ?? true) || ! ($settings['website_enabled'] ?? true)) {
            return null;
        }

        $identity = $context['businessIdentity'] ?? [];
        $seo = $context['seoDefaultSettings'] ?? [];
        $siteName = $seo['site_name'] ?: ($identity['brand_name'] ?? config('app.name'));
        $baseUrl = $seo['canonical_base_url'] ?: url('/');

        return self::clean([
            '@type' => 'WebSite',
            '@id' => rtrim($baseUrl, '/') . '#website',
            'name' => $siteName,
            'url' => $baseUrl,
            'description' => $identity['short_description'] ?? ($seo['meta_description'] ?? null),
            'publisher' => ['@id' => rtrim($baseUrl, '/') . '#business'],
        ]);
    }

    public static function breadcrumbSchema(array $context): ?array
    {
        $settings = $context['structuredDataSettings'] ?? [];

        if (! ($settings['enabled'] ?? true) || ! ($settings['breadcrumbs_enabled'] ?? true)) {
            return null;
        }

        $items = self::breadcrumbItems($context);

        if (count($items) < 2) {
            return null;
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)
                ->values()
                ->map(fn (array $item, int $index) => self::clean([
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ]))
                ->all(),
        ];
    }

    public static function productSchema(array $context): ?array
    {
        $settings = $context['structuredDataSettings'] ?? [];
        $product = $context['product'] ?? null;

        if (! ($settings['enabled'] ?? true) || ! ($settings['product_enabled'] ?? true) || ! $product instanceof Product) {
            return null;
        }

        $seo = $context['seoDefaultSettings'] ?? [];
        $baseUrl = $seo['canonical_base_url'] ?: url('/');
        $price = $product->idr_price ?: $product->sgd_price;
        $currency = $product->idr_price ? 'IDR' : ($product->sgd_price ? 'SGD' : null);

        return self::clean([
            '@type' => 'Product',
            '@id' => route('products.show', $product) . '#product',
            'name' => $product->name,
            'description' => $product->meta_description ?: $product->short_description,
            'image' => $product->og_image_url ?: $product->thumbnail_url,
            'url' => $product->canonical_url ?: route('products.show', $product),
            'category' => $product->category?->name,
            'brand' => ['@id' => rtrim($baseUrl, '/') . '#business'],
            'offers' => $price ? self::clean([
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => $currency,
                'availability' => 'https://schema.org/InStock',
                'url' => $product->canonical_url ?: route('products.show', $product),
            ]) : null,
            'additionalProperty' => collect([
                ['name' => 'Duration', 'value' => $product->duration],
                ['name' => 'Destination', 'value' => $product->destination?->name],
                ['name' => 'Meeting point', 'value' => $product->meeting_point],
            ])->filter(fn (array $property) => filled($property['value']))->values()->all() ?: null,
        ]);
    }

    public static function jsonLd(array $context): ?string
    {
        $graph = self::graph($context);

        if ($graph === []) {
            return null;
        }

        return json_encode([
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private static function breadcrumbItems(array $context): array
    {
        $product = $context['product'] ?? null;

        if ($product instanceof Product) {
            return [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'Products', 'url' => route('products.index')],
                ['name' => $product->name, 'url' => route('products.show', $product)],
            ];
        }

        if (request()->routeIs('products.index')) {
            return [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'Products', 'url' => route('products.index')],
            ];
        }

        return [];
    }

    private static function openingHours(array $settings, array $contact): ?string
    {
        return match ($settings['opening_hours_source'] ?? 'contact_information') {
            'custom' => trim($settings['custom_opening_hours'] ?? '') ?: null,
            'disabled' => null,
            default => $contact['opening_hours'] ?? null,
        };
    }

    private static function clean(array $data): array
    {
        $cleaned = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = Arr::isAssoc($value)
                    ? self::clean($value)
                    : array_values(array_filter($value, fn ($item) => filled($item)));
            }

            if (filled($value)) {
                $cleaned[$key] = $value;
            }
        }

        return $cleaned;
    }
}
