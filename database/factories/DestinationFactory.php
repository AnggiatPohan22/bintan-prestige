<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DestinationFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Bintan Island',
            'Lagoi Bay',
            'Tanjung Pinang'
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'image' => null,
            'is_active' => true,
        ];
    }
}