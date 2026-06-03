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
                'section_key' => 'home.about_journey',
                'label' => 'Dream Your Next Trip',
                'title' => 'Discover When Even You Want To Go',
                'description' => 'Are you tired of the typical tourist destinations and looking to step out of your comfort zone? Adventure travel may be the perfect solution for you! Here are four.',
                'button_text' => 'BOOK YOUR TRIP',
                'button_url' => route('products.index'),
                'extra_data' => [
                    'feature_one_title' => 'Best Travel Agency',
                    'feature_one_text' => 'Thoughtfully arranged Bintan travel experiences for guests who want comfort, quality, and reliable service.',
                    'feature_two_title' => 'Secure Journey With Us',
                    'feature_two_text' => 'Travel with confidence through organized transfers, curated tours, and clear guest support.',
                    'vertical_text' => 'TRAVEL',
                ],
                'sort_order' => 10,
            ],
            [
                'section_key' => 'home.popular_tour',
                'label' => 'Most Popular Tour',
                'title' => "Let's Discover Bintan With Our Excellent Trips",
                'description' => "Whether you're looking for a private island escape, resort transfer, family-friendly activity, or curated Bintan journey, Bintan Prestige provides thoughtfully arranged travel experiences with comfort, quality, and local insight.",
                'button_text' => 'TAKE A TOUR',
                'button_url' => route('products.index'),
                'extra_data' => [
                    'logo_text' => 'LOGO HERE',
                ],
                'sort_order' => 20,
            ],
            [
                'section_key' => 'home.popular_products_intro',
                'label' => 'Most Popular Tour Packages',
                'title' => 'Something Amazing Waiting For You',
                'button_text' => 'View All Package',
                'button_url' => route('products.index'),
                'sort_order' => 30,
            ],
            [
                'section_key' => 'home.categories_intro',
                'label' => 'Next Adventure Destination',
                'title' => 'Popular Travel Categories Available In Bintan',
                'description' => 'Explore Bintan by travel style and discover curated packages that match your journey.',
                'sort_order' => 40,
            ],
            [
                'section_key' => 'home.explore_banner',
                'label' => 'Next Adventure Destination',
                'title' => 'Popular Travel Destinations Available Worldwide',
                'button_text' => 'BOOK YOUR TRIP NOW',
                'button_url' => route('products.index'),
                'extra_data' => [
                    'outline_text' => 'EXPLORE THE WORLD',
                ],
                'sort_order' => 50,
            ],
            [
                'section_key' => 'home.testimonials',
                'label' => 'Clients Feedback About Us',
                'title' => 'See Those Lovely Words From Clients',
                'description' => 'Read what our guests say about their Bintan travel experience with Bintan Prestige.',
                'sort_order' => 60,
            ],
            [
                'section_key' => 'home.footer_cta',
                'label' => 'Explore Tour',
                'title' => 'Plan Your Perfect Bintan Escape With Us',
                'description' => 'Tell us your arrival point, travel date, and preferred experience. Our team will help you choose the right package.',
                'button_text' => 'Chat via WhatsApp',
                'button_url' => 'https://wa.me/?text=Hello%20Bintan%20Prestige%2C%20I%20want%20to%20plan%20a%20Bintan%20trip.',
                'sort_order' => 70,
            ],
        ];

        foreach ($sections as $section) {
            PageSection::updateOrCreate(
                [
                    'page_key' => 'home',
                    'section_key' => $section['section_key'],
                ],
                array_merge(
                    [
                        'is_active' => true,
                        'sort_order' => 0,
                    ],
                    $section,
                    ['page_key' => 'home']
                )
            );
        }
    }
}
