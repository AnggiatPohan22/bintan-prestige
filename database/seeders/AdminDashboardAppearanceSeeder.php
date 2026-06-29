<?php

namespace Database\Seeders;

use App\Models\AdminDashboardAppearance;
use Illuminate\Database\Seeder;

class AdminDashboardAppearanceSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent — skip jika sudah ada record
        if (AdminDashboardAppearance::count() > 0) {
            return;
        }

        AdminDashboardAppearance::create([
            'mode'          => 'dark',
            'sidebar_bg'    => '#020617',
            'sidebar_style' => 'dark',
            'primary_color' => '#7C3AED',
            'primary_hover' => '#6D28D9',
            'primary_text'  => '#FFFFFF',
            'accent_color'  => '#06B6D4',
            'gold_color'    => '#D4AF37',
            'show_gold'     => true,
            'bg_base'       => '#020617',
            'bg_card'       => '#1E293B',
            'bg_input'      => '#0F172A',
            'custom_vars'   => null,
            'preset_name'   => 'Command Center Dark',
            'is_default'    => true,
        ]);
    }
}
