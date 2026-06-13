<?php

namespace App\Support;

use Illuminate\Support\Collection;

class HomepageContent
{
    public static function fromSections(Collection $sections): array
    {
        return [
            'search' => self::search($sections),
            'popular_products' => self::popularProducts($sections),
            'about_journey' => self::aboutJourney($sections),
            'categories' => self::categories($sections),
            'destinations' => self::destinations($sections),
            'testimonials' => self::testimonials(),
            'faq' => self::faq($sections),
        ];
    }

    private static function search(Collection $sections): array
    {
        $extra = self::extra($sections, 'home.hero');

        return [
            'destination_label' => self::text($extra, 'search_destination_label', 'Destination'),
            'destination_placeholder' => self::text($extra, 'search_destination_placeholder', 'All Destinations'),
            'category_label' => self::text($extra, 'search_category_label', 'Package Type'),
            'category_placeholder' => self::text($extra, 'search_category_placeholder', 'All Categories'),
            'submit_label' => self::text($extra, 'search_submit_label', 'Find Packages'),
            'softcopy' => self::text($extra, 'search_softcopy', 'Discover premium Bintan packages with local assistance, flexible pickup, and simple WhatsApp booking.'),
        ];
    }

    private static function popularProducts(Collection $sections): array
    {
        $section = $sections->get('home.popular_products_intro');
        $extra = self::extra($sections, 'home.popular_products_intro');

        return [
            'view_all_text' => filled($section?->button_text) ? $section->button_text : 'View All Package',
            'view_all_url' => PageSectionCta::safeUrl($section?->button_url, route('products.index')),
            'empty_title' => self::text($extra, 'empty_title', 'Products coming soon'),
            'empty_text' => self::text($extra, 'empty_text', 'Published tour packages will appear here.'),
        ];
    }

    private static function aboutJourney(Collection $sections): array
    {
        $extra = self::extra($sections, 'home.about_journey');

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

    private static function categories(Collection $sections): array
    {
        $extra = self::extra($sections, 'home.categories_intro');

        return [
            'empty_title' => self::text($extra, 'empty_title', 'No categories available yet.'),
        ];
    }

    private static function destinations(Collection $sections): array
    {
        $extra = self::extra($sections, 'home.categories_intro');

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

    private static function faq(Collection $sections): array
    {
        $extra = self::extra($sections, 'home.faq');

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

    private static function extra(Collection $sections, string $sectionKey): array
    {
        $extraData = $sections->get($sectionKey)?->extra_data;

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
