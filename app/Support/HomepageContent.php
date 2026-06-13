<?php

namespace App\Support;

use App\Models\Destination;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class HomepageContent
{
    public static function fromSections(Collection $sections): array
    {
        $sectionData = HomepageSectionData::fromSections($sections);

        return [
            'sections' => $sectionData,
            'search' => self::search($sectionData),
            'popular_products' => self::popularProducts($sectionData),
            'about_journey' => self::aboutJourney($sectionData),
            'categories' => self::categories($sectionData),
            'destinations' => self::destinations($sectionData),
            'testimonials' => self::testimonials(),
            'faq' => self::faq($sectionData),
        ];
    }

    public static function destinationCards(Collection $destinations): Collection
    {
        return $destinations
            ->take(4)
            ->map(fn (Destination $destination) => self::destinationCard($destination))
            ->values();
    }

    public static function faqItems(Collection $faqs, array $homepageContent): Collection
    {
        if ($faqs->isNotEmpty()) {
            return $faqs
                ->map(fn ($faq) => [
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                ])
                ->values();
        }

        return collect($homepageContent['faq']['fallback_items'] ?? [])
            ->filter(fn ($faq) => is_array($faq))
            ->map(fn (array $faq) => [
                'question' => trim((string) ($faq['question'] ?? '')),
                'answer' => trim((string) ($faq['answer'] ?? '')),
            ])
            ->filter(fn (array $faq) => filled($faq['question']) || filled($faq['answer']))
            ->values();
    }

    private static function search(array $sectionData): array
    {
        $extra = self::extra($sectionData, 'home.hero');

        return [
            'destination_label' => self::text($extra, 'search_destination_label', 'Destination'),
            'destination_placeholder' => self::text($extra, 'search_destination_placeholder', 'All Destinations'),
            'category_label' => self::text($extra, 'search_category_label', 'Package Type'),
            'category_placeholder' => self::text($extra, 'search_category_placeholder', 'All Categories'),
            'submit_label' => self::text($extra, 'search_submit_label', 'Find Packages'),
            'softcopy' => self::text($extra, 'search_softcopy', 'Discover premium Bintan packages with local assistance, flexible pickup, and simple WhatsApp booking.'),
        ];
    }

    private static function popularProducts(array $sectionData): array
    {
        $section = $sectionData['home.popular_products_intro'] ?? [];
        $extra = self::extra($sectionData, 'home.popular_products_intro');

        return [
            'view_all_text' => filled($section['button_text'] ?? null) ? $section['button_text'] : 'View All Package',
            'view_all_url' => PageSectionCta::safeUrl($section['button_url'] ?? null, route('products.index')),
            'empty_title' => self::text($extra, 'empty_title', 'Products coming soon'),
            'empty_text' => self::text($extra, 'empty_text', 'Published tour packages will appear here.'),
        ];
    }

    private static function aboutJourney(array $sectionData): array
    {
        $extra = self::extra($sectionData, 'home.about_journey');

        return [
            'features' => self::items($extra, 'features', [
                [
                    'title' => 'Best Travel Agency',
                    'text' => 'Thoughtfully arranged Bintan travel experiences for guests who want comfort, quality, and reliable service.',
                    'icon' => 'shield',
                ],
                [
                    'title' => 'Secure Journey With Us',
                    'text' => 'Travel with confidence through organized transfers, curated tours, and clear guest support.',
                    'icon' => 'support',
                ],
            ]),
        ];
    }

    private static function categories(array $sectionData): array
    {
        $extra = self::extra($sectionData, 'home.categories_intro');

        return [
            'empty_title' => self::text($extra, 'empty_title', 'No categories available yet.'),
        ];
    }

    private static function destinations(array $sectionData): array
    {
        $extra = self::extra($sectionData, 'home.categories_intro');

        return [
            'empty_title' => self::text($extra, 'empty_title', 'No destinations available yet.'),
        ];
    }

    private static function testimonials(): array
    {
        return [
            'fallback_items' => [
                [
                    'name' => 'Floyd Miles',
                    'role' => 'Guest Traveller',
                    'text' => 'Our Bintan trip was smooth from pickup to the tour arrangement. Everything felt organized, comfortable, and professional.',
                    'rating' => 4,
                    'initials' => 'FM',
                ],
                [
                    'name' => 'Esther Howard',
                    'role' => 'Family Traveller',
                    'text' => 'The service was very helpful and easy to communicate with. The team made our island activity feel simple and enjoyable.',
                    'rating' => 4,
                    'initials' => 'EH',
                ],
                [
                    'name' => 'Albert Flores',
                    'role' => 'Resort Guest',
                    'text' => 'Great experience with clear booking support and reliable transfer service. Highly recommended for guests visiting Bintan.',
                    'rating' => 4,
                    'initials' => 'AF',
                ],
            ],
        ];
    }

    private static function faq(array $sectionData): array
    {
        $extra = self::extra($sectionData, 'home.faq');

        return [
            'fallback_items' => self::items($extra, 'fallback_items', [
                [
                    'question' => 'Can I arrange pickup from ferry terminal or resort?',
                    'answer' => 'Yes, pickup options can be arranged depending on package, meeting point, and route availability.',
                ],
                [
                    'question' => 'How do I confirm a booking?',
                    'answer' => 'Choose a package and contact us through WhatsApp to confirm date, guests, pickup, and availability.',
                ],
                [
                    'question' => 'Can packages be customized?',
                    'answer' => 'Many tours and transfers can be adjusted for timing, route, or pickup location.',
                ],
            ]),
        ];
    }

    private static function destinationCard(Destination $destination): array
    {
        $packageCount = (int) ($destination->products_count ?? 0);
        $name = $destination->name ?: 'Bintan Destination';
        $description = trim((string) $destination->description);

        return [
            'id' => $destination->id,
            'slug' => $destination->slug,
            'name' => $name,
            'description' => $description,
            'url' => route('products.index', ['destination' => [$destination->id]]),
            'aria_label' => $description !== ''
                ? $name . ' - ' . Str::limit($description, 90)
                : 'View packages for ' . $name,
            'image_url' => $destination->image ? asset('storage/' . $destination->image) : null,
            'products_count' => $packageCount,
            'package_label' => str_pad($packageCount, 2, '0', STR_PAD_LEFT) . ' ' . Str::plural('Package', $packageCount),
        ];
    }

    private static function extra(array $sectionData, string $sectionKey): array
    {
        $extraData = $sectionData[$sectionKey]['extra'] ?? [];

        return is_array($extraData) ? $extraData : [];
    }

    private static function text(array $extra, string $key, string $fallback): string
    {
        $value = $extra[$key] ?? null;

        return is_string($value) && trim($value) !== ''
            ? trim($value)
            : $fallback;
    }

    private static function items(array $extra, string $key, array $fallback): array
    {
        if (! array_key_exists($key, $extra)) {
            return $fallback;
        }

        if (! is_array($extra[$key])) {
            return $fallback;
        }

        return collect($extra[$key])
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => collect($item)
                ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                ->all())
            ->filter(fn (array $item) => collect($item)->filter(fn ($value) => filled($value))->isNotEmpty())
            ->values()
            ->all();
    }
}
