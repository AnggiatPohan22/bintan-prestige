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
use App\Models\SiteAsset;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\GlobalSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailBookingFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(GlobalSettingsService::class)->forgetAll();
    }

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

    public function test_product_detail_prepares_display_state_for_price_media_description_and_metadata(): void
    {
        $category = Category::factory()->create(['name' => 'Tour Package']);
        $destination = Destination::factory()->create(['name' => 'Lagoi']);
        $product = $this->createProduct([
            'name' => 'Prepared State Tour',
            'status' => 'published',
            'thumbnail' => 'products/prepared-state.jpg',
            'short_description' => 'Prepared short copy.',
            'description' => 'Prepared overview copy.',
            'duration' => '6 Hours',
            'meeting_point' => 'Lagoi Bay',
            'meta_title' => 'Prepared SEO Title',
            'meta_description' => 'Prepared SEO description.',
            'canonical_url' => 'https://example.test/prepared-state-tour',
        ], $category, $destination);

        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_IDR,
            'price' => 780000,
        ]);
        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_SGD,
            'price' => 65,
        ]);

        $response = $this->get(route('products.show', $product));

        $priceState = $response->viewData('priceState');
        $mediaState = $response->viewData('mediaState');
        $descriptionState = $response->viewData('descriptionState');
        $durationState = $response->viewData('durationState');
        $meetingPointState = $response->viewData('meetingPointState');
        $metadataState = $response->viewData('metadataState');
        $breadcrumbState = $response->viewData('breadcrumbState');

        $response->assertOk();
        $this->assertTrue($priceState['has_idr']);
        $this->assertTrue($priceState['has_sgd']);
        $this->assertSame('Rp 780.000', $priceState['primary_formatted']);
        $this->assertSame('SGD 65', $priceState['secondary_formatted']);
        $this->assertSame('thumbnail', $mediaState['primary']['source']);
        $this->assertSame('Prepared State Tour in Lagoi', $mediaState['primary']['alt']);
        $this->assertSame('Prepared overview copy.', $descriptionState['plain_text']);
        $this->assertSame('escaped_plain_text_with_line_breaks', $descriptionState['render_mode']);
        $this->assertSame('6 Hours', $durationState['display']);
        $this->assertSame('Lagoi Bay', $meetingPointState['display']);
        $this->assertSame('Prepared SEO Title', $metadataState['title']);
        $this->assertSame('Prepared SEO description.', $metadataState['description']);
        $this->assertSame('https://example.test/prepared-state-tour', $metadataState['canonical']);
        $this->assertSame(['Home', 'Products', 'Prepared State Tour'], $breadcrumbState->pluck('label')->all());
    }

    public function test_product_detail_layout_renders_breadcrumb_single_h1_summary_facts_and_prepared_cta(): void
    {
        $category = Category::factory()->create(['name' => 'Private Tour']);
        $destination = Destination::factory()->create(['name' => 'Trikora']);
        $product = $this->createProduct([
            'name' => 'Trikora Coastal Escape',
            'status' => 'published',
            'short_description' => 'A relaxed coastal route with private pickup.',
            'description' => 'Full coastal overview copy.',
            'duration' => '5 Hours',
            'meeting_point' => 'Trikora Beach Lobby',
            'pickup_available' => true,
            'pickup_type' => 'Hotel Pickup',
            'whatsapp_number' => '+62 812-3456-7890',
        ], $category, $destination);

        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_IDR,
            'price' => 640000,
        ]);
        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_SGD,
            'price' => 54,
        ]);

        $response = $this->get(route('products.show', $product));
        $html = $response->getContent();
        $whatsappState = $response->viewData('whatsappState');

        $response->assertOk();
        $this->assertSame(1, preg_match_all('/<h1\b/i', $html));
        $response->assertSee('aria-label="Breadcrumb"', false);
        $response->assertSeeInOrder(['Home', 'Products', 'Trikora Coastal Escape']);
        $response->assertSee('aria-current="page"', false);
        $response->assertSee('Private Tour');
        $response->assertSee('Trikora');
        $response->assertSee('A relaxed coastal route with private pickup.');
        $response->assertSee('Duration');
        $response->assertSee('5 Hours');
        $response->assertSee('Meeting Point');
        $response->assertSee('Trikora Beach Lobby');
        $response->assertSee('Pickup');
        $response->assertSee('Hotel Pickup');
        $response->assertSee('Rp 640.000');
        $response->assertSee('SGD 54');
        $response->assertSee('href="' . $whatsappState['chat_url'] . '"', false);
    }

    public function test_product_detail_layout_sections_render_in_prepared_order(): void
    {
        $product = $this->createProduct([
            'name' => 'Ordered Layout Tour',
            'status' => 'published',
            'description' => 'Ordered overview copy.',
        ]);

        ProductFeature::create([
            'product_id' => $product->id,
            'label' => 'included',
            'value' => 'First included layout feature',
            'sort_order' => 10,
        ]);
        ProductFeature::create([
            'product_id' => $product->id,
            'label' => 'included',
            'value' => 'Second included layout feature',
            'sort_order' => 20,
        ]);
        ProductItinerary::create([
            'product_id' => $product->id,
            'time' => '09:00',
            'title' => 'First layout stop',
            'description' => 'First layout stop description.',
            'sort_order' => 10,
            'start_time' => 900,
        ]);
        ProductItinerary::create([
            'product_id' => $product->id,
            'time' => '11:00',
            'title' => 'Second layout stop',
            'description' => 'Second layout stop description.',
            'sort_order' => 20,
            'start_time' => 1100,
        ]);
        ProductNote::create([
            'product_id' => $product->id,
            'title' => 'First layout note',
            'description' => 'First note description.',
            'sort_order' => 10,
        ]);
        ProductNote::create([
            'product_id' => $product->id,
            'title' => 'Second layout note',
            'description' => 'Second note description.',
            'sort_order' => 20,
        ]);
        ProductFaq::create([
            'product_id' => $product->id,
            'question' => 'First layout question?',
            'answer' => 'First layout answer.',
            'sort_order' => 10,
        ]);
        ProductFaq::create([
            'product_id' => $product->id,
            'question' => 'Second layout question?',
            'answer' => 'Second layout answer.',
            'sort_order' => 20,
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Overview',
            'Ordered overview copy.',
            'What&#039;s Included',
            'First included layout feature',
            'Second included layout feature',
            'Itinerary',
            'First layout stop',
            'Second layout stop',
            'Important Notes',
            'First layout note',
            'Second layout note',
            'FAQ',
            'First layout question?',
            'Second layout question?',
        ], false);
    }

    public function test_product_detail_layout_hides_empty_summary_and_optional_sections_without_empty_wrappers(): void
    {
        $product = $this->createProduct([
            'name' => 'Sparse Layout Tour',
            'status' => 'published',
            'short_description' => '',
            'description' => '',
            'duration' => '',
            'meeting_point' => '',
            'whatsapp_number' => '',
            'pickup_available' => false,
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertDontSee('class="product-detail-description text-body"', false);
        $response->assertDontSee('<span class="product-detail-meta__label">Duration</span>', false);
        $response->assertDontSee('<span class="product-detail-meta__label">Meeting Point</span>', false);
        $response->assertSee('<span class="product-detail-meta__label">Pickup</span>', false);
        $response->assertDontSee('id="products-show-overview"', false);
        $response->assertDontSee('id="products-show-features"', false);
        $response->assertDontSee('id="products-show-itinerary"', false);
        $response->assertDontSee('id="products-show-notes"', false);
        $response->assertDontSee('id="products-show-faq"', false);
        $response->assertDontSee('data-product-id="' . $product->id . '"', false);
    }

    public function test_product_detail_layout_does_not_add_gallery_lightbox_or_modal_interaction(): void
    {
        $contents = file_get_contents(
            resource_path('views/frontend/products/show.blade.php')
        );

        $this->assertStringNotContainsString('lightbox', $contents);
        $this->assertStringNotContainsString('product-media-modal', $contents);
        $this->assertStringNotContainsString('x-on:keydown', $contents);
    }

    public function test_product_detail_media_state_deduplicates_gallery_images_and_uses_default_placeholder(): void
    {
        $category = Category::factory()->create(['name' => 'Media State Category']);
        $destination = Destination::factory()->create(['name' => 'Media State Destination']);
        $productWithGallery = $this->createProduct([
            'name' => 'Gallery State Tour',
            'status' => 'published',
            'thumbnail' => null,
        ], $category, $destination);

        ProductImage::create([
            'product_id' => $productWithGallery->id,
            'image' => 'products/gallery-state.jpg',
            'sort_order' => 10,
        ]);
        ProductImage::create([
            'product_id' => $productWithGallery->id,
            'image' => '/products/gallery-state.jpg',
            'sort_order' => 20,
        ]);

        $galleryResponse = $this->get(route('products.show', $productWithGallery));
        $galleryMediaState = $galleryResponse->viewData('mediaState');

        $galleryResponse->assertOk();
        $this->assertSame(1, $galleryMediaState['count']);
        $this->assertSame('gallery', $galleryMediaState['primary']['source']);
        $this->assertFalse($galleryMediaState['uses_fallback']);

        SiteAsset::create([
            'key' => 'default_media.product',
            'label' => 'Product placeholder image',
            'path' => 'site-assets/default_media-product/product.jpg',
            'alt' => 'Default product placeholder',
            'is_active' => true,
        ]);
        SiteSetting::create([
            'key' => 'default_media.product.fit',
            'label' => 'Product placeholder fit',
            'value' => 'contain',
            'type' => 'select',
            'group' => 'default_media_settings',
            'is_active' => true,
        ]);

        $productWithoutMedia = $this->createProduct([
            'name' => 'Fallback State Tour',
            'status' => 'published',
            'thumbnail' => null,
        ], $category, $destination);

        $fallbackResponse = $this->get(route('products.show', $productWithoutMedia));
        $fallbackMediaState = $fallbackResponse->viewData('mediaState');

        $fallbackResponse->assertOk();
        $this->assertSame('fallback', $fallbackMediaState['primary']['source']);
        $this->assertTrue($fallbackMediaState['uses_fallback']);
        $this->assertSame('contain', $fallbackMediaState['primary']['fit']);
        $this->assertStringContainsString('site-assets/default_media-product/product.jpg', $fallbackMediaState['primary']['url']);
    }

    public function test_product_detail_whatsapp_state_uses_product_then_global_number_and_hides_when_missing(): void
    {
        $category = Category::factory()->create(['name' => 'WhatsApp State Category']);
        $destination = Destination::factory()->create(['name' => 'WhatsApp State Destination']);
        $productNumberProduct = $this->createProduct([
            'name' => 'Product Number Tour',
            'status' => 'published',
            'whatsapp_number' => '+62 812-0000-1111',
        ], $category, $destination);

        $productNumberResponse = $this->get(route('products.show', $productNumberProduct));
        $productNumberState = $productNumberResponse->viewData('whatsappState');

        $productNumberResponse->assertOk();
        $this->assertTrue($productNumberState['available']);
        $this->assertSame('product', $productNumberState['source']);
        $this->assertSame('6281200001111', $productNumberState['phone']);

        SiteSetting::create([
            'key' => 'contact.whatsapp_number',
            'label' => 'WhatsApp number',
            'value' => '+62 899-9888-777',
            'type' => 'text',
            'group' => 'contact_information',
            'is_active' => true,
        ]);

        $globalNumberProduct = $this->createProduct([
            'name' => 'Global Number Tour',
            'status' => 'published',
            'whatsapp_number' => '',
        ], $category, $destination);

        $globalNumberResponse = $this->get(route('products.show', $globalNumberProduct));
        $globalNumberState = $globalNumberResponse->viewData('whatsappState');

        $globalNumberResponse->assertOk();
        $this->assertTrue($globalNumberState['available']);
        $this->assertSame('global', $globalNumberState['source']);
        $this->assertSame('628999888777', $globalNumberState['phone']);

        SiteSetting::where('key', 'contact.whatsapp_number')->delete();
        app(GlobalSettingsService::class)->forgetAll();

        $missingNumberProduct = $this->createProduct([
            'name' => 'Missing Number Tour',
            'status' => 'published',
            'whatsapp_number' => '',
        ], $category, $destination);

        $missingNumberResponse = $this->get(route('products.show', $missingNumberProduct));
        $missingNumberState = $missingNumberResponse->viewData('whatsappState');

        $missingNumberResponse->assertOk();
        $this->assertFalse($missingNumberState['available']);
        $this->assertSame('none', $missingNumberState['source']);
        $this->assertNull($missingNumberState['chat_url']);
        $missingNumberResponse->assertDontSee('data-product-id="' . $missingNumberProduct->id . '"', false);
    }

    public function test_product_detail_section_state_hides_empty_optional_sections(): void
    {
        $product = $this->createProduct([
            'name' => 'Empty State Tour',
            'status' => 'published',
            'description' => '',
            'whatsapp_number' => '',
            'pickup_available' => false,
            'pickup_note' => null,
        ]);

        $response = $this->get(route('products.show', $product));
        $sectionState = $response->viewData('sectionState');

        $response->assertOk();
        $this->assertFalse($sectionState['has_overview']);
        $this->assertFalse($sectionState['has_features']);
        $this->assertFalse($sectionState['has_itineraries']);
        $this->assertFalse($sectionState['has_notes']);
        $this->assertFalse($sectionState['has_faqs']);
        $this->assertFalse($sectionState['has_addons']);
        $this->assertFalse($sectionState['has_whatsapp_cta']);
        $response->assertDontSee('id="products-show-overview"', false);
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
        $this->assertStringNotContainsString('DefaultMediaAssets::', $contents);
        $this->assertStringNotContainsString('BookingCtaSettings::', $contents);
        $this->assertStringNotContainsString('DB::', $contents);
        $this->assertStringNotContainsString('->load(', $contents);
        $this->assertStringNotContainsString('->where(', $contents);
        $this->assertStringNotContainsString('->sortBy(', $contents);
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
