<?php

namespace App\Support;

use App\Models\Page;
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
            self::pageSchema($context),
            self::itemListSchema($context),
            self::productSchema($context),
            self::faqPageSchema($context),
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
            '@id' => rtrim($baseUrl, '/').'#business',
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
            '@id' => rtrim($baseUrl, '/').'#website',
            'name' => $siteName,
            'url' => $baseUrl,
            'description' => $identity['short_description'] ?? ($seo['meta_description'] ?? null),
            'inLanguage' => \App\Support\Locales::current(),
            'publisher' => ['@id' => rtrim($baseUrl, '/').'#business'],
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
        $siteName = $seo['site_name'] ?: (($context['businessIdentity'] ?? [])['brand_name'] ?? config('app.name'));
        $canonicalUrl = self::canonicalUrl($context, $product);
        $description = self::productDescription($context, $product);
        $images = self::productImages($context, $product);
        $offers = self::productOffers($context, $canonicalUrl);

        return self::clean([
            '@type' => 'Product',
            '@id' => $canonicalUrl.'#product',
            'name' => $product->name,
            'description' => $description,
            'image' => $images,
            'url' => $canonicalUrl,
            'category' => $product->category?->name,
            'brand' => self::clean([
                '@type' => 'Brand',
                '@id' => rtrim($baseUrl, '/').'#business',
                'name' => $siteName,
            ]),
            'offers' => $offers,
        ]);
    }

    public static function pageSchema(array $context): ?array
    {
        $settings = $context['structuredDataSettings'] ?? [];
        $page = $context['page'] ?? null;

        if (! ($settings['enabled'] ?? true) || ! $page instanceof Page) {
            return null;
        }

        $canonicalUrl = $context['canonicalUrl'] ?? route('pages.show', $page->slug);
        $schemaType = in_array($context['pageSchemaType'] ?? null, ['WebPage', 'Article'], true)
            ? $context['pageSchemaType']
            : 'WebPage';
        $description = self::plainText($context['seoDescription'] ?? null);
        $image = self::plainText($context['seoImage'] ?? null);
        $seo = $context['seoDefaultSettings'] ?? [];
        $baseUrl = $seo['canonical_base_url'] ?: url('/');

        $schema = [
            '@type' => $schemaType,
            '@id' => rtrim($canonicalUrl, '#').'#webpage',
            'url' => $canonicalUrl,
            'name' => self::plainText($context['listingName'] ?? null) ?? $page->title,
            'description' => $description,
            'image' => $image,
            'inLanguage' => \App\Support\Locales::current(),
            'isPartOf' => ['@id' => rtrim($baseUrl, '/').'#website'],
            'datePublished' => $page->created_at?->toAtomString(),
            'dateModified' => $page->updated_at?->toAtomString(),
        ];

        if ($schemaType === 'Article') {
            $schema['headline'] = $page->title;
            $schema['mainEntityOfPage'] = ['@id' => rtrim($canonicalUrl, '#').'#webpage'];
            $schema['publisher'] = ['@id' => rtrim($baseUrl, '/').'#business'];
        }

        return self::clean($schema);
    }

    public static function faqPageSchema(array $context): ?array
    {
        $settings = $context['structuredDataSettings'] ?? [];
        $product = $context['product'] ?? null;
        $page = $context['page'] ?? null;

        if (! ($settings['enabled'] ?? true) || (! $product instanceof Product && ! $page instanceof Page)) {
            return null;
        }

        $faqItems = collect($context['faqItems'] ?? [])
            ->filter(fn ($faq) => is_array($faq))
            ->filter(fn (array $faq) => ($faq['has_question'] ?? false) && ($faq['has_answer'] ?? false))
            ->map(fn (array $faq) => self::clean([
                '@type' => 'Question',
                'name' => self::plainText($faq['question'] ?? null),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => self::plainText($faq['answer'] ?? null),
                ],
            ]))
            ->filter(fn (array $faq) => filled($faq['name'] ?? null) && filled($faq['acceptedAnswer']['text'] ?? null))
            ->values();

        if ($faqItems->isEmpty()) {
            return null;
        }

        return [
            '@type' => 'FAQPage',
            'mainEntity' => $faqItems->all(),
        ];
    }

    public static function itemListSchema(array $context): ?array
    {
        $settings = $context['structuredDataSettings'] ?? [];
        $listingProducts = $context['listingProducts'] ?? null;

        if (! ($settings['enabled'] ?? true) || ! $listingProducts) {
            return null;
        }

        $products = method_exists($listingProducts, 'getCollection')
            ? $listingProducts->getCollection()
            : collect($listingProducts);

        $products = $products
            ->filter(fn ($product) => $product instanceof Product)
            ->values();

        if ($products->isEmpty()) {
            return null;
        }

        $firstPosition = method_exists($listingProducts, 'firstItem')
            ? ($listingProducts->firstItem() ?: 1)
            : 1;
        $canonicalUrl = $context['canonicalUrl'] ?? url()->current();

        return self::clean([
            '@type' => 'ItemList',
            '@id' => rtrim($canonicalUrl, '#').'#itemlist',
            'name' => $context['listingName'] ?? 'Product listing',
            'itemListElement' => $products
                ->map(fn (Product $product, int $index) => self::clean([
                    '@type' => 'ListItem',
                    'position' => $firstPosition + $index,
                    'item' => [
                        '@type' => 'Thing',
                        'name' => $product->name,
                        'url' => route('products.show', $product),
                    ],
                ]))
                ->all(),
        ]);
    }

    public static function jsonLd(array $context): ?string
    {
        $graph = self::graph($context);

        if ($graph === []) {
            return null;
        }

        // JSON_HEX_* escapes <, >, &, ', " so a value containing </script> (e.g. a
        // product name or page title) cannot break out of the <script> block.
        return json_encode([
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private static function breadcrumbItems(array $context): array
    {
        $product = $context['product'] ?? null;
        $breadcrumbState = collect($context['breadcrumbState'] ?? []);

        if ($breadcrumbState->isNotEmpty()) {
            $canonicalUrl = $product instanceof Product
                ? self::canonicalUrl($context, $product)
                : ($context['canonicalUrl'] ?? url()->current());

            return $breadcrumbState
                ->map(fn (array $item) => [
                    'name' => $item['label'] ?? null,
                    'url' => ($item['url'] ?? null) ?: (($item['current'] ?? false) ? $canonicalUrl : null),
                ])
                ->filter(fn (array $item) => filled($item['name']) && filled($item['url']))
                ->values()
                ->all();
        }

        if ($product instanceof Product) {
            $canonicalUrl = self::canonicalUrl($context, $product);

            return [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'Products', 'url' => route('products.index')],
                ['name' => $product->name, 'url' => $canonicalUrl],
            ];
        }

        $page = $context['page'] ?? null;

        if ($page instanceof Page) {
            $canonicalUrl = $context['canonicalUrl'] ?? route('pages.show', $page->slug);

            return [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => $page->title, 'url' => $canonicalUrl],
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

    private static function canonicalUrl(array $context, Product $product): string
    {
        return $context['canonicalUrl']
            ?? self::plainText($product->canonical_url)
            ?? route('products.show', $product);
    }

    private static function productDescription(array $context, Product $product): ?string
    {
        return self::plainText($context['seoDescription'] ?? null)
            ?? self::plainText(($context['metadataState'] ?? [])['description'] ?? null)
            ?? self::plainText($product->meta_description)
            ?? self::plainText($product->short_description)
            ?? self::plainText(($context['descriptionState'] ?? [])['excerpt'] ?? null)
            ?? self::plainText(($context['descriptionState'] ?? [])['plain_text'] ?? null);
    }

    private static function productImages(array $context, Product $product): ?array
    {
        $images = collect();
        $mediaState = $context['mediaState'] ?? [];

        if (filled($product->og_image)) {
            $images->push($product->og_image_url);
        }

        collect($mediaState['items'] ?? [])
            ->reject(fn (array $image) => $image['is_placeholder'] ?? false)
            ->pluck('url')
            ->each(fn (?string $url) => $images->push($url));

        if ($images->isEmpty() && filled($product->thumbnail_url)) {
            $images->push($product->thumbnail_url);
        }

        $images = $images
            ->filter()
            ->unique()
            ->values();

        return $images->isNotEmpty() ? $images->all() : null;
    }

    private static function productOffers(array $context, string $canonicalUrl): ?array
    {
        $priceState = $context['priceState'] ?? [];
        $product = $context['product'] ?? null;

        $idrAmount = self::positivePrice($priceState['idr_amount'] ?? ($product instanceof Product ? $product->idr_price : null));
        $sgdAmount = self::positivePrice($priceState['sgd_amount'] ?? ($product instanceof Product ? $product->sgd_price : null));
        $offers = collect();

        if ($idrAmount !== null) {
            $offers->push(self::offer('IDR', $idrAmount, $canonicalUrl));
        }

        if ($sgdAmount !== null) {
            $offers->push(self::offer('SGD', $sgdAmount, $canonicalUrl));
        }

        return $offers->isNotEmpty() ? $offers->all() : null;
    }

    private static function offer(string $currency, float $amount, string $canonicalUrl): array
    {
        return [
            '@type' => 'Offer',
            'price' => $amount,
            'priceCurrency' => $currency,
            'url' => $canonicalUrl,
        ];
    }

    private static function positivePrice(mixed $amount): ?float
    {
        if (! is_numeric($amount) || (float) $amount <= 0) {
            return null;
        }

        return (float) $amount;
    }

    private static function plainText(mixed $value): ?string
    {
        $text = trim(strip_tags((string) $value));
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return $text !== '' ? $text : null;
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
