<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductIndexUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_index_renders_page_and_section_keys_for_page_sections_mapping(): void
    {
        Category::factory()->create(['name' => 'Tours']);
        Destination::factory()->create(['name' => 'Lagoi']);
        Product::factory()->create([
            'name' => 'Lagoi Private Tour',
            'short_description' => 'Private tour package.',
            'status' => 'published',
        ]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('data-page-key="products.index"', false);
        $response->assertSee('data-section-key="products.index.hero"', false);
        $response->assertSee('id="products-index-hero"', false);
        $response->assertSee('data-section-key="products.index.catalog"', false);
        $response->assertSee('id="products-index-catalog"', false);
        $response->assertSee('Explore Tours, Taxi & Activities in Bintan', false);
        $response->assertSee('Lagoi Private Tour');
        $response->assertSee('Price on request');
        $response->assertDontSee('Rp 0');
        $response->assertDontSee('Open Lagoi Private Tour video');
    }

    public function test_product_index_renders_valid_idr_and_sgd_prices(): void
    {
        $product = $this->createProduct([
            'name' => 'Dual Currency Tour',
            'status' => 'published',
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_IDR,
            'price' => 570000,
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_SGD,
            'price' => 50,
        ]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Dual Currency Tour');
        $response->assertSee('Rp 570.000');
        $response->assertSee('SGD 50');
        $response->assertDontSee('Price on request');
    }

    public function test_product_index_renders_sgd_when_idr_price_is_missing(): void
    {
        $product = $this->createProduct([
            'name' => 'SGD Only Tour',
            'status' => 'published',
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_SGD,
            'price' => 35,
        ]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('SGD Only Tour');
        $response->assertSee('SGD 35');
        $response->assertDontSee('Rp 0');
    }

    private function createProduct(array $attributes = []): Product
    {
        $category = Category::factory()->create();
        $destination = Destination::factory()->create();

        return Product::factory()->create([
            'category_id' => $category->id,
            'destination_id' => $destination->id,
            ...$attributes,
        ]);
    }
}
