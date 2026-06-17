<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalBusinessIdentitySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_business_identity_from_global_assets_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.business-identity.update'), [
                'business_identity' => [
                    'brand_name' => 'Bintan Prestige Travel',
                    'legal_name' => 'PT Bintan Prestige Indonesia',
                    'tagline' => 'Premium Bintan island experiences.',
                    'short_description' => 'Curated Bintan tours and private travel services.',
                    'business_type' => 'Travel Agency',
                    'location_label' => 'Bintan Island, Indonesia',
                    'copyright_text' => 'All rights reserved.',
                ],
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'business-identity']));

        $this->assertDatabaseHas('site_settings', [
            'key' => 'business.identity.brand_name',
            'value' => 'Bintan Prestige Travel',
            'type' => 'text',
            'group' => 'business_identity',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'business.identity.short_description',
            'value' => 'Curated Bintan tours and private travel services.',
            'type' => 'textarea',
            'group' => 'business_identity',
            'is_active' => true,
        ]);
    }

    public function test_business_identity_tab_only_shows_identity_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'business-identity']));

        $response->assertOk();
        $response->assertSee('Business Identity');
        $response->assertSee('Brand name');
        $response->assertSee('Legal company name');
        $response->assertDontSee('Upload favicon');
        $response->assertDontSee('Site Logo Variants');
    }

    public function test_frontend_layout_uses_business_identity_for_title_and_share_meta(): void
    {
        $response = $this->view('layouts.frontend', [
            'businessIdentity' => [
                'brand_name' => 'Bintan Prestige Travel',
                'short_description' => 'Curated Bintan tours and private travel services.',
            ],
        ]);

        $response->assertSee('<title>', false);
        $response->assertSee('Bintan Prestige Travel');
        $response->assertSee('Curated Bintan tours and private travel services.');
    }
}
