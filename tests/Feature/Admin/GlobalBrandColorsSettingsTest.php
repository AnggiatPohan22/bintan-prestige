<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\BrandColorSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalBrandColorsSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_brand_colors_from_global_assets_settings(): void
    {
        $admin = User::factory()->admin()->create();
        $colors = collect(BrandColorSettings::fields())
            ->mapWithKeys(fn (array $field) => [$field['slug'] => $field['default']])
            ->all();

        $colors['palette_primary'] = '#111111';
        $colors['palette_secondary'] = '#d4af37';
        $colors['button_primary_bg'] = '#222222';

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.brand-colors.update'), [
                'brand_colors' => $colors,
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'brand-colors']));

        $this->assertDatabaseHas('site_settings', [
            'key' => 'brand.palette.primary',
            'value' => '#111111',
            'type' => 'color',
            'group' => 'brand_colors',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'brand.palette.secondary',
            'value' => '#d4af37',
            'type' => 'color',
            'group' => 'brand_colors',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'brand.button.primary.bg',
            'value' => '#222222',
            'type' => 'color',
            'group' => 'brand_colors',
            'is_active' => true,
        ]);
    }

    public function test_brand_color_tab_only_shows_brand_color_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'brand-colors']));

        $response->assertOk();
        $response->assertSee('Brand Colors');
        $response->assertSee('Palette');
        $response->assertSee('Typography');
        $response->assertSee('Primary button background');
        $response->assertDontSee('Upload favicon');
        $response->assertDontSee('Site Logo Variants');
    }

    public function test_frontend_layout_injects_brand_color_css_variables(): void
    {
        SiteSetting::create([
            'key' => 'brand.palette.primary',
            'label' => 'Primary',
            'value' => '#111111',
            'type' => 'color',
            'group' => 'brand_colors',
            'is_active' => true,
        ]);

        $response = $this->view('layouts.frontend', [
            'brandColors' => [
                ...collect(BrandColorSettings::fields())
                    ->mapWithKeys(fn (array $field) => [$field['slug'] => $field['default']])
                    ->all(),
                'palette_primary' => '#111111',
                'palette_secondary' => '#d4af37',
                'button_primary_bg' => '#222222',
            ],
        ]);

        $response->assertSee('--frontend-black: #111111', false);
        $response->assertSee('--frontend-gold: #d4af37', false);
        $response->assertSee('--frontend-button-primary-bg: #222222', false);
    }
}
