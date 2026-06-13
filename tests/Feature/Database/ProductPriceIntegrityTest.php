<?php

namespace Tests\Feature\Database;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Services\ProductPriceService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ProductPriceIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_have_idr_price(): void
    {
        $product = $this->createProduct();

        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_IDR,
            'price' => 250000,
        ]);

        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_IDR,
            'price' => 250000,
        ]);
    }

    public function test_product_can_have_sgd_price(): void
    {
        $product = $this->createProduct();

        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_SGD,
            'price' => 25,
        ]);

        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_SGD,
            'price' => 25,
        ]);
    }

    public function test_product_can_have_idr_and_sgd_prices_together(): void
    {
        $product = $this->createProduct();

        app(ProductPriceService::class)->sync(
            $product,
            '250000',
            '25'
        );

        $this->assertSame(2, $product->prices()->count());
        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_IDR,
            'price' => 250000,
        ]);
        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_SGD,
            'price' => 25,
        ]);
    }

    public function test_duplicate_product_currency_price_is_rejected_by_database(): void
    {
        $product = $this->createProduct();

        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_IDR,
            'price' => 250000,
        ]);

        $this->expectException(QueryException::class);

        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_IDR,
            'price' => 300000,
        ]);
    }

    public function test_product_price_service_updates_without_creating_duplicates(): void
    {
        $product = $this->createProduct();
        $service = app(ProductPriceService::class);

        $service->sync($product, '250000', '25');
        $service->sync($product, '300000', '30');

        $this->assertSame(2, $product->prices()->count());
        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_IDR,
            'price' => 300000,
        ]);
        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_SGD,
            'price' => 30,
        ]);
    }

    public function test_product_price_service_rejects_invalid_currency(): void
    {
        $product = $this->createProduct();

        $this->expectException(InvalidArgumentException::class);

        app(ProductPriceService::class)->syncCurrency(
            $product,
            'USD',
            '100'
        );
    }

    public function test_product_price_service_rejects_negative_amount(): void
    {
        $product = $this->createProduct();

        $this->expectException(InvalidArgumentException::class);

        app(ProductPriceService::class)->syncCurrency(
            $product,
            ProductPrice::CURRENCY_IDR,
            '-1'
        );
    }

    public function test_admin_product_validation_rejects_negative_prices(): void
    {
        $category = Category::create([
            'name' => 'Tour Package',
            'slug' => 'tour-package-' . uniqid(),
            'description' => 'Tour package category',
            'is_active' => true,
        ]);

        $destination = Destination::create([
            'name' => 'Lagoi',
            'slug' => 'lagoi-' . uniqid(),
            'description' => 'Lagoi destination',
            'image' => null,
            'is_active' => true,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'category_id' => $category->id,
                'destination_id' => $destination->id,
                'name' => 'Lagoi Private Tour',
                'slug' => 'lagoi-private-tour-' . uniqid(),
                'short_description' => 'Private tour package.',
                'description' => 'Private tour package description.',
                'meeting_point' => 'Lagoi Bay',
                'duration' => '4 Hours',
                'whatsapp_number' => '628123456789',
                'idr_price' => '-1',
                'sgd_price' => '-1',
                'status' => 'published',
            ])
            ->assertSessionHasErrors([
                'idr_price',
                'sgd_price',
            ]);
    }

    private function createProduct(): Product
    {
        Category::create([
            'name' => 'Tour Package',
            'slug' => 'tour-package-' . uniqid(),
            'description' => 'Tour package category',
            'is_active' => true,
        ]);

        Destination::create([
            'name' => 'Lagoi',
            'slug' => 'lagoi-' . uniqid(),
            'description' => 'Lagoi destination',
            'image' => null,
            'is_active' => true,
        ]);

        return Product::factory()->create();
    }
}
