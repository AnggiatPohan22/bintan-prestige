<?php

namespace Database\Factories;

use App\Models\Plugin;
use Illuminate\Database\Eloquent\Factories\Factory;

class PluginFactory extends Factory
{
    protected $model = Plugin::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name'         => ucwords($name),
            'slug'         => str($name)->slug()->value(),
            'version'      => '1.0.0',
            'author'       => $this->faker->name(),
            'description'  => $this->faker->sentence(),
            'is_active'    => false,
            'config'       => null,
            'installed_at' => now(),
            'activated_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'is_active'    => true,
            'activated_at' => now(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active'    => false,
            'activated_at' => null,
        ]);
    }
}
