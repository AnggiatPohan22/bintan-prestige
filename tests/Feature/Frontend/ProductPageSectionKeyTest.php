<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageSectionKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_index_renders_page_and_section_keys(): void
    {
        Category::factory()->create(['name' => 'Tour Package']);
        Destination::factory()->create(['name' => 'Lagoi']);

        Product::factory()->create([
            'name' => 'Lagoi Private Tour',
            'status' => 'published',
        ]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('data-page-key="products.index"', false);
        $response->assertSee('id="products-index-hero"', false);
        $response->assertSee('data-section-key="products.index.hero"', false);
        $response->assertSee('id="products-index-catalog"', false);
        $response->assertSee('data-section-key="products.index.catalog"', false);
    }

    public function test_product_detail_renders_page_and_section_keys(): void
    {
        Category::factory()->create(['name' => 'Tour Package']);
        Destination::factory()->create(['name' => 'Lagoi']);

        $product = Product::factory()->create([
            'name' => 'Lagoi Private Tour',
            'status' => 'published',
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('data-page-key="products.show"', false);
        $response->assertSee('id="products-show-hero"', false);
        $response->assertSee('data-section-key="products.show.hero"', false);
        $response->assertSee('id="products-show-gallery"', false);
        $response->assertSee('data-section-key="products.show.gallery"', false);
        $response->assertSee('id="products-show-summary"', false);
        $response->assertSee('data-section-key="products.show.summary"', false);
        $response->assertSee('id="products-show-booking"', false);
        $response->assertSee('data-section-key="products.show.booking"', false);
    }
}
