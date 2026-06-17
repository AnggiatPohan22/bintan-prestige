<?php

namespace Tests\Feature\Database;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFactoryStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_factory_creates_product_with_valid_default_status(): void
    {
        $this->createProductDependencies();

        $product = Product::factory()->create();

        $this->assertSame('published', $product->status);
        $this->assertContains($product->status, ['draft', 'published']);
    }

    public function test_product_factory_can_create_draft_product(): void
    {
        $this->createProductDependencies();

        $product = Product::factory()->draft()->create();

        $this->assertSame('draft', $product->status);
        $this->assertFalse(
            Product::published()
                ->whereKey($product)
                ->exists()
        );
    }

    public function test_product_factory_can_create_published_product(): void
    {
        $this->createProductDependencies();

        $product = Product::factory()->published()->create();

        $this->assertSame('published', $product->status);
        $this->assertTrue(
            Product::published()
                ->whereKey($product)
                ->exists()
        );
    }

    private function createProductDependencies(): void
    {
        Category::create([
            'name' => 'Tour Package',
            'slug' => 'tour-package',
            'description' => 'Tour package category',
            'is_active' => true,
        ]);

        Destination::create([
            'name' => 'Lagoi',
            'slug' => 'lagoi',
            'description' => 'Lagoi destination',
            'image' => null,
            'is_active' => true,
        ]);
    }
}
