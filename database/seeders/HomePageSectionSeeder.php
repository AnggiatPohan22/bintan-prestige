<?php

namespace Database\Seeders;

use App\Models\PageSection;
use Illuminate\Database\Seeder;

class HomePageSectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            [
                'section_key' => 'home.hero',
                'label' => 'Luxury Bintan Travel',
                'title' => 'BINTAN PRESTIGE',
                'description' => 'Private tours, island transfers, and curated experiences designed for a smoother premium escape.',
                'sort_order' => 0,
                'extra_data' => ['animation' => 'ken-burns'],
            ],
            [
                'section_key' => 'home.about_journey',
                'label' => 'Dream Your Next Trip',
                'title' => 'Discover When Even You Want To Go',
                'description' => 'Are you tired of the typical tourist destinations and looking to step out of your comfort zone? Adventure travel may be the perfect solution for you! Here are four.',
                'button_text' => 'BOOK YOUR TRIP',
                'button_url' => '/products',
                'sort_order' => 10,
            ],
            [
                'section_key' => 'home.popular_tour',
                'label' => 'Most Popular Tour',
                'title' => "Let's Discover Bintan With Our Excellent Trips",
                'description' => "Whether you're looking for a private island escape, resort transfer, family-friendly activity, or curated Bintan journey, Bintan Prestige provides thoughtfully arranged travel experiences with comfort, quality, and local insight.",
                'button_text' => 'TAKE A TOUR',
                'button_url' => '/products',
                'sort_order' => 20,
            ],
            [
                'section_key' => 'home.popular_products_intro',
                'label' => 'Most Popular Tour Packages',
                'title' => 'Something Amazing Waiting For You',
                'sort_order' => 30,
            ],
            [
                'section_key' => 'home.categories_intro',
                'label' => 'Choose Your Experience',
                'title' => 'Explore Bintan By Category',
                'sort_order' => 40,
            ],
            [
                'section_key' => 'home.manual_ads',
                'label' => 'Special Offer',
                'title' => 'Plan Your Bintan Journey With Us',
                'sort_order' => 50,
            ],
            [
                'section_key' => 'home.explore_banner',
                'label' => 'Next Adventure Destination',
                'title' => 'Popular Travel Destinations Available Worldwide',
                'button_text' => 'BOOK YOUR TRIP NOW',
                'button_url' => '/products',
                'sort_order' => 60,
                'extra_data' => ['overlay_title' => 'EXPLORE THE WORLD'],
            ],
            [
                'section_key' => 'home.testimonials',
                'label' => 'Clients Feedback About Us',
                'title' => 'See Those Lovely Words From Clients',
                'description' => 'Read what our guests say about their Bintan travel experience with Bintan Prestige.',
                'sort_order' => 70,
            ],
            [
                'section_key' => 'home.footer_cta',
                'label' => 'Ready for Bintan?',
                'title' => 'Start planning your premium island experience.',
                'sort_order' => 80,
            ],
        ];

        foreach ($sections as $section) {
            PageSection::updateOrCreate(
                [
                    'page_key' => 'home',
                    'section_key' => $section['section_key'],
                ],
                $section + [
                    'page_key' => 'home',
                    'is_active' => true,
                ]
            );
        }
    }
}
