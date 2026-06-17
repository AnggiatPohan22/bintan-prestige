<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Destination;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductPrice;
use App\Models\SiteAsset;
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
        $files = [
            resource_path('views/frontend/products/index.blade.php'),
            resource_path('views/frontend/products/partials/entity-context.blade.php'),
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertStringNotContainsString('PageSection::', $contents, $file);
            $this->assertStringNotContainsString('::query(', $contents, $file);
            $this->assertStringNotContainsString('DB::', $contents, $file);
        }
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

    public function test_public_listing_shows_active_filter_summary_result_count_and_reset_url(): void
    {
        $category = Category::factory()->create([
            'name' => 'Taxi Packages',
        ]);
        $destination = Destination::factory()->create([
            'name' => 'Lagoi',
        ]);
        $matchingProduct = $this->createProduct([
            'name' => 'Lagoi Taxi Package',
            'duration' => '4 Hours',
            'pickup_type' => 'Private Car',
            'status' => 'published',
        ], $category, $destination);
        $this->createPrice($matchingProduct, ProductPrice::CURRENCY_IDR, 550000);
        $otherProduct = $this->createProduct([
            'name' => 'Different Package',
            'duration' => 'Full Day',
            'pickup_type' => 'Shared Car',
            'status' => 'published',
        ], $category, $destination);
        $this->createPrice($otherProduct, ProductPrice::CURRENCY_IDR, 1250000);

        $response = $this->get(route('products.index', [
            'category' => [$category->id],
            'destination' => [$destination->id],
            'duration' => ['4 Hours'],
            'vehicle_type' => ['Private Car'],
            'min_price' => 500000,
            'max_price' => 600000,
            'sort' => 'price_low',
        ]));

        $response->assertOk();
        $response->assertSee('1 package found for your next Bintan experience.');
        $response->assertSee('Active listing state');
        $response->assertSee('Category');
        $response->assertSee('Taxi Packages');
        $response->assertSee('Destination');
        $response->assertSee('Lagoi');
        $response->assertSee('Duration');
        $response->assertSee('4 Hours');
        $response->assertSee('Vehicle');
        $response->assertSee('Private Car');
        $response->assertSee('Price');
        $response->assertSee('Rp 500.000 - Rp 600.000');
        $response->assertSee('Sort');
        $response->assertSee('Harga IDR terendah');
        $response->assertSee('href="' . route('products.index') . '"', false);
        $response->assertSee('Lagoi Taxi Package');
        $response->assertDontSee('Different Package');
        $response->assertDontSee('name="page"', false);
    }

    public function test_public_listing_single_category_context_renders_entity_layout_and_keeps_product_grid_contract(): void
    {
        $category = Category::factory()->create([
            'name' => 'Island Tours',
            'slug' => 'island-tours',
            'description' => 'Curated island routes with local assistance.',
        ]);
        $otherCategory = Category::factory()->create([
            'name' => 'Taxi Transfers',
            'slug' => 'taxi-transfers',
        ]);
        $destination = Destination::factory()->create([
            'name' => 'Lagoi Bay',
            'slug' => 'lagoi-bay-category-context',
        ]);
        $inactiveDestination = Destination::factory()->create([
            'name' => 'Inactive Category Context Destination',
            'slug' => 'inactive-category-context-destination',
            'is_active' => false,
        ]);

        $visibleProduct = $this->createProduct([
            'name' => 'Category Context Visible Tour',
            'status' => 'published',
        ], $category, $destination);
        $this->createProduct([
            'name' => 'Category Context Draft Tour',
            'status' => 'draft',
        ], $category, $destination);
        $this->createProduct([
            'name' => 'Category Context Inactive Destination Tour',
            'status' => 'published',
        ], $category, $inactiveDestination);
        $this->createProduct([
            'name' => 'Category Context Other Category Tour',
            'status' => 'published',
        ], $otherCategory, $destination);

        $response = $this->get(route('products.index', [
            'category' => [$category->id],
        ]));
        $html = $response->getContent();

        $response->assertOk();
        $response->assertViewHas('entityContext');
        $this->assertSame('category', $response->viewData('entityContext')['entity']['type']);
        $this->assertSame(1, $response->viewData('entityContext')['productCount']);
        $this->assertSame(1, substr_count($html, '<h1'));
        $response->assertSee('data-entity-type="category"', false);
        $response->assertSee('Island Tours');
        $response->assertSee('Curated island routes with local assistance.');
        $response->assertSee('1 package');
        $response->assertSee('class="product-grid"', false);
        $response->assertSee('class="product-card"', false);
        $response->assertSee('Category Context Visible Tour');
        $response->assertSee(route('products.show', $visibleProduct), false);
        $response->assertDontSee('Category Context Draft Tour');
        $response->assertDontSee('Category Context Inactive Destination Tour');
        $response->assertDontSee('Category Context Other Category Tour');
        $response->assertDontSee('id="product-filter-category-' . $category->id . '"', false);
        $response->assertSee('name="category[]" value="' . $category->id . '"', false);
        $response->assertSee('href="' . route('products.index', ['category' => [$category->id]]) . '"', false);
    }

    public function test_public_listing_single_destination_context_renders_media_layout_and_hides_redundant_destination_filter(): void
    {
        $category = Category::factory()->create([
            'name' => 'Adventure Tours',
            'slug' => 'adventure-tours-destination-context',
        ]);
        $inactiveCategory = Category::factory()->create([
            'name' => 'Inactive Destination Context Category',
            'slug' => 'inactive-destination-context-category',
            'is_active' => false,
        ]);
        $destination = Destination::factory()->create([
            'name' => 'Treasure Bay',
            'slug' => 'treasure-bay',
            'description' => 'Waterfront resort area with family activities.',
            'image' => 'destinations/treasure-bay.jpg',
        ]);
        $otherDestination = Destination::factory()->create([
            'name' => 'Trikora Coast',
            'slug' => 'trikora-coast-destination-context',
        ]);

        $visibleProduct = $this->createProduct([
            'name' => 'Destination Context Visible Tour',
            'status' => 'published',
        ], $category, $destination);
        $this->createProduct([
            'name' => 'Destination Context Draft Tour',
            'status' => 'draft',
        ], $category, $destination);
        $this->createProduct([
            'name' => 'Destination Context Inactive Category Tour',
            'status' => 'published',
        ], $inactiveCategory, $destination);
        $this->createProduct([
            'name' => 'Destination Context Other Destination Tour',
            'status' => 'published',
        ], $category, $otherDestination);

        $response = $this->get(route('products.index', [
            'destination' => [$destination->id],
        ]));
        $html = $response->getContent();

        $response->assertOk();
        $response->assertViewHas('entityContext');
        $this->assertSame('destination', $response->viewData('entityContext')['entity']['type']);
        $this->assertSame(1, $response->viewData('entityContext')['productCount']);
        $this->assertSame(1, substr_count($html, '<h1'));
        $response->assertSee('data-entity-type="destination"', false);
        $response->assertSee('Treasure Bay');
        $response->assertSee('Waterfront resort area with family activities.');
        $response->assertSee('storage/destinations/treasure-bay.jpg', false);
        $response->assertSee('alt="Treasure Bay destination image"', false);
        $response->assertSee('Destination Context Visible Tour');
        $response->assertSee(route('products.show', $visibleProduct), false);
        $response->assertDontSee('Destination Context Draft Tour');
        $response->assertDontSee('Destination Context Inactive Category Tour');
        $response->assertDontSee('Destination Context Other Destination Tour');
        $response->assertDontSee('id="product-filter-destination-' . $destination->id . '"', false);
        $response->assertSee('name="destination[]" value="' . $destination->id . '"', false);
        $response->assertSee('href="' . route('products.index', ['destination' => [$destination->id]]) . '"', false);
    }

    public function test_public_listing_destination_context_uses_fallback_media_and_entity_empty_state(): void
    {
        $destination = Destination::factory()->create([
            'name' => 'Empty Destination Context',
            'slug' => 'empty-destination-context',
            'description' => '',
            'image' => null,
        ]);

        SiteAsset::create([
            'key' => 'default_media.destination',
            'label' => 'Destination fallback',
            'path' => 'defaults/destination.jpg',
            'alt' => 'Default destination image',
            'is_active' => true,
        ]);

        $response = $this->get(route('products.index', [
            'destination' => [$destination->id],
        ]));

        $response->assertOk();
        $response->assertSee('Empty Destination Context');
        $response->assertSee('storage/defaults/destination.jpg', false);
        $response->assertSee('Default destination image');
        $response->assertSee('No packages are currently available for this destination');
        $response->assertSee('This destination is active, but there are no public packages available yet.');
        $response->assertSee('href="' . route('products.index', ['destination' => [$destination->id]]) . '"', false);
        $response->assertDontSee('product-card', false);
    }

    public function test_public_listing_category_context_uses_entity_empty_high_page_and_non_redundant_filter_state(): void
    {
        $category = Category::factory()->create([
            'name' => 'Empty Category Context',
            'slug' => 'empty-category-context',
            'description' => '',
        ]);

        $emptyResponse = $this->get(route('products.index', [
            'category' => [$category->id],
        ]));

        $emptyResponse->assertOk();
        $emptyResponse->assertSee('No packages are currently available for this category');
        $emptyResponse->assertSee('This category is active, but there are no public packages available yet.');
        $emptyResponse->assertDontSee('id="product-filter-category-' . $category->id . '"', false);

        $destination = Destination::factory()->create([
            'name' => 'Paged Category Destination',
            'slug' => 'paged-category-destination',
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $this->createProduct([
                'name' => 'Category Context Page Tour ' . $i,
                'status' => 'published',
            ], $category, $destination);
        }

        $highPageResponse = $this->get(route('products.index', [
            'category' => [$category->id],
            'page' => 3,
        ]));

        $highPageResponse->assertOk();
        $highPageResponse->assertSee('This page does not have any packages');
        $highPageResponse->assertSee('The current category context has fewer pages for the selected criteria.');
        $highPageResponse->assertSee('Back to first page');
        $highPageResponse->assertSee('href="' . route('products.index', ['category' => [$category->id]]) . '"', false);
        $highPageResponse->assertDontSee('name="page"', false);
    }

    public function test_public_listing_empty_states_distinguish_global_and_filtered_results(): void
    {
        $globalEmptyResponse = $this->get(route('products.index'));

        $globalEmptyResponse->assertOk();
        $globalEmptyResponse->assertSee('No packages are currently available');
        $globalEmptyResponse->assertSee('Published packages will appear here once they are ready for guests.');

        $category = Category::factory()->create([
            'name' => 'Empty Filter Category',
        ]);
        $destination = Destination::factory()->create([
            'name' => 'Empty Filter Destination',
        ]);
        $product = $this->createProduct([
            'name' => 'Visible Filter Product',
            'status' => 'published',
        ], $category, $destination);
        $this->createPrice($product, ProductPrice::CURRENCY_IDR, 200000);

        $filteredEmptyResponse = $this->get(route('products.index', [
            'category' => [$category->id],
            'min_price' => 900000,
        ]));

        $filteredEmptyResponse->assertOk();
        $filteredEmptyResponse->assertSee('No packages matched the selected filters');
        $filteredEmptyResponse->assertSee('Try removing one or more filters while keeping this category context.');
        $filteredEmptyResponse->assertSee('Reset filters');
        $filteredEmptyResponse->assertDontSee('Visible Filter Product');
    }

    public function test_public_listing_high_page_empty_state_uses_first_page_recovery_url(): void
    {
        $category = Category::factory()->create();
        $destination = Destination::factory()->create();

        for ($i = 1; $i <= 9; $i++) {
            $this->createProduct([
                'name' => 'High Page Tour ' . $i,
                'status' => 'published',
            ], $category, $destination);
        }

        $response = $this->get(route('products.index', [
            'page' => 99,
        ]));

        $response->assertOk();
        $response->assertViewHas('hasHighPageEmptyState', true);
        $response->assertSee('This page is empty');
        $response->assertSee('The listing has fewer pages for the current criteria. Return to the first page to continue browsing.');
        $response->assertSee('Back to first page');
        $response->assertSee('href="' . route('products.index') . '"', false);
        $response->assertDontSee('No packages are currently available');
    }

    public function test_public_listing_uses_nine_products_per_page_with_tenth_product_on_second_page(): void
    {
        $category = Category::factory()->create();
        $destination = Destination::factory()->create();

        for ($i = 1; $i <= 10; $i++) {
            $this->createProduct([
                'name' => 'Nine Per Page Tour ' . $i,
                'status' => 'published',
                'created_at' => now()->subMinutes(10 - $i),
                'updated_at' => now()->subMinutes(10 - $i),
            ], $category, $destination);
        }

        $firstPage = $this->get(route('products.index'));

        $firstPage->assertOk();
        $firstPage->assertSee('Showing 1-9 of 10 packages.');
        $firstPage->assertSee('Nine Per Page Tour 10');

        $firstPageProducts = $firstPage->viewData('products');
        $this->assertSame(9, $firstPageProducts->perPage());
        $this->assertCount(9, $firstPageProducts->getCollection());
        $this->assertContains('Nine Per Page Tour 10', $firstPageProducts->getCollection()->pluck('name')->all());
        $this->assertNotContains('Nine Per Page Tour 1', $firstPageProducts->getCollection()->pluck('name')->all());

        $secondPage = $this->get(route('products.index', [
            'page' => 2,
        ]));

        $secondPage->assertOk();
        $secondPage->assertSee('Showing 10-10 of 10 packages.');
        $secondPageProducts = $secondPage->viewData('products');
        $this->assertCount(1, $secondPageProducts->getCollection());
        $this->assertSame('Nine Per Page Tour 1', $secondPageProducts->getCollection()->first()->name);
    }

    public function test_product_card_fallback_image_alt_and_missing_optional_data_are_safe(): void
    {
        $product = $this->createProduct([
            'name' => 'Fallback Safe Listing Tour',
            'thumbnail' => null,
            'short_description' => '',
            'duration' => null,
            'status' => 'published',
        ]);
        $product->load('prices', 'images');
        $product->setRelation('category', null);
        $product->setRelation('destination', null);

        $siteAssets = collect([
            SiteAsset::make([
                'key' => 'default_media.product',
                'path' => 'defaults/product.jpg',
                'alt' => 'Default product image',
                'is_active' => true,
            ]),
        ])->keyBy('key');

        $card = $this->view('frontend.components.product-card', [
            'product' => $product,
            'variant' => 'listing',
            'siteAssets' => $siteAssets,
            'defaultMediaSettings' => [
                'product' => ['fit' => 'contain'],
            ],
        ]);

        $card->assertSee('storage/defaults/product.jpg', false);
        $card->assertSee('alt="Fallback Safe Listing Tour"', false);
        $card->assertSee('width="640"', false);
        $card->assertSee('height="800"', false);
        $card->assertSee('loading="lazy"', false);
        $card->assertSee('decoding="async"', false);
        $card->assertSee('style="object-fit: contain"', false);
        $card->assertSee('Price on request');
        $card->assertDontSee('Default product image');
        $card->assertDontSee('product-card__category', false);
        $card->assertDontSee('product-card__meta-dot', false);
        $card->assertDontSee('product-card__description', false);
        $card->assertDontSee('Rp 0');
    }

    public function test_product_listing_responsive_grid_css_contract_prevents_mobile_overflow(): void
    {
        $css = file_get_contents(base_path('resources/css/frontend-products.css'));
        $gridShellCss = substr(
            $css,
            strpos($css, '.product-grid-shell'),
            strpos($css, '.product-card') - strpos($css, '.product-grid-shell')
        );

        $this->assertStringContainsString('.product-grid-shell', $css);
        $this->assertStringContainsString('@apply overflow-visible;', $css);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr);', $css);
        $this->assertStringContainsString('@media (min-width: 640px)', $css);
        $this->assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr));', $css);
        $this->assertStringContainsString('@media (min-width: 1024px)', $css);
        $this->assertStringContainsString('grid-template-columns: repeat(3, minmax(0, 1fr));', $css);
        $this->assertStringContainsString('align-items: stretch;', $css);
        $this->assertStringContainsString('aspect-ratio: 4 / 5;', $css);
        $this->assertStringContainsString('height: 100%;', $css);
        $this->assertStringContainsString('width: 100%;', $css);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringNotContainsString('grid-auto-flow: column;', $gridShellCss);
        $this->assertStringNotContainsString('overflow-x-auto', $gridShellCss);
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
        $response->assertSee('No packages matched the selected filters');
        $response->assertDontSee('Valid Query Tour');
        $response->assertDontSee('Array');
        $response->assertSee('Some query values were ignored because they are not available filter options.');
    }

    public function test_public_listing_pagination_preserves_valid_filters_only(): void
    {
        $category = Category::factory()->create();
        $destination = Destination::factory()->create();

        for ($i = 1; $i <= 10; $i++) {
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
        $response->assertSee('Showing 1-9 of 10 packages.');
        $response->assertSee('sort=price_low', false);
        $response->assertSee('category%5B0%5D=' . $category->id, false);
        $response->assertSee('destination%5B0%5D=' . $destination->id, false);
        $response->assertSee('vehicle_type%5B0%5D=Private%20Car', false);
        $response->assertSee('min_price=100000', false);
        $response->assertDontSee('unsafe=drop-me', false);

        $pageTwo = $this->get(route('products.index', [
            'sort' => 'price_low',
            'category' => [$category->id],
            'destination' => [$destination->id],
            'duration' => ['4 Hours'],
            'vehicle_type' => ['Private Car'],
            'min_price' => 100000,
            'page' => 2,
        ]));

        $pageTwo->assertOk();
        $pageTwo->assertSee('Showing 10-10 of 10 packages.');
        $pageTwo->assertSee('Paginated Filter Tour 1');
    }

    public function test_admin_product_pagination_and_homepage_product_count_are_unchanged(): void
    {
        $category = Category::factory()->create();
        $destination = Destination::factory()->create();

        for ($i = 1; $i <= 13; $i++) {
            $this->createProduct([
                'name' => 'Shared Count Tour ' . $i,
                'status' => 'published',
            ], $category, $destination);
        }

        $adminResponse = $this
            ->actingAs(User::factory()->admin()->create())
            ->get(route('admin.products.index'));

        $adminResponse->assertOk();
        $this->assertSame(10, $adminResponse->viewData('products')->perPage());

        $homeResponse = $this->get(route('home'));

        $homeResponse->assertOk();
        $this->assertCount(12, $homeResponse->viewData('homeProducts'));
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
        $response->assertSee('No packages matched the selected filters');
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

    public function test_public_listing_accessibility_semantics_are_rendered_server_side(): void
    {
        $category = Category::factory()->create([
            'name' => 'Accessible Tours',
        ]);
        $destination = Destination::factory()->create([
            'name' => 'Lagoi',
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $product = $this->createProduct([
                'name' => 'Accessible Listing Tour ' . $i,
                'status' => 'published',
                'created_at' => now()->subMinutes(10 - $i),
                'updated_at' => now()->subMinutes(10 - $i),
            ], $category, $destination);
            $this->createPrice($product, ProductPrice::CURRENCY_IDR, 500000 + $i);
        }

        $response = $this->get(route('products.index'));
        $html = $response->getContent();

        $response->assertOk();
        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('<h3 class="product-card__title title-card">', $html);
        $this->assertStringNotContainsString('<h1 class="product-card', $html);
        $this->assertStringContainsString('aria-label="Open product listing filters"', $html);
        $this->assertStringContainsString('aria-label="Open product listing sorting options"', $html);
        $this->assertStringContainsString('method="GET"', $html);
        $this->assertStringContainsString('for="product-filter-min-price"', $html);
        $this->assertStringContainsString('for="product-filter-max-price"', $html);
        $this->assertStringContainsString('aria-label="View details for Accessible Listing Tour 10"', $html);
        $this->assertStringContainsString('Price starts from', $html);
        $this->assertStringContainsString('role="navigation" aria-label="Product listing pagination"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('aria-label="Go to next product listing page"', $html);
        $this->assertStringContainsString('href="' . route('products.show', Product::where('name', 'Accessible Listing Tour 10')->first()) . '"', $html);
        $this->assertStringNotContainsString('rel="nofollow"', $html);
    }

    public function test_public_listing_active_filters_have_accessible_remove_links(): void
    {
        $category = Category::factory()->create([
            'name' => 'Taxi Packages',
        ]);
        $destination = Destination::factory()->create([
            'name' => 'Lagoi',
        ]);
        $product = $this->createProduct([
            'name' => 'Accessible Filter Tour',
            'duration' => '4 Hours',
            'pickup_type' => 'Private Car',
            'status' => 'published',
        ], $category, $destination);
        $this->createPrice($product, ProductPrice::CURRENCY_IDR, 550000);

        $response = $this->get(route('products.index', [
            'category' => [$category->id],
            'destination' => [$destination->id],
            'duration' => ['4 Hours'],
            'vehicle_type' => ['Private Car'],
            'min_price' => 500000,
            'max_price' => 600000,
            'sort' => 'price_low',
        ]));

        $response->assertOk();
        $response->assertSee('aria-label="Remove category filter: Taxi Packages"', false);
        $response->assertSee('aria-label="Remove destination filter: Lagoi"', false);
        $response->assertSee('aria-label="Remove duration filter: 4 Hours"', false);
        $response->assertSee('aria-label="Remove vehicle filter: Private Car"', false);
        $response->assertSee('aria-label="Remove price filter: Rp 500.000 - Rp 600.000"', false);
        $response->assertSee('aria-label="Reset sorting to newest: Harga IDR terendah"', false);
    }

    public function test_public_listing_base_and_query_metadata_policy_is_normalized(): void
    {
        $product = $this->createProduct([
            'name' => 'Metadata Listing Tour',
            'status' => 'published',
        ]);
        $this->createPrice($product, ProductPrice::CURRENCY_IDR, 750000);

        $baseResponse = $this->get(route('products.index'));
        $baseHtml = $baseResponse->getContent();

        $baseResponse->assertOk();
        $this->assertSame(1, substr_count($baseHtml, 'rel="canonical"'));
        $this->assertStringContainsString('<meta name="robots" content="index, follow">', $baseHtml);
        $this->assertStringContainsString('<link rel="canonical" href="' . route('products.index') . '">', $baseHtml);
        $this->assertStringContainsString('<meta name="description" content="Choose curated island tours, private transfers, and activities with easy WhatsApp booking support.">', $baseHtml);
        $this->assertStringContainsString('Explore Tours, Taxi &amp; Activities in Bintan', $baseHtml);

        $sortResponse = $this->get(route('products.index', [
            'sort' => 'price_low',
        ]));
        $sortHtml = $sortResponse->getContent();

        $sortResponse->assertOk();
        $this->assertSame(1, substr_count($sortHtml, 'rel="canonical"'));
        $this->assertStringContainsString('<meta name="robots" content="noindex, follow">', $sortHtml);
        $this->assertStringContainsString('<link rel="canonical" href="' . route('products.index') . '">', $sortHtml);
        $this->assertStringContainsString('Filtered Bintan Packages', $sortHtml);

        $invalidResponse = $this->get(route('products.index', [
            'category' => ['<script>alert(1)</script>'],
            'tracking' => '<script>alert(2)</script>',
        ]));
        $invalidHtml = $invalidResponse->getContent();

        $invalidResponse->assertOk();
        $this->assertStringContainsString('<meta name="robots" content="noindex, follow">', $invalidHtml);
        $this->assertStringContainsString('<link rel="canonical" href="' . route('products.index') . '">', $invalidHtml);
        $this->assertStringContainsString('Bintan Product Listing', $invalidHtml);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $invalidHtml);
        $this->assertStringNotContainsString('<script>alert(2)</script>', $invalidHtml);
    }

    public function test_public_listing_pagination_metadata_uses_self_canonical_only_for_valid_plain_pages(): void
    {
        $category = Category::factory()->create();
        $destination = Destination::factory()->create();

        for ($i = 1; $i <= 10; $i++) {
            $this->createProduct([
                'name' => 'Canonical Page Tour ' . $i,
                'status' => 'published',
                'created_at' => now()->subMinutes(10 - $i),
                'updated_at' => now()->subMinutes(10 - $i),
            ], $category, $destination);
        }

        $pageTwoResponse = $this->get(route('products.index', [
            'page' => 2,
        ]));

        $pageTwoResponse->assertOk();
        $pageTwoResponse->assertSee('<meta name="robots" content="index, follow">', false);
        $pageTwoResponse->assertSee('<link rel="canonical" href="' . route('products.index', ['page' => 2]) . '">', false);

        $highPageResponse = $this->get(route('products.index', [
            'page' => 99,
        ]));

        $highPageResponse->assertOk();
        $highPageResponse->assertSee('<meta name="robots" content="noindex, follow">', false);
        $highPageResponse->assertSee('<link rel="canonical" href="' . route('products.index') . '">', false);
    }

    public function test_public_listing_outputs_valid_breadcrumb_and_itemlist_schema_without_fake_product_data(): void
    {
        $category = Category::factory()->create();
        $destination = Destination::factory()->create();
        $firstProduct = $this->createProduct([
            'name' => 'Schema Listing Tour One',
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ], $category, $destination);
        $secondProduct = $this->createProduct([
            'name' => 'Schema Listing Tour Two',
            'status' => 'published',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ], $category, $destination);

        $response = $this->get(route('products.index'));
        $graph = collect($this->structuredDataGraph($response->getContent()));
        $itemList = $graph->firstWhere('@type', 'ItemList');
        $breadcrumb = $graph->firstWhere('@type', 'BreadcrumbList');

        $response->assertOk();
        $this->assertNotNull($breadcrumb);
        $this->assertNotNull($itemList);
        $this->assertSame('Explore Tours, Taxi & Activities in Bintan', $itemList['name'] ?? null);
        $this->assertCount(2, $itemList['itemListElement']);
        $this->assertSame(1, $itemList['itemListElement'][0]['position']);
        $this->assertSame($firstProduct->name, $itemList['itemListElement'][0]['item']['name']);
        $this->assertSame(route('products.show', $firstProduct), $itemList['itemListElement'][0]['item']['url']);
        $this->assertSame($secondProduct->name, $itemList['itemListElement'][1]['item']['name']);
        $response->assertDontSee('"availability"', false);
        $response->assertDontSee('"review"', false);
        $response->assertDontSee('"aggregateRating"', false);
    }

    private function structuredDataGraph(string $html): array
    {
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

        $this->assertNotEmpty($matches[1] ?? null, 'Expected JSON-LD script to be present.');

        $data = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);

        return $data['@graph'] ?? [];
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
