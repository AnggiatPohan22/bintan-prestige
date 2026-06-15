<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Support\Collection;

class ProductDetailDisplayState
{
    public static function make(Product $product, array $globalSettings): array
    {
        $siteAssets = $globalSettings['siteAssets'] ?? collect();
        $defaultMediaSettings = $globalSettings['defaultMediaSettings'] ?? [];
        $businessIdentity = $globalSettings['businessIdentity'] ?? [];
        $contactInformation = $globalSettings['contactInformation'] ?? [];
        $bookingCtaSettings = $globalSettings['bookingCtaSettings'] ?? [];

        $priceState = self::priceState($product);
        $mediaState = self::mediaState($product, $siteAssets, $defaultMediaSettings);
        $descriptionState = self::descriptionState($product);
        $featureState = self::featureState($product);
        $itineraryItems = self::itineraryItems($product);
        $noteItems = self::noteItems($product);
        $faqItems = self::faqItems($product);
        $durationState = self::simpleTextState($product->duration, 'Duration');
        $meetingPointState = self::simpleTextState($product->meeting_point, 'Meeting Point');
        $pickupState = self::pickupState($product);
        $whatsappState = self::whatsappState(
            $product,
            $businessIdentity,
            $contactInformation,
            $bookingCtaSettings
        );
        $breadcrumbState = self::breadcrumbState($product);
        $metadataState = self::metadataState($product, $descriptionState, $mediaState);

        $sectionState = [
            'has_gallery' => $mediaState['has_gallery'],
            'has_highlights' => $product->highlights->isNotEmpty(),
            'has_overview' => $descriptionState['has_description'],
            'has_features' => $featureState['groups']->contains(fn (array $group) => $group['items']->isNotEmpty()),
            'has_itineraries' => $itineraryItems->isNotEmpty(),
            'has_notes' => $noteItems->isNotEmpty(),
            'has_faqs' => $faqItems->isNotEmpty(),
            'has_addons' => $featureState['addons']->isNotEmpty(),
            'has_whatsapp_cta' => $whatsappState['available'],
        ];

        return [
            'priceState' => $priceState,
            'mediaState' => $mediaState,
            'durationState' => $durationState,
            'meetingPointState' => $meetingPointState,
            'pickupState' => $pickupState,
            'descriptionState' => $descriptionState,
            'highlightItems' => self::highlightItems($product),
            'featureGroups' => $featureState['groups'],
            'addonOptions' => $featureState['addons'],
            'itineraryItems' => $itineraryItems,
            'noteItems' => $noteItems,
            'faqItems' => $faqItems,
            'sectionState' => $sectionState,
            'whatsappState' => $whatsappState,
            'breadcrumbState' => $breadcrumbState,
            'metadataState' => $metadataState,
        ];
    }

    private static function priceState(Product $product): array
    {
        $idrAmount = self::positivePrice(
            $product->prices->firstWhere('currency', ProductPrice::CURRENCY_IDR)?->price
        );
        $sgdAmount = self::positivePrice(
            $product->prices->firstWhere('currency', ProductPrice::CURRENCY_SGD)?->price
        );

        $hasIdr = $idrAmount !== null;
        $hasSgd = $sgdAmount !== null;
        $primaryCurrency = $hasIdr ? ProductPrice::CURRENCY_IDR : ($hasSgd ? ProductPrice::CURRENCY_SGD : null);
        $primaryAmount = $hasIdr ? $idrAmount : ($hasSgd ? $sgdAmount : null);

        return [
            'has_idr' => $hasIdr,
            'idr_amount' => $idrAmount,
            'idr_formatted' => $hasIdr ? self::formatPrice(ProductPrice::CURRENCY_IDR, $idrAmount) : null,
            'has_sgd' => $hasSgd,
            'sgd_amount' => $sgdAmount,
            'sgd_formatted' => $hasSgd ? self::formatPrice(ProductPrice::CURRENCY_SGD, $sgdAmount) : null,
            'has_any_price' => $hasIdr || $hasSgd,
            'primary_currency' => $primaryCurrency,
            'primary_amount' => $primaryAmount,
            'primary_formatted' => $primaryCurrency ? self::formatPrice($primaryCurrency, $primaryAmount) : null,
            'secondary_formatted' => $hasIdr && $hasSgd ? self::formatPrice(ProductPrice::CURRENCY_SGD, $sgdAmount) : null,
            'fallback_label' => 'Price on request',
        ];
    }

