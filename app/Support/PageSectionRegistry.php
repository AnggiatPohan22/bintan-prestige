<?php

namespace App\Support;

use App\Models\PageSection;
use Illuminate\Support\Collection;

class PageSectionRegistry
{
    public static function pages(): array
    {
        return [
            'home' => [
                'label' => 'Home',
                'description' => 'Homepage sections shown on the public landing page.',
                'group' => 'Frontend',
                'order' => 0,
            ],
            'products.index' => [
                'label' => 'Product Listing',
                'description' => 'Product catalog hero, listing area, and filter/sort modal sections.',
                'group' => 'Product',
                'order' => 10,
            ],
            'products.show' => [
                'label' => 'Product Detail',
                'description' => 'Product detail gallery, summary, content panels, and booking form sections.',
                'group' => 'Product',
                'order' => 20,
            ],
        ];
    }

    public static function sections(): array
    {
        return [
            'products.index' => [
                [
                    'section_key' => 'products.index.hero',
                    'label' => 'Product Listing Hero',
                    'title' => 'Explore Tours, Taxi & Activities in Bintan',
                    'description' => 'Catalog hero section above the product listing.',
                    'sort_order' => 0,
                ],
                [
                    'section_key' => 'products.index.catalog',
                    'label' => 'Product Catalog',
                    'title' => 'Available Products',
                    'description' => 'Product grid, toolbar, filters, sorting, pagination, and empty state.',
                    'sort_order' => 10,
                ],
                [
                    'section_key' => 'products.index.filter_modal',
                    'label' => 'Product Filter Modal',
                    'title' => 'Filter',
                    'description' => 'Modal controls for filtering product listing results.',
                    'sort_order' => 20,
                ],
                [
                    'section_key' => 'products.index.sort_modal',
                    'label' => 'Product Sort Modal',
                    'title' => 'Sort packages',
                    'description' => 'Modal controls for sorting product listing results.',
                    'sort_order' => 30,
                ],
            ],
            'products.show' => [
                [
                    'section_key' => 'products.show.hero',
                    'label' => 'Product Detail Hero',
                    'title' => 'Product Detail',
                    'description' => 'Top wrapper for gallery and product summary.',
                    'sort_order' => 0,
                ],
                [
                    'section_key' => 'products.show.gallery',
                    'label' => 'Product Gallery',
                    'title' => 'Product Gallery',
                    'description' => 'Main product image and thumbnail list.',
                    'sort_order' => 10,
                ],
                [
                    'section_key' => 'products.show.summary',
                    'label' => 'Product Summary',
                    'title' => 'Product Summary',
                    'description' => 'Product badges, title, short description, metadata, price, and chat CTA.',
                    'sort_order' => 20,
                ],
                [
                    'section_key' => 'products.show.content',
                    'label' => 'Product Content',
                    'title' => 'Product Content',
                    'description' => 'Wrapper for lower product detail panels.',
                    'sort_order' => 30,
                ],
                [
                    'section_key' => 'products.show.overview',
                    'label' => 'Overview',
                    'title' => 'Overview',
                    'description' => 'Long product description panel.',
                    'sort_order' => 40,
                ],
                [
                    'section_key' => 'products.show.features',
                    'label' => 'Features',
                    'title' => "What's Included",
                    'description' => 'Included, excluded, optional, add-on, and important product features.',
                    'sort_order' => 50,
                ],
                [
                    'section_key' => 'products.show.itinerary',
                    'label' => 'Itinerary',
                    'title' => 'Itinerary',
                    'description' => 'Product itinerary timeline panel.',
                    'sort_order' => 60,
                ],
                [
                    'section_key' => 'products.show.notes',
                    'label' => 'Important Notes',
                    'title' => 'Important Notes',
                    'description' => 'Important product notes panel.',
                    'sort_order' => 70,
                ],
                [
                    'section_key' => 'products.show.faq',
                    'label' => 'FAQ',
                    'title' => 'FAQ',
                    'description' => 'Product FAQ accordion panel.',
                    'sort_order' => 80,
                ],
                [
                    'section_key' => 'products.show.booking',
                    'label' => 'Booking Information',
                    'title' => 'Booking Information',
                    'description' => 'Sticky booking information form and WhatsApp booking CTA.',
                    'sort_order' => 90,
                ],
            ],
        ];
    }

    public static function syncRegisteredSections(): void
    {
        foreach (self::sections() as $pageKey => $sections) {
            foreach ($sections as $section) {
                PageSection::query()->firstOrCreate(
                    [
                        'page_key' => $pageKey,
                        'section_key' => $section['section_key'],
                    ],
                    $section + [
                        'page_key' => $pageKey,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    public static function pageOptions(Collection $existingPageKeys): Collection
    {
        return collect(self::pages())
            ->map(fn (array $page, string $key) => $page + [
                'key' => $key,
                'exists' => $existingPageKeys->contains($key),
            ])
            ->merge(
                $existingPageKeys
                    ->reject(fn (string $key) => array_key_exists($key, self::pages()))
                    ->mapWithKeys(fn (string $key) => [
                        $key => [
                            'key' => $key,
                            'label' => self::labelFromKey($key),
                            'description' => 'Detected from existing page section records.',
                            'group' => 'Custom',
                            'order' => 999,
                            'exists' => true,
                        ],
                    ])
            )
            ->sortBy([['order', 'asc'], ['label', 'asc']])
            ->values();
    }

    public static function registeredSectionKeys(): array
    {
        return collect(self::sections())->flatten(1)->pluck('section_key')->all();
    }

    public static function supportsImageInput(?string $sectionKey): bool
    {
        return HomepageSectionMedia::allowsGallery($sectionKey)
            || HomepageSectionMedia::supportsLegacyImages($sectionKey)
            || count(HomepageSectionMedia::slotsFor($sectionKey)) > 0;
    }

    public static function labelFromKey(string $key): string
    {
        return ucwords(str_replace(['.', '_', '-'], ' ', $key));
    }
}
