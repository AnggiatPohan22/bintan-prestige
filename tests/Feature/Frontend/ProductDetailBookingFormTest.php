<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\ProductFeature;
use App\Models\ProductFaq;
use App\Models\ProductImage;
use App\Models\ProductItinerary;
use App\Models\ProductNote;
use App\Models\ProductPrice;
use App\Models\User;
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

    public function test_product_detail_public_visibility_matches_listing_policy(): void
    {
        $activeCategory = Category::factory()->create([
            'name' => 'Visible Detail Category',
            'slug' => 'visible-detail-category',
        ]);
        $activeDestination = Destination::factory()->create([
            'name' => 'Visible Detail Destination',
            'slug' => 'visible-detail-destination',
        ]);
        $inactiveCategory = Category::factory()->create([
            'name' => 'Inactive Detail Category',
            'slug' => 'inactive-detail-category',
            'is_active' => false,
        ]);
        $inactiveDestination = Destination::factory()->create([
            'name' => 'Inactive Detail Destination',
            'slug' => 'inactive-detail-destination',
            'is_active' => false,
        ]);

        $publishedProduct = $this->createProduct([
            'name' => 'Visible Detail Tour',
            'status' => 'published',
        ], $activeCategory, $activeDestination);
        $draftProduct = $this->createProduct([
            'name' => 'Draft Detail Tour',
            'status' => 'draft',
        ], $activeCategory, $activeDestination);
        $inactiveCategoryProduct = $this->createProduct([
            'name' => 'Inactive Category Detail Tour',
            'status' => 'published',
        ], $inactiveCategory, $activeDestination);
        $inactiveDestinationProduct = $this->createProduct([
            'name' => 'Inactive Destination Detail Tour',
            'status' => 'published',
        ], $activeCategory, $inactiveDestination);
        $invalidStatusProduct = $this->createProduct([
            'name' => 'Invalid Status Detail Tour',
            'status' => 'archived',
        ], $activeCategory, $activeDestination);

        $this->get(route('products.show', $publishedProduct))
            ->assertOk()
            ->assertSee('Visible Detail Tour');

        $this->get(route('products.show', $draftProduct))
            ->assertNotFound();

        $this->get(route('products.show', $inactiveCategoryProduct))
            ->assertNotFound();

        $this->get(route('products.show', $inactiveDestinationProduct))
            ->assertNotFound();

        $this->get(route('products.show', $invalidStatusProduct))
            ->assertNotFound();

        $this->get('/products/not-a-real-product-slug')
            ->assertNotFound();

        foreach ([
            $draftProduct,
            $inactiveCategoryProduct,
            $inactiveDestinationProduct,
            $invalidStatusProduct,
        ] as $product) {
            $this->assertDatabaseHas('products', [
                'id' => $product->id,
            ]);
        }
    }

    public function test_product_detail_hides_products_with_archived_parent_records(): void
    {
        $activeCategory = Category::factory()->create();
        $activeDestination = Destination::factory()->create();
        $archivedCategory = Category::factory()->create([
            'name' => 'Archived Detail Category',
            'slug' => 'archived-detail-category',
        ]);
        $archivedDestination = Destination::factory()->create([
            'name' => 'Archived Detail Destination',
            'slug' => 'archived-detail-destination',
        ]);
        $archivedCategoryProduct = $this->createProduct([
            'name' => 'Archived Category Detail Tour',
            'status' => 'published',
        ], $archivedCategory, $activeDestination);
        $archivedDestinationProduct = $this->createProduct([
            'name' => 'Archived Destination Detail Tour',
            'status' => 'published',
        ], $activeCategory, $archivedDestination);

        $archivedCategory->delete();
        $archivedDestination->delete();

        $this->get(route('products.show', $archivedCategoryProduct))
            ->assertNotFound();

        $this->get(route('products.show', $archivedDestinationProduct))
            ->assertNotFound();

        $this->assertDatabaseHas('products', [
            'id' => $archivedCategoryProduct->id,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $archivedDestinationProduct->id,
        ]);
    }

    public function test_admin_can_still_access_draft_and_inactive_parent_products(): void
    {
        $activeCategory = Category::factory()->create();
        $activeDestination = Destination::factory()->create();
        $inactiveCategory = Category::factory()->create([
            'name' => 'Admin Inactive Detail Category',
            'slug' => 'admin-inactive-detail-category',
            'is_active' => false,
        ]);

        $draftProduct = $this->createProduct([
            'name' => 'Admin Draft Detail Tour',
            'status' => 'draft',
        ], $activeCategory, $activeDestination);
        $inactiveParentProduct = $this->createProduct([
            'name' => 'Admin Inactive Parent Detail Tour',
            'status' => 'published',
        ], $inactiveCategory, $activeDestination);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $draftProduct))
            ->assertOk()
            ->assertSee('Admin Draft Detail Tour');

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $inactiveParentProduct))
            ->assertOk()
            ->assertSee('Admin Inactive Parent Detail Tour');
    }

    public function test_product_detail_eager_loads_required_relations_with_expected_ordering(): void
    {
        $product = $this->createProduct([
            'name' => 'Ordered Detail Tour',
            'status' => 'published',
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image' => 'products/second-image.jpg',
            'sort_order' => 20,
        ]);
        ProductImage::create([
            'product_id' => $product->id,
            'image' => 'products/first-image.jpg',
            'sort_order' => 10,
        ]);
        ProductFeature::create([
            'product_id' => $product->id,
            'label' => 'included',
            'value' => 'Second feature',
            'sort_order' => 20,
        ]);
        ProductFeature::create([
            'product_id' => $product->id,
            'label' => 'included',
            'value' => 'First feature',
            'sort_order' => 10,
        ]);
        ProductItinerary::create([
            'product_id' => $product->id,
            'time' => '10:00',
            'title' => 'Second itinerary',
            'description' => 'Second itinerary description.',
            'sort_order' => 20,
            'start_time' => 1000,
        ]);
        ProductItinerary::create([
            'product_id' => $product->id,
            'time' => '09:00',
            'title' => 'First itinerary',
            'description' => 'First itinerary description.',
            'sort_order' => 10,
            'start_time' => 900,
        ]);
        ProductNote::create([
            'product_id' => $product->id,
            'title' => 'Second note',
            'description' => 'Second note description.',
            'sort_order' => 20,
        ]);
        ProductNote::create([
            'product_id' => $product->id,
            'title' => 'First note',
            'description' => 'First note description.',
            'sort_order' => 10,
        ]);
        ProductFaq::create([
            'product_id' => $product->id,
            'question' => 'Second question?',
            'answer' => 'Second answer.',
            'sort_order' => 20,
        ]);
        ProductFaq::create([
            'product_id' => $product->id,
            'question' => 'First question?',
            'answer' => 'First answer.',
            'sort_order' => 10,
        ]);

        $response = $this->get(route('products.show', $product));
        $renderedProduct = $response->viewData('product');

        $response->assertOk();
        $this->assertTrue($renderedProduct->relationLoaded('category'));
        $this->assertTrue($renderedProduct->relationLoaded('destination'));
        $this->assertTrue($renderedProduct->relationLoaded('prices'));
        $this->assertTrue($renderedProduct->relationLoaded('images'));
        $this->assertTrue($renderedProduct->relationLoaded('highlights'));
        $this->assertTrue($renderedProduct->relationLoaded('features'));
        $this->assertTrue($renderedProduct->relationLoaded('itineraries'));
        $this->assertTrue($renderedProduct->relationLoaded('notes'));
        $this->assertTrue($renderedProduct->relationLoaded('faqs'));
        $this->assertSame([
            'products/first-image.jpg',
            'products/second-image.jpg',
        ], $renderedProduct->images->pluck('image')->all());
        $this->assertSame([
            'First feature',
            'Second feature',
        ], $renderedProduct->features->pluck('value')->all());
        $this->assertSame([
            'First itinerary',
            'Second itinerary',
        ], $renderedProduct->itineraries->pluck('title')->all());
        $this->assertSame([
            'First note',
            'Second note',
        ], $renderedProduct->notes->pluck('title')->all());
        $this->assertSame([
            'First question?',
            'Second question?',
        ], $renderedProduct->faqs->pluck('question')->all());
    }

    public function test_product_detail_empty_optional_relations_do_not_cause_exception(): void
    {
        $product = $this->createProduct([
            'name' => 'Empty Optional Detail Tour',
            'status' => 'published',
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('Empty Optional Detail Tour');
        $response->assertSee('Price on request');
    }

    public function test_product_detail_blade_does_not_query_products_or_relations(): void
    {
        $contents = file_get_contents(
            resource_path('views/frontend/products/show.blade.php')
        );

        $this->assertStringNotContainsString('Product::', $contents);
        $this->assertStringNotContainsString('::query(', $contents);
        $this->assertStringNotContainsString('DB::', $contents);
        $this->assertStringNotContainsString('->load(', $contents);
        $this->assertStringNotContainsString('->images()', $contents);
        $this->assertStringNotContainsString('->features()', $contents);
        $this->assertStringNotContainsString('->itineraries()', $contents);
        $this->assertStringNotContainsString('->notes()', $contents);
        $this->assertStringNotContainsString('->faqs()', $contents);
    }

    public function test_product_detail_query_count_stays_bounded_for_full_detail_relations(): void
    {
        $product = $this->createProduct([
            'name' => 'Query Bounded Detail Tour',
            'status' => 'published',
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_IDR,
            'price' => 570000,
        ]);
        ProductImage::create([
            'product_id' => $product->id,
            'image' => 'products/query-image.jpg',
            'sort_order' => 10,
        ]);
        ProductFeature::create([
            'product_id' => $product->id,
            'label' => 'included',
            'value' => 'Included query feature',
            'sort_order' => 10,
        ]);
        ProductItinerary::create([
            'product_id' => $product->id,
            'time' => '09:00',
            'title' => 'Query itinerary',
            'description' => 'Query itinerary description.',
            'sort_order' => 10,
            'start_time' => 900,
        ]);
        ProductNote::create([
            'product_id' => $product->id,
            'title' => 'Query note',
            'description' => 'Query note description.',
            'sort_order' => 10,
        ]);
        ProductFaq::create([
            'product_id' => $product->id,
            'question' => 'Query question?',
            'answer' => 'Query answer.',
            'sort_order' => 10,
        ]);

        $queryCount = 0;
        $this->app['db']->listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $this->get(route('products.show', $product))
            ->assertOk();

        $this->assertLessThanOrEqual(20, $queryCount);
    }

    private function createProduct(
        array $attributes = [],
        ?Category $category = null,
        ?Destination $destination = null
    ): Product {
        $category ??= Category::factory()->create();
        $destination ??= Destination::factory()->create();

        return Product::factory()->create([
            'category_id' => $category->id,
            'destination_id' => $destination->id,
            ...$attributes,
        ]);
    }
}
