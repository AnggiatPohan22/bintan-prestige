<?php

namespace Database\Seeders;

use App\Models\PageSection;
use App\Support\PageSectionRegistry;
use Illuminate\Database\Seeder;

class HomePageSectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = PageSectionRegistry::sections()['home'] ?? [];

        foreach ($sections as $section) {
            PageSection::updateOrCreate(
                ['page_key' => 'home', 'section_key' => $section['section_key']],
                $section + ['page_key' => 'home', 'is_active' => true]
            );
        }
    }
}
