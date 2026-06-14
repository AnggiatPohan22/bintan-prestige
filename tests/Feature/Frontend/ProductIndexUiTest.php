<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Destination;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\User;
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
        $response->assertSee('Explore Tours, Taxi &amp; Activities in Bintan', false);
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

    public function test_product_listing_uses_active_page_section_content_image_and_cta(): void
    {
        PageSection::create([
            'page_key' => 'products.index',
            'section_key' => 'products.index.hero',
            'label' => 'CMS Listing Eyebrow',
            'title' => 'CMS Product Listing Title',
            'subtitle' => 'CMS listing subtitle.',
            'description' => 'CMS listing intro paragraph.',
            'image' => 'page-sections/products/listing-hero.jpg',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        PageSection::create([
            'page_key' => 'products.index',
            'section_key' => 'products.index.catalog',
            'title' => 'CMS Catalog Heading',
            'subtitle' => 'CMS final CTA heading',
            'description' => 'CMS catalog supporting paragraph.',
            'button_text' => 'Plan CMS Listing',
            'button_url' => '/products?source=listing-cms',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->createProduct([
            'name' => 'CMS Listing Product',
            'status' => 'published',
        ]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('CMS Listing Eyebrow');
        $response->assertSee('CMS Product Listing Title');
        $response->assertSee('CMS listing subtitle.');
        $response->assertSee('CMS listing intro paragraph.');
        $response->assertSee('storage/page-sections/products/listing-hero.jpg', false);
        $response->assertSee('CMS Catalog Heading');
        $response->assertSee('CMS catalog supporting paragraph.');
        $response->assertSee('CMS final CTA heading');
        $response->assertSee('Plan CMS Listing');
        $response->assertSee('href="/products?source=listing-cms"', false);
        $response->assertSee('CMS Listing Product');
    }

    public function test_product_listing_escapes_cms_content(): void
    {
        PageSection::create([
            'page_key' => 'products.index',
            'section_key' => 'products.index.hero',
            'title' => '<script>alert("listing")</script>',
            'description' => '<strong>Unsafe listing copy</strong>',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertDontSee('<script>alert("listing")</script>', false);
        $response->assertDontSee('<strong>Unsafe listing copy</strong>', false);
        $response->assertSee('&lt;script&gt;alert(&quot;listing&quot;)&lt;/script&gt;', false);
        $response->assertSee('&lt;strong&gt;Unsafe listing copy&lt;/strong&gt;', false);
    }

    public function test_product_listing_uses_fallbacks_for_missing_inactive_or_empty_sections(): void
    {
        PageSection::create([
            'page_key' => 'products.index',
            'section_key' => 'products.index.hero',
            'label' => 'Inactive Listing Eyebrow',
            'title' => 'Inactive Listing Title',
            'description' => 'Inactive listing copy.',
            'is_active' => false,
            'sort_order' => 0,
        ]);

        PageSection::create([
            'page_key' => 'products.index',
            'section_key' => 'products.index.catalog',
            'title' => '',
            'description' => '',
            'button_text' => 'Broken CTA Label',
            'button_url' => '',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Explore Tours, Taxi &amp; Activities in Bintan', false);
        $response->assertSee('Choose curated island tours, private transfers, and activities with easy WhatsApp booking support.');
        $response->assertSee('Available Products');
        $response->assertDontSee('Inactive Listing Title');
        $response->assertDontSee('Inactive listing copy.');
        $response->assertDontSee('Broken CTA Label');
        $response->assertDontSee('href=""', false);
    }

    public function test_product_listing_rejects_unsafe_cms_cta_url(): void
    {
        PageSection::create([
            'page_key' => 'products.index',
            'section_key' => 'products.index.catalog',
            'title' => 'CMS Catalog Heading',
            'button_text' => 'Unsafe Listing CTA',
            'button_url' => 'javascript:alert(1)',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertDontSee('Unsafe Listing CTA');
        $response->assertDontSee('javascript:alert', false);
        $response->assertDontSee('href=""', false);
    }

    public function test_product_listing_blade_does_not_query_page_sections_directly(): void
    {
        $contents = file_get_contents(
            resource_path('views/frontend/products/index.blade.php')
        );

        $this->assertStringNotContainsString('PageSection::', $contents);
        $this->assertStringNotContainsString('::query(', $contents);
        $this->assertStringNotContainsString('DB::', $contents);
    }

    public function test_product_listing_card_uses_consolidated_hierarchy_actions_and_media_contract(): void
    {
        $category = Category::factory()->create([
            'name' => 'Adventure Tours',
        ]);
        $destination = Destination::factory()->create([
            'name' => 'Lagoi Bay',
        ]);
        $product = $this->createProduct([
            'name' => 'Lagoi Bay Private Island Experience With Resort Pickup',
            'short_description' => 'Curated private island route with resort pickup, flexible stops, and local host support.',
            'thumbnail' => 'products/lagoi-private-island.jpg',
            'duration' => 'Full Day',
            'status' => 'published',
        ], $category, $destination);
        $this->createPrice($product, ProductPrice::CURRENCY_IDR, 880000);

        ProductImage::create([
            'product_id' => $product->id,
            'image' => 'products/gallery/lagoi-private-island-2.jpg',
            'sort_order' => 2,
        ]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('class="product-card"', false);
        $response->assertSee('class="product-card__description"', false);
        $response->assertSee('product-card__cta', false);
        $response->assertSee('alt="Lagoi Bay Private Island Experience With Resort Pickup in Lagoi Bay"', false);
        $response->assertSee(route('products.show', $product), false);
        $response->assertSee('Open Lagoi Bay Private Island Experience With Resort Pickup image');
        $response->assertDontSee('Open Lagoi Bay Private Island Experience With Resort Pickup video');
        $response->assertSeeInOrder([
            'Adventure Tours',
            'Lagoi Bay Private Island Experience With Resort Pickup',
            'Lagoi Bay',
            'Full Day',
            'Curated private island route with resort pickup, flexible stops, and local host support.',
            'Rp 880.000',
            'Details',
        ]);
    }

    public function test_product_index_renders_idr_only_and_missing_price_states_without_zero_fallbacks(): void
    {
        $category = Category::factory()->create();
        $destination = Destination::factory()->create();
        $idrOnlyProduct = $this->createProduct([
            'name' => 'IDR Only Tour',
            'status' => 'published',
        ], $category, $destination);
        $this->createPrice($idrOnlyProduct, ProductPrice::CURRENCY_IDR, 720000);

        $this->createProduct([
            'name' => 'No Price Tour',
            'status' => 'published',
        ], $category, $destination);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('IDR Only Tour');
        $response->assertSee('Rp 720.000');
        $response->assertSee('No Price Tour');
        $response->assertSee('Price on request');
        $response->assertDontSee('Rp 0');
        $response->assertDontSee('SGD 0');
    }

    public function test_public_listing_only_shows_published_products_with_active_public_parents(): void
    {
        $category = Category::factory()->create();
        $destination = Destination::factory()->create();
        $visibleProduct = $this->createProduct([
            'name' => 'Visible Published Tour',
            'status' => 'published',
        ], $category, $destination);
        $this->createProduct([
            'name' => 'Hidden Draft Tour',
            'status' => 'draft',
        ], $category, $destination);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Visible Published Tour');
        $response->assertDontSee('Hidden Draft Tour');
        $response->assertSee(route('products.show', $visibleProduct), false);

        $products = $response->viewData('products');
        $listedProduct = $products->getCollection()->firstWhere('id', $visibleProduct->id);

        $this->assertNotNull($listedProduct);
        $this->assertTrue($listedProduct->relationLoaded('category'));
        $this->assertTrue($listedProduct->relationLoaded('destination'));
        $this->assertTrue($listedProduct->relationLoaded('prices'));
        $this->assertTrue($listedProduct->relationLoaded('images'));
        $this->assertFalse($listedProduct->relationLoaded('faqs'));
        $this->assertFalse($listedProduct->relationLoaded('itineraries'));
        $this->assertFalse($listedProduct->relationLoaded('notes'));
        $this->assertFalse($listedProduct->relationLoaded('features'));
    }

    public function test_public_listing_hides_products_with_inactive_or_archived_parents_without_removing_admin_availability(): void
    {
        $inactiveCategory = Category::factory()->create([
            'name' => 'Inactive Listing Category',
            'slug' => 'inactive-listing-category',
            'is_active' => false,
        ]);
        $archivedCategory = Category::factory()->create([
            'name' => 'Archived Listing Category',
            'slug' => 'archived-listing-category',
        ]);
        $inactiveDestination = Destination::factory()->create([
            'name' => 'Inactive Listing Destination',
            'slug' => 'inactive-listing-destination',
            'is_active' => false,
        ]);
        $archivedDestination = Destination::factory()->create([
            'name' => 'Archived Listing Destination',
            'slug' => 'archived-listing-destination',
        ]);
        $activeCategory = Category::factory()->create([
            'name' => 'Active Listing Category',
            'slug' => 'active-listing-category',
        ]);
        $activeDestination = Destination::factory()->create([
            'name' => 'Active Listing Destination',
            'slug' => 'active-listing-destination',
        ]);

        $inactiveCategoryProduct = $this->createProduct([
            'name' => 'Inactive Category Product',
            'status' => 'published',
        ], $inactiveCategory, $activeDestination);
        $archivedCategoryProduct = $this->createProduct([
            'name' => 'Archived Category Product',
            'status' => 'published',
        ], $archivedCategory, $activeDestination);
        $inactiveDestinationProduct = $this->createProduct([
            'name' => 'Inactive Destination Product',
            'status' => 'published',
        ], $activeCategory, $inactiveDestination);
        $archivedDestinationProduct = $this->createProduct([
            'name' => 'Archived Destination Product',
            'status' => 'published',
        ], $activeCategory, $archivedDestination);

        $archivedCategory->delete();
        $archivedDestination->delete();

        $publicResponse = $this->get(route('products.index'));

        $publicResponse->assertOk();
        $publicResponse->assertDontSee('Inactive Category Product');
        $publicResponse->assertDontSee('Archived Category Product');
        $publicResponse->assertDontSee('Inactive Destination Product');
        $publicResponse->assertDontSee('Archived Destination Product');

        foreach ([
            $inactiveCategoryProduct,
            $archivedCategoryProduct,
            $inactiveDestinationProduct,
            $archivedDestinationProduct,
        ] as $product) {
            $this->assertDatabaseHas('products', [
                'id' => $product->id,
            ]);
        }

        $adminResponse = $this
            ->actingAs(User::factory()->admin()->create())
            ->get(route('admin.products.index'));

        $adminResponse->assertOk();
        $adminResponse->assertSee('Inactive Category Product');
        $adminResponse->assertSee('Archived Category Product');
        $adminResponse->assertSee('Inactive Destination Product');
        $adminResponse->assertSee('Archived Destination Product');
    }

    public function test_public_listing_invalid_query_parameters_are_safe_and_do_not_widen_results(): void
    {
        $this->createProduct([
            'name' => 'Valid Query Tour',
            'status' => 'published',
        ]);

        $response = $this->get(route('products.index', [
            'category' => [
                ['nested' => 'value'],
            ],
            'destination' => 'not-a-destination',
            'duration' => 'Unknown Duration',
            'vehicle_type' => 'Unknown Vehicle',
            'min_price' => ['bad'],
            'max_price' => 'not-a-number',
            'sort' => ['price_low'],
        ]));

        $response->assertOk();
        $response->assertSee('No products available');
        $response->assertDontSee('Valid Query Tour');
        $response->assertDontSee('Array');
    }

    public function test_public_listing_pagination_preserves_valid_filters_only(): void
    {
        $category = Category::factory()->create();
        $destination = Destination::factory()->create();

        for ($i = 1; $i <= 9; $i++) {
            $product = $this->createProduct([
                'name' => 'Paginated Filter Tour ' . $i,
                'status' => 'published',
                'duration' => '4 Hours',
                'pickup_type' => 'Private Car',
            ], $category, $destination);

            ProductPrice::create([
                'product_id' => $product->id,
                'currency' => ProductPrice::CURRENCY_IDR,
                'price' => 150000,
            ]);
        }

        $response = $this->get(route('products.index', [
            'sort' => 'price_low',
            'category' => [$category->id],
            'destination' => [$destination->id],
            'duration' => ['4 Hours'],
            'vehicle_type' => ['Private Car'],
            'min_price' => 100000,
            'unsafe' => 'drop-me',
        ]));

        $response->assertOk();
        $response->assertSee('page=2', false);
        $response->assertSee('sort=price_low', false);
        $response->assertSee('category%5B0%5D=' . $category->id, false);
        $response->assertSee('destination%5B0%5D=' . $destination->id, false);
        $response->assertSee('vehicle_type%5B0%5D=Private%20Car', false);
        $response->assertSee('min_price=100000', false);
        $response->assertDontSee('unsafe=drop-me', false);
    }

    public function test_public_listing_price_filter_uses_idr_only_and_respects_public_visibility(): void
    {
        $category = Category::factory()->create();
        $destination = Destination::factory()->create();
        $inactiveCategory = Category::factory()->create([
            'name' => 'Inactive Price Category',
            'slug' => 'inactive-price-category',
            'is_active' => false,
        ]);

        $matchingProduct = $this->createProduct([
            'name' => 'IDR In Range Tour',
            'status' => 'published',
        ], $category, $destination);
        $this->createPrice($matchingProduct, ProductPrice::CURRENCY_IDR, 750000);

        $tooLowProduct = $this->createProduct([
            'name' => 'IDR Too Low Tour',
            'status' => 'published',
        ], $category, $destination);
        $this->createPrice($tooLowProduct, ProductPrice::CURRENCY_IDR, 350000);

        $tooHighProduct = $this->createProduct([
            'name' => 'IDR Too High Tour',
            'status' => 'published',
        ], $category, $destination);
        $this->createPrice($tooHighProduct, ProductPrice::CURRENCY_IDR, 1250000);

        $sgdOnlyProduct = $this->createProduct([
            'name' => 'SGD Filter Missing IDR Tour',
            'status' => 'published',
        ], $category, $destination);
        $this->createPrice($sgdOnlyProduct, ProductPrice::CURRENCY_SGD, 80);

        $this->createProduct([
            'name' => 'No Price Filter Tour',
            'status' => 'published',
        ], $category, $destination);

        $draftProduct = $this->createProduct([
            'name' => 'Draft Price Tour',
            'status' => 'draft',
        ], $category, $destination);
        $this->createPrice($draftProduct, ProductPrice::CURRENCY_IDR, 750000);

        $inactiveParentProduct = $this->createProduct([
            'name' => 'Inactive Parent Price Tour',
            'status' => 'published',
        ], $inactiveCategory, $destination);
        $this->createPrice($inactiveParentProduct, ProductPrice::CURRENCY_IDR, 750000);

        $response = $this->get(route('products.index', [
            'min_price' => 500000,
            'max_price' => 1000000,
        ]));

        $response->assertOk();
        $response->assertSee('IDR In Range Tour');
        $response->assertDontSee('IDR Too Low Tour');
        $response->assertDontSee('IDR Too High Tour');
        $response->assertDontSee('SGD Filter Missing IDR Tour');
        $response->assertDontSee('No Price Filter Tour');
        $response->assertDontSee('Draft Price Tour');
        $response->assertDontSee('Inactive Parent Price Tour');
    }

    public function test_public_listing_invalid_price_range_is_safe(): void
    {
        $product = $this->createProduct([
            'name' => 'Safe Price Range Tour',
            'status' => 'published',
        ]);
        $this->createPrice($product, ProductPrice::CURRENCY_IDR, 750000);

        $response = $this->get(route('products.index', [
            'min_price' => 1000000,
            'max_price' => 500000,
        ]));

        $response->assertOk();
        $response->assertSee('No products available');
        $response->assertDontSee('Safe Price Range Tour');
    }

    public function test_public_listing_price_sort_uses_idr_ascending_with_missing_prices_last(): void
    {
        [$lowProduct, $highProduct, $sgdOnlyProduct, $noPriceProduct] =
            $this->createPriceSortingProducts();

        $response = $this->get(route('products.index', [
            'sort' => 'price_low',
        ]));

        $response->assertOk();
        $this->assertProductOrder($response, [
            $lowProduct->name,
            $highProduct->name,
        ]);
        $this->assertProductAppearsAfter($response, $sgdOnlyProduct->name, $highProduct->name);
        $this->assertProductAppearsAfter($response, $noPriceProduct->name, $highProduct->name);
        $this->assertSame(1, $this->productNameCount($response, $lowProduct->name));
    }

    public function test_public_listing_price_sort_uses_idr_descending_with_missing_prices_last(): void
    {
        [$lowProduct, $highProduct, $sgdOnlyProduct, $noPriceProduct] =
            $this->createPriceSortingProducts();

        $response = $this->get(route('products.index', [
            'sort' => 'price_high',
        ]));

        $response->assertOk();
        $this->assertProductOrder($response, [
            $highProduct->name,
            $lowProduct->name,
        ]);
        $this->assertProductAppearsAfter($response, $sgdOnlyProduct->name, $lowProduct->name);
        $this->assertProductAppearsAfter($response, $noPriceProduct->name, $lowProduct->name);
        $this->assertSame(1, $this->productNameCount($response, $highProduct->name));
    }

    public function test_public_listing_currency_parameter_does_not_change_frontend_06_idr_context(): void
    {
        $category = Category::factory()->create();
        $destination = Destination::factory()->create();

        $higherSgdLowerIdr = $this->createProduct([
            'name' => 'Lower IDR Higher SGD Tour',
            'status' => 'published',
        ], $category, $destination);
        $this->createPrice($higherSgdLowerIdr, ProductPrice::CURRENCY_IDR, 300000);
        $this->createPrice($higherSgdLowerIdr, ProductPrice::CURRENCY_SGD, 99);

        $lowerSgdHigherIdr = $this->createProduct([
            'name' => 'Higher IDR Lower SGD Tour',
            'status' => 'published',
        ], $category, $destination);
        $this->createPrice($lowerSgdHigherIdr, ProductPrice::CURRENCY_IDR, 500000);
        $this->createPrice($lowerSgdHigherIdr, ProductPrice::CURRENCY_SGD, 20);

        $response = $this->get(route('products.index', [
            'currency' => 'sgd',
            'sort' => 'price_low',
        ]));

        $response->assertOk();
        $response->assertViewHas('priceCurrency', ProductPrice::CURRENCY_IDR);
        $this->assertProductOrder($response, [
            'Lower IDR Higher SGD Tour',
            'Higher IDR Lower SGD Tour',
        ]);
        $response->assertDontSee('currency=sgd', false);
    }

    public function test_public_listing_deprecated_duration_sort_and_invalid_sort_fall_back_to_newest(): void
    {
        $category = Category::factory()->create();
        $destination = Destination::factory()->create();

        $olderProduct = $this->createProduct([
            'name' => 'Older Full Day Tour',
            'status' => 'published',
            'duration' => 'Full Day',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ], $category, $destination);

        $newerProduct = $this->createProduct([
            'name' => 'Newer Two Hour Tour',
            'status' => 'published',
            'duration' => '2 Hours',
            'created_at' => now(),
            'updated_at' => now(),
        ], $category, $destination);

        $durationResponse = $this->get(route('products.index', [
            'sort' => 'duration_short',
        ]));

        $durationResponse->assertOk();
        $durationResponse->assertViewHas('sort', 'newest');
        $durationResponse->assertDontSee('Durasi Tersingkat');
        $durationResponse->assertDontSee('Durasi Terlama');
        $this->assertProductOrder($durationResponse, [
            $newerProduct->name,
            $olderProduct->name,
        ]);

        $invalidResponse = $this->get(route('products.index', [
            'sort' => ['products.created_at desc'],
        ]));

        $invalidResponse->assertOk();
        $invalidResponse->assertViewHas('sort', 'newest');
        $this->assertProductOrder($invalidResponse, [
            $newerProduct->name,
            $olderProduct->name,
        ]);
    }

    private function createProduct(
        array $attributes = [],
        ?Category $category = null,
        ?Destination $destination = null
    ): Product
    {
        $category ??= Category::factory()->create();
        $destination ??= Destination::factory()->create();

        return Product::factory()->create([
            'category_id' => $category->id,
            'destination_id' => $destination->id,
            ...$attributes,
        ]);
    }

    private function createPrice(
        Product $product,
        string $currency,
        int $price
    ): ProductPrice {
        return ProductPrice::create([
            'product_id' => $product->id,
            'currency' => $currency,
            'price' => $price,
        ]);
    }

    private function createPriceSortingProducts(): array
    {
        $category = Category::factory()->create();
        $destination = Destination::factory()->create();

        $lowProduct = $this->createProduct([
            'name' => 'IDR Lowest Sort Tour',
            'status' => 'published',
            'created_at' => now()->subMinutes(4),
            'updated_at' => now()->subMinutes(4),
        ], $category, $destination);
        $this->createPrice($lowProduct, ProductPrice::CURRENCY_IDR, 100000);

        $highProduct = $this->createProduct([
            'name' => 'IDR Highest Sort Tour',
            'status' => 'published',
            'created_at' => now()->subMinutes(3),
            'updated_at' => now()->subMinutes(3),
        ], $category, $destination);
        $this->createPrice($highProduct, ProductPrice::CURRENCY_IDR, 900000);

        $sgdOnlyProduct = $this->createProduct([
            'name' => 'SGD Missing IDR Sort Tour',
            'status' => 'published',
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ], $category, $destination);
        $this->createPrice($sgdOnlyProduct, ProductPrice::CURRENCY_SGD, 10);

        $noPriceProduct = $this->createProduct([
            'name' => 'No Price Sort Tour',
            'status' => 'published',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ], $category, $destination);

        return [
            $lowProduct,
            $highProduct,
            $sgdOnlyProduct,
            $noPriceProduct,
        ];
    }

    private function assertProductOrder($response, array $expectedNames): void
    {
        $actualNames = $response
            ->viewData('products')
            ->getCollection()
            ->pluck('name')
            ->all();
        $lastPosition = -1;

        foreach ($expectedNames as $name) {
            $position = array_search($name, $actualNames, true);

            $this->assertNotFalse($position, "Failed asserting that [{$name}] exists.");
            $this->assertGreaterThan(
                $lastPosition,
                $position,
                "Failed asserting that [{$name}] appears in the expected product order."
            );

            $lastPosition = $position;
        }
    }

    private function productNameCount($response, string $name): int
    {
        return $response
            ->viewData('products')
            ->getCollection()
            ->where('name', $name)
            ->count();
    }

    private function assertProductAppearsAfter(
        $response,
        string $laterProductName,
        string $earlierProductName
    ): void {
        $actualNames = $response
            ->viewData('products')
            ->getCollection()
            ->pluck('name')
            ->all();

        $laterPosition = array_search($laterProductName, $actualNames, true);
        $earlierPosition = array_search($earlierProductName, $actualNames, true);

        $this->assertNotFalse($laterPosition, "Failed asserting that [{$laterProductName}] exists.");
        $this->assertNotFalse($earlierPosition, "Failed asserting that [{$earlierProductName}] exists.");
        $this->assertGreaterThan(
            $earlierPosition,
            $laterPosition,
            "Failed asserting that [{$laterProductName}] appears after [{$earlierProductName}]."
        );
    }
}
