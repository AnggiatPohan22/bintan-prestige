<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\TrackingIntegrationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalTrackingIntegrationsSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_tracking_integrations_from_global_assets(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.tracking-integrations.update'), [
                'tracking_integrations' => [
                    'enabled' => '1',
                    'environment_mode' => 'all',
                    'ga4_enabled' => '1',
                    'ga4_measurement_id' => 'G-TEST12345',
                    'gtm_enabled' => '1',
                    'gtm_container_id' => 'GTM-TEST123',
                    'meta_pixel_enabled' => '1',
                    'meta_pixel_id' => '1234567890',
                    'google_verification' => 'verification-token',
                    'clarity_enabled' => '1',
                    'clarity_project_id' => 'clarity123',
                    'custom_head_enabled' => '1',
                    'custom_head_script' => '<script>window.customHead=true;</script>',
                    'custom_body_start_enabled' => '1',
                    'custom_body_start_script' => '<noscript>body start</noscript>',
                    'custom_body_end_enabled' => '1',
                    'custom_body_end_script' => '<script>window.customEnd=true;</script>',
                    'whatsapp_enabled' => '1',
                    'whatsapp_ga4_event_name' => 'whatsapp_cta_click',
                    'whatsapp_meta_event_name' => 'Lead',
                    'whatsapp_track_header' => '1',
                    'whatsapp_track_footer' => '1',
                    'whatsapp_track_product' => '1',
                ],
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'tracking-integrations']));

        $this->assertDatabaseHas('site_settings', [
            'key' => 'tracking.ga4.measurement_id',
            'value' => 'G-TEST12345',
            'type' => 'text',
            'group' => TrackingIntegrationSettings::GROUP,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'tracking.whatsapp.enabled',
            'value' => '1',
            'type' => 'boolean',
            'group' => TrackingIntegrationSettings::GROUP,
            'is_active' => true,
        ]);
    }

    public function test_tracking_integrations_tab_only_shows_tracking_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'tracking-integrations']));

        $response->assertOk();
        $response->assertSee('Tracking / Integrations');
        $response->assertSee('GA4 measurement ID');
        $response->assertSee('GTM container ID');
        $response->assertSee('WhatsApp CTA Tracking');
        $response->assertDontSee('Default meta title');
        $response->assertDontSee('Footer Display');
    }

    public function test_tracking_partials_render_enabled_integrations(): void
    {
        $settings = [
            ...TrackingIntegrationSettings::valuesFromSettings(collect()),
            'environment_mode' => 'all',
            'ga4_enabled' => true,
            'ga4_measurement_id' => 'G-TEST12345',
            'gtm_enabled' => true,
            'gtm_container_id' => 'GTM-TEST123',
            'meta_pixel_enabled' => true,
            'meta_pixel_id' => '1234567890',
            'google_verification' => 'verification-token',
            'clarity_enabled' => true,
            'clarity_project_id' => 'clarity123',
            'custom_body_end_enabled' => true,
            'custom_body_end_script' => '<script>window.customEnd=true;</script>',
            'whatsapp_enabled' => true,
        ];

        $head = $this->view('partials.tracking-head', [
            'trackingIntegrationSettings' => $settings,
        ]);
        $bodyStart = $this->view('partials.tracking-body-start', [
            'trackingIntegrationSettings' => $settings,
        ]);
        $bodyEnd = $this->view('partials.tracking-body-end', [
            'trackingIntegrationSettings' => $settings,
        ]);

        $head->assertSee('google-site-verification', false);
        $head->assertSee('G-TEST12345', false);
        $head->assertSee('GTM-TEST123', false);
        $head->assertSee('1234567890', false);
        $head->assertSee('clarity123', false);
        $bodyStart->assertSee('googletagmanager.com/ns.html?id=GTM-TEST123', false);
        $bodyEnd->assertSee('BP_TRACKING_INTEGRATIONS', false);
        $bodyEnd->assertSee('whatsapp_cta_click', false);
        $bodyEnd->assertSee('window.customEnd=true', false);
    }

    public function test_whatsapp_ctas_include_tracking_metadata(): void
    {
        $product = $this->publishedProduct([
            'name' => 'Lagoi Private Tour',
            'whatsapp_number' => '6281234567890',
            'cta_button_text' => 'Ask Availability',
        ]);

        $response = $this->view('frontend.products.show', [
            'product' => $product,
            'trackingIntegrationSettings' => [
                ...TrackingIntegrationSettings::valuesFromSettings(collect()),
                'environment_mode' => 'all',
            ],
        ]);

        $response->assertSee('data-whatsapp-tracking="header"', false);
        $response->assertSee('data-whatsapp-tracking="footer"', false);
        $response->assertSee('data-whatsapp-tracking="product"', false);
        $response->assertSee('data-product-name="Lagoi Private Tour"', false);
        $response->assertSee('BP_TRACKING_INTEGRATIONS', false);
    }

    private function publishedProduct(array $attributes = []): Product
    {
        Category::factory()->create();
        Destination::factory()->create();

        return Product::factory()
            ->create([
                ...$attributes,
                'status' => 'published',
            ]);
    }
}