    private static function mediaState(Product $product, mixed $siteAssets, array $defaultMediaSettings): array
    {
        $fallbackAsset = DefaultMediaAssets::asset($siteAssets, 'product');
        $fallbackFit = DefaultMediaAssets::fit($defaultMediaSettings, 'product');
        $baseAlt = self::imageBaseAlt($product);
        $items = collect();

        if (filled($product->thumbnail)) {
            $items->push([
                'key' => self::normalizeMediaKey($product->thumbnail),
                'url' => $product->thumbnail_url,
                'alt' => $baseAlt,
                'fit' => null,
                'source' => 'thumbnail',
                'is_placeholder' => false,
            ]);
        }

        $galleryNumber = 1;
        foreach ($product->images as $image) {
            if (! filled($image->image)) {
                continue;
            }

            $items->push([
                'key' => self::normalizeMediaKey($image->image),
                'url' => asset('storage/' . ltrim($image->image, '/')),
                'alt' => $baseAlt . ' gallery image ' . $galleryNumber,
                'fit' => null,
                'source' => 'gallery',
                'is_placeholder' => false,
            ]);

            $galleryNumber++;
        }

        if ($items->isEmpty() && $fallbackAsset?->url) {
            $items->push([
                'key' => self::normalizeMediaKey($fallbackAsset->path ?: $fallbackAsset->url),
                'url' => $fallbackAsset->url,
                'alt' => $baseAlt,
                'fit' => $fallbackFit,
                'source' => 'fallback',
                'is_placeholder' => true,
            ]);
        }

        $items = $items
            ->filter(fn (array $item) => filled($item['url']))
            ->unique('key')
            ->values();
        $primary = $items->first();

        return [
            'primary' => $primary,
            'items' => $items,
            'thumbnails' => $items->take(4)->values(),
            'count' => $items->count(),
            'has_gallery' => $items->isNotEmpty(),
            'uses_fallback' => ($primary['source'] ?? null) === 'fallback',
            'fallback_fit' => $fallbackFit,
            'fallback_available' => (bool) $fallbackAsset?->url,
        ];
    }

    private static function simpleTextState(?string $value, string $label): array
    {
        $display = trim((string) $value);

        return [
            'label' => $label,
            'value' => $display !== '' ? $display : null,
            'display' => $display !== '' ? $display : '-',
            'has_value' => $display !== '',
        ];
    }

    private static function descriptionState(Product $product): array
    {
        $shortDescription = trim((string) $product->short_description);
        $description = trim((string) $product->description);

        return [
            'short_description' => $shortDescription !== '' ? $shortDescription : null,
            'description' => $description !== '' ? $description : null,
            'plain_text' => $description !== '' ? $description : null,
            'excerpt' => self::excerpt($description ?: $shortDescription),
            'has_short_description' => $shortDescription !== '',
            'has_description' => $description !== '',
            'render_mode' => 'escaped_plain_text_with_line_breaks',
        ];
    }

    private static function featureState(Product $product): array
    {
        $titles = [
            'included' => 'Included',
            'excluded' => 'Excluded',
            'optional' => 'Optional',
            'addon' => 'Add-ons',
            'important' => 'Important',
        ];

        $groups = collect($titles)
            ->map(fn (string $title, string $label) => [
                'label' => $label,
                'title' => $title,
                'items' => $product->features
                    ->where('label', $label)
                    ->map(fn ($feature) => [
                        'id' => $feature->id,
                        'value' => $feature->value,
                        'sort_order' => $feature->sort_order,
                    ])
                    ->values(),
            ])
            ->values();

        $addons = $product->features
            ->where('label', 'addon')
            ->pluck('value')
            ->filter()
            ->values();

        if ($product->pickup_available) {
            $addons = $addons
                ->merge([
                    $product->pickup_type ?: 'Pickup',
                    'Drop off',
                ])
                ->filter()
                ->unique()
                ->values();
        }

        return [
            'groups' => $groups,
            'addons' => $addons,
        ];
    }

    private static function highlightItems(Product $product): Collection
    {
        return $product->highlights
            ->map(fn ($highlight) => [
                'id' => $highlight->id,
                'icon' => $highlight->icon,
                'title' => $highlight->title,
                'sort_order' => $highlight->sort_order,
            ])
            ->values();
    }

    private static function itineraryItems(Product $product): Collection
    {
        return $product->itineraries
            ->map(fn ($itinerary) => [
                'id' => $itinerary->id,
                'time' => self::displayText($itinerary->time),
                'has_time' => filled($itinerary->time),
                'start_time_raw' => $itinerary->start_time,
                'title' => $itinerary->title,
                'description' => self::displayText($itinerary->description),
                'has_description' => filled($itinerary->description),
                'sort_order' => $itinerary->sort_order,
            ])
            ->values();
    }

    private static function noteItems(Product $product): Collection
    {
        return $product->notes
            ->map(fn ($note) => [
                'id' => $note->id,
                'title' => self::displayText($note->title),
                'has_title' => filled($note->title),
                'description' => $note->description,
                'sort_order' => $note->sort_order,
            ])
            ->values();
    }

