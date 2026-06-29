<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TravelSeeder::class,
            HomePageSectionSeeder::class,
            FaqSeeder::class,
            MenuSeeder::class,
            PageTemplateSeeder::class,
            AdminDashboardAppearanceSeeder::class,
        ]);
    }
}
