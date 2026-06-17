<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\ContactInformationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalContactInformationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_contact_information_from_global_assets_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.contact-information.update'), [
                'contact_information' => [
                    'email' => 'hello@bintanprestige.com',
                    'phone' => '+62 812 3456 7890',
                    'whatsapp_number' => '6281234567890',
                    'whatsapp_message' => 'Hello Bintan Prestige, I want to plan a trip.',
                    'address' => 'Bintan Island, Indonesia',
                    'google_maps_url' => 'https://maps.google.com/?q=Bintan',
                    'opening_hours' => 'Open Daily 09:00 - 18:00',
                ],
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'contact-information']));

        $this->assertDatabaseHas('site_settings', [
            'key' => 'contact.email',
            'value' => 'hello@bintanprestige.com',
            'type' => 'email',
            'group' => 'contact_information',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'contact.whatsapp_number',
            'value' => '6281234567890',
            'type' => 'text',
            'group' => 'contact_information',
            'is_active' => true,
        ]);
    }

    public function test_contact_information_tab_only_shows_contact_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'contact-information']));

        $response->assertOk();
        $response->assertSee('Contact Information');
        $response->assertSee('WhatsApp number');
        $response->assertDontSee('Upload favicon');
        $response->assertDontSee('Site Logo Variants');
    }

    public function test_footer_uses_global_contact_information(): void
    {
        $contactInformation = [
            ...ContactInformationSettings::valuesFromSettings(collect()),
            'email' => 'hello@bintanprestige.com',
            'phone' => '+62 812 3456 7890',
            'whatsapp_number' => '6281234567890',
            'whatsapp_message' => 'Hello from test.',
            'address' => 'Bintan Island, Indonesia',
            'google_maps_url' => 'https://maps.google.com/?q=Bintan',
            'opening_hours' => 'Open Daily 09:00 - 18:00',
        ];

        $response = $this->view('frontend.partials.footer', [
            'businessIdentity' => [
                'brand_name' => 'Bintan Prestige',
                'short_description' => 'Premium travel services.',
                'copyright_text' => 'All rights reserved.',
            ],
            'contactInformation' => $contactInformation,
            'contactWhatsappUrl' => ContactInformationSettings::whatsappUrl($contactInformation),
        ]);

        $response->assertSee('hello@bintanprestige.com');
        $response->assertSee('+62 812 3456 7890');
        $response->assertSee('Open Daily 09:00 - 18:00');
        $response->assertSee('https://wa.me/6281234567890', false);
    }
}
