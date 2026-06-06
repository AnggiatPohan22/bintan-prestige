<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\User;
use App\Support\BookingCtaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalBookingCtaSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_booking_cta_settings_from_global_assets(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.booking-cta.update'), [
                'booking_cta' => [
                    'enabled' => '1',
                    'use_on_header' => '1',
                    'use_on_footer' => '1',
                    'use_on_product' => '1',
                    'header_label' => 'Start Booking',
                    'footer_label' => 'Chat With Travel Team',
                    'product_chat_label' => 'Ask This Package',
                    'product_booking_label' => 'Reserve This Package',
                    'whatsapp_number_source' => 'override',
                    'whatsapp_number_override' => '628111222333',
                    'default_message' => 'Hello {site_name}, I want to plan from {page_url}.',
                    'product_message_template' => 'Hello {site_name}, I want {product_name}: {product_url}',
                ],
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'booking-cta']));

        $this->assertDatabaseHas('site_settings', [
            'key' => 'booking_cta.header_label',
            'value' => 'Start Booking',
            'type' => 'text',
            'group' => BookingCtaSettings::GROUP,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'booking_cta.use_on_product',
            'value' => '1',
            'type' => 'boolean',
            'group' => BookingCtaSettings::GROUP,
            'is_active' => true,
        ]);
    }

    public function test_booking_cta_tab_only_shows_booking_cta_form(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.settings.global-assets.edit', ['tab' => 'booking-cta']));

        $response->assertOk();
        $response->assertSee('Booking / CTA');
        $response->assertSee('WhatsApp number source');
        $response->assertSee('Product message template');
        $response->assertSee('Fallback priority');
        $response->assertDontSee('GA4 measurement ID');
        $response->assertDontSee('Default meta title');
    }

    public function test_frontend_header_and_footer_can_use_global_booking_cta(): void
    {
        $bookingCtaSettings = [
            ...BookingCtaSettings::valuesFromSettings(collect()),
            'enabled' => true,
            'use_on_header' => true,
            'use_on_footer' => true,
            'header_label' => 'Start Booking',
            'footer_label' => 'Chat With Travel Team',
            'whatsapp_number_source' => 'override',
            'whatsapp_number_override' => '628111222333',
            'default_message' => 'Hello {site_name}, I want to plan a trip.',
        ];

        $viewData = [
            'bookingCtaSettings' => $bookingCtaSettings,
            'businessIdentity' => ['brand_name' => 'Bintan Prestige'],
            'contactInformation' => ['whatsapp_number' => '628999888777'],
        ];

        $header = $this->view('frontend.partials.header', $viewData);
        $footer = $this->view('frontend.partials.footer', [
            ...$viewData,
            'activeSocialMediaLinks' => [],
        ]);

        $header->assertSee('Start Booking');
        $header->assertSee('https://wa.me/628111222333', false);
        $footer->assertSee('Chat With Travel Team');
        $footer->assertSee('https://wa.me/628111222333', false);
    }

    public function test_product_cta_keeps_product_number_and_uses_global_message_fallback(): void
    {
        $product = $this->publishedProduct([
            'name' => 'Lagoi Private Tour',
            'whatsapp_number' => '6281234567890',
            'cta_button_text' => null,
        ]);

        $response = $this->view('frontend.products.show', [
            'product' => $product,
            'businessIdentity' => ['brand_name' => 'Bintan Prestige'],
            'contactInformation' => ['whatsapp_number' => '628999888777'],
            'bookingCtaSettings' => [
                ...BookingCtaSettings::valuesFromSettings(collect()),
                'enabled' => true,
                'use_on_product' => true,
                'product_chat_label' => 'Ask This Package',
                'product_booking_label' => 'Reserve This Package',
                'product_message_template' => 'Hello {site_name}, I want {product_name}: {product_url}',
            ],
        ]);

        $response->assertSee('Ask This Package');
        $response->assertSee('Reserve This Package');
        $response->assertSee('https://wa.me/6281234567890', false);
        $response->assertSee(urlencode('Hello Bintan Prestige, I want Lagoi Private Tour: ' . route('products.show', $product)), false);
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
