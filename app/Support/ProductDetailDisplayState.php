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
        $highlightItems = self::highlightItems($product);
        $itineraryItems = self::itineraryItems($product);
        $noteItems = self::noteItems($product);
        $faqItems = self::faqItems($product);
        $durationState = self::simpleTextState($product->duration, 'Duration');
        $meetingPointState = self::simpleTextState($product->meeting_point, 'Meeting Point');
        $pickupState = self::pickupState($product);
        $ctaState = self::ctaState($product);
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
            'has_highlights' => $highlightItems->isNotEmpty(),
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
            'ctaState' => $ctaState,
            'descriptionState' => $descriptionState,
            'highlightItems' => $highlightItems,
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
                    ->map(function ($feature) {
                        $value = self::displayText($feature->value);

                        return [
                            'id' => $feature->id,
                            'value' => $value,
                            'has_value' => $value !== null,
                            'sort_order' => $feature->sort_order,
                        ];
                    })
                    ->filter(fn (array $feature) => $feature['has_value'])
                    ->values(),
            ])
            ->values();

        $addons = $product->features
            ->where('label', 'addon')
            ->map(fn ($feature) => self::displayText($feature->value))
            ->filter()
            ->values()
            ->toBase();

        if ($product->pickup_available) {
            $addons = $addons
                ->merge([
                    self::displayText($product->pickup_type) ?: 'Pickup',
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
            ->map(function ($highlight) {
                $title = self::displayText($highlight->title);

                return [
                    'id' => $highlight->id,
                    'icon' => self::displayText($highlight->icon),
                    'title' => $title,
                    'has_title' => $title !== null,
                    'sort_order' => $highlight->sort_order,
                ];
            })
            ->filter(fn (array $highlight) => $highlight['has_title'])
            ->values();
    }

    private static function itineraryItems(Product $product): Collection
    {
        return $product->itineraries
            ->map(function ($itinerary) {
                $time = self::itineraryTimeText($itinerary->time);
                $title = self::displayText($itinerary->title);
                $description = self::displayText($itinerary->description);

                return [
                    'id' => $itinerary->id,
                    'time' => $time,
                    'has_time' => $time !== null,
                    'start_time_raw' => $itinerary->start_time,
                    'title' => $title,
                    'has_title' => $title !== null,
                    'description' => $description,
                    'has_description' => $description !== null,
                    'has_content' => $title !== null || $description !== null,
                    'sort_order' => $itinerary->sort_order,
                ];
            })
            ->filter(fn (array $itinerary) => $itinerary['has_content'])
            ->values();
    }

    private static function noteItems(Product $product): Collection
    {
        return $product->notes
            ->map(function ($note) {
                $title = self::displayText($note->title);
                $description = self::displayText($note->description);

                return [
                    'id' => $note->id,
                    'title' => $title,
                    'has_title' => $title !== null,
                    'description' => $description,
                    'has_description' => $description !== null,
                    'has_content' => $title !== null || $description !== null,
                    'sort_order' => $note->sort_order,
                ];
            })
            ->filter(fn (array $note) => $note['has_content'])
            ->values();
    }

    private static function faqItems(Product $product): Collection
    {
        return $product->faqs
            ->map(function ($faq) {
                $question = self::displayText($faq->question);
                $answer = self::displayText($faq->answer);

                return [
                    'id' => $faq->id,
                    'question' => $question,
                    'has_question' => $question !== null,
                    'answer' => $answer,
                    'has_answer' => $answer !== null,
                    'sort_order' => $faq->sort_order,
                ];
            })
            ->filter(fn (array $faq) => $faq['has_question'])
            ->values();
    }

    private static function pickupState(Product $product): array
    {
        $type = self::displayText($product->pickup_type);
        $note = self::displayText($product->pickup_note);

        return [
            'available' => (bool) $product->pickup_available,
            'type' => $type,
            'label' => $product->pickup_available ? ($type ?: 'Available') : 'Not included',
            'note' => $note,
            'has_note' => $note !== null,
        ];
    }

    private static function itineraryTimeText(?string $value): ?string
    {
        $display = self::displayText($value);

        if ($display === null) {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $display, $matches) === 1) {
            $hour = (int) $matches[1];
            $minute = (int) $matches[2];

            if ($hour > 23 || $minute > 59) {
                return null;
            }
        }

        return $display;
    }

    private static function ctaState(Product $product): array
    {
        $title = self::displayText($product->cta_title);
        $description = self::displayText($product->cta_description);

        return [
            'title' => $title,
            'description' => $description,
            'has_title' => $title !== null,
            'has_description' => $description !== null,
            'has_content' => $title !== null || $description !== null,
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
        $globalNumber = self::normalizePhone(
            BookingCtaSettings::whatsappNumber($bookingCtaSettings, $contactInformation)
        );
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
            : self::productWhatsappMessage($product, 'ask');
        $bookingMessage = $usesGlobalProductCta
            ? BookingCtaSettings::renderMessage($bookingCtaSettings['product_message_template'] ?? '', $context)
            : self::productWhatsappMessage($product, 'booking');

        return [
            'available' => $phone !== '',
            'phone' => $phone !== '' ? $phone : null,
            'source' => $source,
            'chat_label' => $product->cta_button_text ?: ($usesGlobalProductCta ? ($bookingCtaSettings['product_chat_label'] ?? 'Chat via WhatsApp') : 'Chat via WhatsApp'),
            'booking_label' => $product->cta_button_text ?: ($usesGlobalProductCta ? ($bookingCtaSettings['product_booking_label'] ?? 'Book via WhatsApp') : 'Book via WhatsApp'),
            'chat_accessible_label' => 'Chat via WhatsApp about ' . $product->name,
            'booking_accessible_label' => 'Ask about booking ' . $product->name . ' via WhatsApp',
            'booking_note' => 'Our team will confirm availability and booking details on WhatsApp.',
            'chat_message' => $chatMessage,
            'booking_message' => $bookingMessage,
            'chat_url' => $phone !== '' ? self::whatsappUrl($phone, $chatMessage) : null,
            'booking_url' => $phone !== '' ? self::whatsappUrl($phone, $bookingMessage) : null,
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
        $metaTitle = self::displayText($product->meta_title);
        $metaDescription = self::displayText($product->meta_description);
        $shortDescription = self::displayText($product->short_description);
        $canonicalUrl = self::displayText($product->canonical_url);
        $keywords = self::displayText($product->meta_keywords);
        $ogImage = self::displayText($product->og_image);
        $description = $metaDescription
            ?: $shortDescription
            ?: ($descriptionState['excerpt'] ?: 'Plan your Bintan experience with Bintan Prestige.');
        $primaryImage = $mediaState['primary'] ?? null;

        return [
            'title' => $metaTitle ?: $product->name,
            'description' => $description,
            'keywords' => $keywords,
            'canonical' => $canonicalUrl ?: route('products.show', $product),
            'robots' => 'index, follow',
            'image' => $ogImage ? $product->og_image_url : ($primaryImage['url'] ?? null),
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
        $normalized = preg_replace('/\D+/', '', $phone ?? '') ?: '';

        return strlen($normalized) >= 8 ? $normalized : '';
    }

    private static function whatsappUrl(string $phone, string $message): string
    {
        return 'https://wa.me/' . $phone . ($message !== '' ? '?text=' . urlencode($message) : '');
    }

    private static function productWhatsappMessage(Product $product, string $intent): string
    {
        $intro = $intent === 'booking'
            ? 'Hello, I want to ask about booking details for:'
            : 'Hello, I want to ask about:';

        return implode("\n", [
            $intro,
            '',
            ...self::productWhatsappContextLines($product),
        ]);
    }

    private static function productWhatsappContextLines(Product $product): array
    {
        $lines = [
            'Product: ' . $product->name,
        ];

        if ($product->destination?->name) {
            $lines[] = 'Destination: ' . $product->destination->name;
        }

        if (filled($product->duration)) {
            $lines[] = 'Duration: ' . trim((string) $product->duration);
        }

        $lines[] = 'Product URL: ' . route('products.show', $product);

        return $lines;
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
