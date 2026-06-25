<?php

/*
|--------------------------------------------------------------------------
| Admin Dashboard Appearance Presets
|--------------------------------------------------------------------------
|
| Preset warna siap pakai untuk "Customize Dashboard" di Settings.
| Setiap preset mengisi semua kolom AdminDashboardAppearance.
| key: digunakan sebagai identifier saat user pilih preset di UI.
|
*/

return [

    'command_center_dark' => [
        'label'         => 'Command Center Dark',
        'description'   => 'Default — Electric Violet di atas Midnight Base',
        'mode'          => 'dark',
        'sidebar_style' => 'dark',
        'sidebar_bg'    => '#020617',
        'primary_color' => '#7C3AED',
        'primary_hover' => '#6D28D9',
        'primary_text'  => '#FFFFFF',
        'accent_color'  => '#06B6D4',
        'gold_color'    => '#D4AF37',
        'show_gold'     => true,
        'bg_base'       => '#020617',
        'bg_card'       => '#1E293B',
        'bg_input'      => '#0F172A',
        'preset_name'   => 'Command Center Dark',
    ],

    'midnight_navy' => [
        'label'         => 'Midnight Navy',
        'description'   => 'Dark biru gelap dengan aksen cyan',
        'mode'          => 'dark',
        'sidebar_style' => 'dark',
        'sidebar_bg'    => '#0C1120',
        'primary_color' => '#3B82F6',
        'primary_hover' => '#2563EB',
        'primary_text'  => '#FFFFFF',
        'accent_color'  => '#06B6D4',
        'gold_color'    => '#D4AF37',
        'show_gold'     => true,
        'bg_base'       => '#0C1120',
        'bg_card'       => '#1A2540',
        'bg_input'      => '#0F1A30',
        'preset_name'   => 'Midnight Navy',
    ],

    'light_classic' => [
        'label'         => 'Light Classic',
        'description'   => 'Konten putih, sidebar gelap',
        'mode'          => 'light',
        'sidebar_style' => 'dark',
        'sidebar_bg'    => '#020617',
        'primary_color' => '#7C3AED',
        'primary_hover' => '#6D28D9',
        'primary_text'  => '#FFFFFF',
        'accent_color'  => '#06B6D4',
        'gold_color'    => '#D4AF37',
        'show_gold'     => true,
        'bg_base'       => '#F8FAFC',
        'bg_card'       => '#FFFFFF',
        'bg_input'      => '#FFFFFF',
        'preset_name'   => 'Light Classic',
    ],

    'full_light' => [
        'label'         => 'Full Light',
        'description'   => 'Tampilan terang seluruhnya',
        'mode'          => 'light',
        'sidebar_style' => 'light',
        'sidebar_bg'    => '#FFFFFF',
        'primary_color' => '#7C3AED',
        'primary_hover' => '#6D28D9',
        'primary_text'  => '#FFFFFF',
        'accent_color'  => '#06B6D4',
        'gold_color'    => '#D4AF37',
        'show_gold'     => true,
        'bg_base'       => '#F8FAFC',
        'bg_card'       => '#FFFFFF',
        'bg_input'      => '#FFFFFF',
        'preset_name'   => 'Full Light',
    ],

];
