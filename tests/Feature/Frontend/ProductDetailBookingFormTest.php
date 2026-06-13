<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\ProductFeature;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailBookingFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_detail_renders_booking_information_form_without_changing_product_core(): void
    {
        Category::factory()->create(['name' => 'Tour Package']);
        Destination::factory()->create(['name' => 'Lagoi']);

        $product = Product::factory()->create([
            'name' => 'Lagoi Private Tour',
            'status' => 'published',
            'pickup_available' => true,
            'pickup_type' => 'Hotel Pickup',
            'meeting_point' => 'Lagoi Bay',
            'duration' => '4 Hours',
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => 'IDR',
            'price' => 570000,
        ]);

        ProductFeature::create([
            'product_id' => $product->id,
            'label' => 'addon',
            'value' => 'Private Taxi',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('product-detail-page', false);
        $response->assertSee('data-page-key="products.show"', false);
        $response->assertSee('id="products-show-hero"', false);
        $response->assertSee('data-section-key="products.show.hero"', false);
        $response->assertSee('id="products-show-gallery"', false);
        $response->assertSee('data-section-key="products.show.gallery"', false);
        $response->assertSee('id="products-show-summary"', false);
        $response->assertSee('data-section-key="products.show.summary"', false);
        $response->assertSee('id="products-show-overview"', false);
        $response->assertSee('data-section-key="products.show.overview"', false);
        $response->assertSee('id="products-show-booking"', false);
        $response->assertSee('data-section-key="products.show.booking"', false);
        $response->assertSee('Booking Information');
        $response->assertSee('Price from');
        $response->assertSee('x-model="bookingDate"', false);
        $response->assertSee('Adults');
        $response->assertSee('Children');
        $response->assertSee('Add-ons');
        $response->assertSee('Private Taxi');
        $response->assertSee('Hotel Pickup');
        $response->assertSee('bookingWhatsappUrl()', false);
    }

    public function test_product_detail_without_price_renders_request_price_state(): void
    {
        Category::factory()->create(['name' => 'Tour Package']);
        Destination::factory()->create(['name' => 'Lagoi']);

        $product = Product::factory()->create([
            'name' => 'Custom Private Tour',
            'status' => 'published',
            'duration' => 'Flexible',
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('Custom Private Tour');
        $response->assertSee('Price on request');
        $response->assertDontSee('Rp 0');
    }

    public function test_product_detail_renders_sgd_price_when_idr_is_missing(): void
    {
        Category::factory()->create(['name' => 'Tour Package']);
        Destination::factory()->create(['name' => 'Lagoi']);

        $product = Product::factory()->create([
            'name' => 'Singapore Guest Tour',
            'status' => 'published',
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_SGD,
            'price' => 45,
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('Singapore Guest Tour');
        $response->assertSee('SGD 45');
        $response->assertDontSee('Rp 0');
    }
}
