<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
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
    }
}