    private static function faqItems(Product $product): Collection
    {
        return $product->faqs
            ->map(fn ($faq) => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'sort_order' => $faq->sort_order,
            ])
            ->values();
    }

    private static function pickupState(Product $product): array
    {
        return [
            'available' => (bool) $product->pickup_available,
            'type' => $product->pickup_type,
            'label' => $product->pickup_available ? ($product->pickup_type ?: 'Available') : 'Not included',
            'note' => self::displayText($product->pickup_note),
            'has_note' => filled($product->pickup_note),
        ];
    }

    private static function whatsappState(
        Product $product,
        array $businessIdentity,
        array $contactInformation,
        array $bookingCtaSettings
    ): array {
        $usesGlobalProductCta = BookingCtaSettings::isEnabledFor($bookingCtaSettings, 'product');
        $productNumber = self::normalizePhone($product->whatsapp_number);
        $globalNumber = BookingCtaSettings::whatsappNumber($bookingCtaSettings, $contactInformation);
        $phone = $productNumber ?: $globalNumber;
        $source = $productNumber ? 'product' : ($globalNumber ? 'global' : 'none');
        $context = [
            'site_name' => $businessIdentity['brand_name'] ?? config('app.name'),
            'product_name' => $product->name,
            'product_url' => route('products.show', $product),
            'page_url' => url()->current(),
        ];
        $chatMessage = $usesGlobalProductCta
            ? BookingCtaSettings::renderMessage($bookingCtaSettings['product_message_template'] ?? '', $context)
            : "Hello, I want to ask about:\n\n" . $product->name . "\n" . route('products.show', $product);
        $bookingMessage = $usesGlobalProductCta
            ? BookingCtaSettings::renderMessage($bookingCtaSettings['product_message_template'] ?? '', $context)
            : 'Hello, I want to book ' . $product->name;

        return [
            'available' => $phone !== '',
            'phone' => $phone !== '' ? $phone : null,
            'source' => $source,
            'chat_label' => $product->cta_button_text ?: ($usesGlobalProductCta ? ($bookingCtaSettings['product_chat_label'] ?? 'Chat via WhatsApp') : 'Chat via WhatsApp'),
            'booking_label' => $product->cta_button_text ?: ($usesGlobalProductCta ? ($bookingCtaSettings['product_booking_label'] ?? 'Book via WhatsApp') : 'Book via WhatsApp'),
            'chat_message' => $chatMessage,
            'booking_message' => $bookingMessage,
            'chat_url' => $phone !== '' ? self::whatsappUrl($phone, $chatMessage) : null,
            'product_url' => route('products.show', $product),
        ];
    }

    private static function breadcrumbState(Product $product): Collection
    {
        return collect([
            ['label' => 'Home', 'url' => route('home'), 'current' => false],
            ['label' => 'Products', 'url' => route('products.index'), 'current' => false],
            ['label' => $product->name, 'url' => null, 'current' => true],
        ]);
    }

    private static function metadataState(Product $product, array $descriptionState, array $mediaState): array
    {
        $description = $product->meta_description
            ?: $product->short_description
            ?: ($descriptionState['excerpt'] ?: 'Plan your Bintan experience with Bintan Prestige.');
        $primaryImage = $mediaState['primary'] ?? null;

        return [
            'title' => $product->meta_title ?: $product->name,
            'description' => $description,
            'keywords' => $product->meta_keywords,
            'canonical' => $product->canonical_url ?: route('products.show', $product),
            'robots' => 'index, follow',
            'image' => $product->og_image_url ?: ($primaryImage['url'] ?? null),
            'image_alt' => $primaryImage['alt'] ?? $product->name,
            'social_share_type' => 'product',
        ];
    }

    private static function positivePrice(mixed $amount): ?float
    {
        if (! is_numeric($amount) || (float) $amount <= 0) {
            return null;
        }

        return (float) $amount;
    }

    private static function formatPrice(string $currency, float $amount): string
    {
        return $currency === ProductPrice::CURRENCY_IDR
            ? 'Rp ' . number_format($amount, 0, ',', '.')
            : 'SGD ' . number_format($amount, 0);
    }

    private static function imageBaseAlt(Product $product): string
    {
        $destination = $product->destination?->name;

        return $destination
            ? $product->name . ' in ' . $destination
            : $product->name;
    }

    private static function normalizeMediaKey(?string $path): string
    {
        return strtolower(trim(str_replace('\\', '/', (string) $path), " \t\n\r\0\x0B/"));
    }

    private static function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', $phone ?? '') ?: '';
    }

    private static function whatsappUrl(string $phone, string $message): string
    {
        return 'https://wa.me/' . $phone . ($message !== '' ? '?text=' . urlencode($message) : '');
    }

    private static function displayText(?string $value): ?string
    {
        $display = trim((string) $value);

        return $display !== '' ? $display : null;
    }

    private static function excerpt(?string $value, int $limit = 155): ?string
    {
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)));

        if ($plain === '') {
            return null;
        }

        if (mb_strlen($plain) <= $limit) {
            return $plain;
        }

        return rtrim(mb_substr($plain, 0, $limit - 3)) . '...';
    }
}
