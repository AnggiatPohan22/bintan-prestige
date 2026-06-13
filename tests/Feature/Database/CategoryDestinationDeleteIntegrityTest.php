<?php

namespace Tests\Feature\Database;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductFeature;
use App\Models\ProductImage;
use App\Models\ProductItinerary;
use App\Models\ProductNote;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoryDestinationDeleteIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_delete_category_does_not_delete_product_or_product_children(): void
    {
        [$category, , $product] = $this->createProductWithChildren();

        $category->delete();

        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);
        $this->assertProductAndChildrenExist($product);
    }

    public function test_soft_delete_destination_does_not_delete_product_or_product_children(): void
    {
        [, $destination, $product] = $this->createProductWithChildren();

        $destination->delete();

        $this->assertSoftDeleted('destinations', [
            'id' => $destination->id,
        ]);
        $this->assertProductAndChildrenExist($product);
    }

    public function test_database_rejects_hard_delete_category_that_has_products(): void
    {
        [$category, , $product] = $this->createProductWithChildren();

        try {
            $category->forceDelete();

            $this->fail('Category hard delete should be rejected while products reference it.');
        } catch (QueryException) {
            $this->assertDatabaseHas('categories', [
                'id' => $category->id,
            ]);
            $this->assertProductAndChildrenExist($product);
        }
    }

    public function test_database_rejects_hard_delete_destination_that_has_products(): void
    {
        [, $destination, $product] = $this->createProductWithChildren();

        try {
            $destination->forceDelete();

            $this->fail('Destination hard delete should be rejected while products reference it.');
        } catch (QueryException) {
            $this->assertDatabaseHas('destinations', [
                'id' => $destination->id,
            ]);
            $this->assertProductAndChildrenExist($product);
        }
    }

    public function test_controller_rejects_force_delete_category_that_has_products(): void
    {
        [$category, , $product] = $this->createProductWithChildren();
        $category->delete();

        $response = $this
            ->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.categories.force-delete', $category->id));

        $response
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('error');
        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);
        $this->assertProductAndChildrenExist($product);
    }

    public function test_controller_rejects_force_delete_destination_that_has_products(): void
    {
        [, $destination, $product] = $this->createProductWithChildren();
        $destination->delete();

        $response = $this
            ->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.destinations.force-delete', $destination->id));

        $response
            ->assertRedirect(route('admin.destinations.index'))
            ->assertSessionHas('error');
        $this->assertSoftDeleted('destinations', [
            'id' => $destination->id,
        ]);
        $this->assertProductAndChildrenExist($product);
    }

    public function test_empty_category_can_be_permanently_deleted(): void
    {
        $category = $this->createCategory();
        $category->delete();

        $response = $this
            ->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.categories.force-delete', $category->id));

        $response
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_empty_destination_can_be_permanently_deleted(): void
    {
        $destination = $this->createDestination();
        $destination->delete();

        $response = $this
            ->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.destinations.force-delete', $destination->id));

        $response
            ->assertRedirect(route('admin.destinations.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('destinations', [
            'id' => $destination->id,
        ]);
    }

    public function test_products_do_not_have_orphaned_category_or_destination_references(): void
    {
        $this->createProductWithChildren();

        $invalidCategoryCount = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereNull('categories.id')
            ->count();

        $invalidDestinationCount = DB::table('products')
            ->leftJoin('destinations', 'products.destination_id', '=', 'destinations.id')
            ->whereNull('destinations.id')
            ->count();

        $this->assertSame(0, $invalidCategoryCount);
        $this->assertSame(0, $invalidDestinationCount);
    }

    private function createProductWithChildren(): array
    {
        $category = $this->createCategory();
        $destination = $this->createDestination();

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'destination_id' => $destination->id,
            'thumbnail' => 'products/demo-thumb.webp',
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_IDR,
            'price' => 250000,
        ]);
        ProductImage::create([
            'product_id' => $product->id,
            'image' => 'products/demo.webp',
            'sort_order' => 1,
        ]);
        ProductFeature::create([
            'product_id' => $product->id,
            'label' => 'included',
            'value' => 'Private transport',
            'sort_order' => 1,
        ]);
        ProductFaq::create([
            'product_id' => $product->id,
            'question' => 'Is pickup included?',
            'answer' => 'Yes.',
            'sort_order' => 1,
        ]);
        ProductItinerary::create([
            'product_id' => $product->id,
            'time' => '08:00',
            'title' => 'Pickup',
            'description' => 'Hotel pickup.',
            'start_time' => 800,
            'sort_order' => 1,
        ]);
        ProductNote::create([
            'product_id' => $product->id,
            'title' => 'Important note',
            'description' => 'Bring comfortable clothes.',
            'sort_order' => 1,
        ]);

        return [$category, $destination, $product];
    }

    private function createCategory(): Category
    {
        return Category::create([
            'name' => 'Tour Package ' . uniqid(),
            'slug' => 'tour-package-' . uniqid(),
            'description' => 'Tour package category',
            'is_active' => true,
        ]);
    }

    private function createDestination(): Destination
    {
        return Destination::create([
            'name' => 'Lagoi ' . uniqid(),
            'slug' => 'lagoi-' . uniqid(),
            'description' => 'Lagoi destination',
            'image' => null,
            'is_active' => true,
        ]);
    }

    private function assertProductAndChildrenExist(Product $product): void
    {
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('product_features', [
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('product_faqs', [
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('product_itineraries', [
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('product_notes', [
            'product_id' => $product->id,
        ]);
    }
}
