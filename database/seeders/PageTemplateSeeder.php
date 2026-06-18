<?php

namespace Database\Seeders;

use App\Models\PageTemplate;
use Illuminate\Database\Seeder;

class PageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug'        => 'default',
                'name'        => 'Standard',
                'blade_file'  => 'default',
                'description' => 'Blocks render full-width, each managing its own container. The everyday page layout.',
                'sort_order'  => 0,
            ],
            [
                'slug'        => 'full-width',
                'name'        => 'Full-width Landing',
                'blade_file'  => 'full-width',
                'description' => 'Edge-to-edge, no top gap. Best for hero-led marketing and campaign pages.',
                'sort_order'  => 1,
            ],
            [
                'slug'        => 'contained',
                'name'        => 'Contained / Article',
                'blade_file'  => 'contained',
                'description' => 'Narrow centered column with the page title shown. Best for Privacy, Terms, and text pages.',
                'sort_order'  => 2,
            ],
        ];

        foreach ($templates as $template) {
            PageTemplate::firstOrCreate(
                ['slug' => $template['slug']],
                $template + ['is_active' => true],
            );
        }
    }
}
