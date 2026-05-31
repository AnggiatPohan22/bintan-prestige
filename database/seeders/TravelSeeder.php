<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\ProductFeature;
use App\Models\ProductFaq;
use App\Models\ProductHighlight;
use App\Models\ProductImage;
use App\Models\ProductItinerary;
use App\Models\ProductNote;
use App\Models\ProductPrice;
use Illuminate\Database\Seeder;

class TravelSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        */

        Category::query()->delete();

        Category::insert([
            [
                'name' => 'Tour Package',
                'slug' => 'tour-package',
                'description' => 'Tour package category',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Activity',
                'slug' => 'activity',
                'description' => 'Activity category',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Taxi',
                'slug' => 'taxi',
                'description' => 'Taxi category',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Destination
        |--------------------------------------------------------------------------
        */

        Destination::query()->delete();

        Destination::insert([
            [
                'name' => 'Bintan Island',
                'slug' => 'bintan-island',
                'description' => 'Main island destination',
                'image' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Lagoi',
                'slug' => 'lagoi',
                'description' => 'Luxury resort area in Bintan',
                'image' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tanjung Pinang',
                'slug' => 'tanjung-pinang',
                'description' => 'City destination in Bintan',
                'image' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */

        $products = Product::factory(30)->create();

        foreach ($products as $product) {

            /*
            |--------------------------------------------------------------------------
            | Product Prices
            |--------------------------------------------------------------------------
            */

            ProductPrice::create([
                'product_id' => $product->id,
                'currency' => 'IDR',
                'price' => fake()->numberBetween(
                    150000,
                    3000000
                ),
            ]);

            ProductPrice::create([
                'product_id' => $product->id,
                'currency' => 'SGD',
                'price' => fake()->numberBetween(
                    20,
                    300
                ),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Product Features
            |--------------------------------------------------------------------------
            */

            foreach (range(1, 4) as $index) {

                ProductFeature::create([
                    'product_id' => $product->id,

                    'label' => fake()->randomElement([
                        'included',
                        'excluded',
                        'optional',
                        'addon',
                    ]),

                    'value' => fake()->randomElement([
                        'Private Transfer',
                        'Professional Guide',
                        'Lunch Included',
                        'Hotel Pickup',
                        'Air Conditioned Vehicle',
                        'Entrance Ticket',
                        'Snorkeling Equipment',
                    ]),

                    'sort_order' => $index,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Product Highlights
            |--------------------------------------------------------------------------
            */

            foreach (range(1, 3) as $index) {

                ProductHighlight::create([
                    'product_id' => $product->id,

                    'title' => fake()->randomElement([
                        'Professional Tour Guide',
                        'Hotel Pickup Included',
                        'Private Transport',
                        'Best Instagram Spot',
                        'Family Friendly Activity',
                    ]),

                    'icon' => null,

                    'sort_order' => $index,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Product Itinerary
            |--------------------------------------------------------------------------
            */

            ProductItinerary::insert([
                [
                    'product_id' => $product->id,
                    'time' => '08:00',
                    'title' => 'Hotel Pickup',
                    'description' =>
                        'Pickup from hotel lobby',
                    'start_time' => 800,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'product_id' => $product->id,
                    'time' => '10:00',
                    'title' => 'Activity Start',
                    'description' =>
                        'Tour activity begin',
                    'start_time' => 1000,
                    'sort_order' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Product FAQ
            |--------------------------------------------------------------------------
            */

            ProductFaq::create([
                'product_id' => $product->id,
                'question' => 'Is hotel pickup included?',
                'answer' => 'Yes, hotel pickup included.',
                'sort_order' => 1,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Product Notes
            |--------------------------------------------------------------------------
            */

            ProductNote::create([
                'product_id' => $product->id,
                'title' => 'Important Information',
                'description' =>
                    'Bring sunscreen and comfortable clothes.',
                'sort_order' => 1,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Product Images
            |--------------------------------------------------------------------------
            */

            ProductImage::create([
                'product_id' => $product->id,
                'image' => 'products/demo.webp',
                'sort_order' => 1,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Booking
        |--------------------------------------------------------------------------
        */

        Booking::factory(15)
            ->create()
            ->each(function ($booking) {

                BookingItem::create([
                    'booking_id' => $booking->id,

                    'product_id' => Product::inRandomOrder()
                        ->first()
                        ->id,

                    'qty' => rand(1, 5),

                    'price' => rand(
                        100000,
                        500000
                    ),

                    'currency' => 'IDR',
                ]);
            });
    }
}