<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title'            => ucwords($title),
            'slug'             => Str::slug($title),
            'status'           => 'draft',
            'template_id'      => null,
            'meta_title'       => null,
            'meta_description' => null,
            'og_image'         => null,
            'sort_order'       => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'published']);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function scheduled(\DateTimeInterface|string|null $publishAt = null): static
    {
        return $this->state([
            'status'     => 'scheduled',
            'publish_at' => $publishAt ?? now()->addHour(),
        ]);
    }
}
