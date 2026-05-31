<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Destination;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Lagoi Private Transfer',
            'Bintan City Tour',
            'Mangrove Tour',
            'Snorkeling Adventure',
            'Airport Pickup',
            'Island Adventure',
            'Blue Lake Tour',
            'ATV Experience'
        ]);

        return [
            'category_id' => Category::inRandomOrder()->first()->id,
            'destination_id' => Destination::inRandomOrder()->first()->id,

            'name' => $name,
            'slug' => Str::slug($name . '-' . fake()->unique()->numberBetween(1,9999)),

            'thumbnail' => null,

            'short_description' => fake()->sentence(),

            'description' => fake()->paragraph(5),

            'meeting_point' => fake()->city(),

            'duration' => fake()->randomElement([
                '2 Hours',
                '4 Hours',
                'Half Day',
                'Full Day'
            ]),

            'whatsapp_number' => '628123456789',

            'is_featured' => fake()->boolean(),

            'status' => true,
        ];
    }
}