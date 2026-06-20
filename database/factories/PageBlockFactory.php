<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

class PageBlockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'page_id'    => Page::factory(),
            'block_type' => fake()->randomElement(['hero', 'text', 'image', 'gallery']),
            'label'      => fake()->words(2, true),
            'data'       => [],
            'sort_order' => 0,
            'is_visible' => true,
        ];
    }
}
