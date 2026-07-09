<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductFeature;
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
        $response->assertDontSee('bookingWhatsappUrl()', false);
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
        $this->assertFalse($response->viewData('ctaState')['has_content']);
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
        $response->assertSee('href="'.$whatsappState['chat_url'].'"', false);
        $response->assertSee('href="'.$whatsappState['booking_url'].'"', false);
        $response->assertSee('aria-label="'.$whatsappState['chat_accessible_label'].'"', false);
        $response->assertSee('aria-label="'.$whatsappState['booking_accessible_label'].'"', false);
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
        $response->assertDontSee('<div class="product-detail-booking-card__row">', false);
        $response->assertDontSee('product-detail-booking-card__intro', false);
        $response->assertSee('<span class="product-detail-meta__label">Pickup</span>', false);
        $response->assertDontSee('id="products-show-overview"', false);
        $response->assertDontSee('id="products-show-features"', false);
        $response->assertDontSee('id="products-show-itinerary"', false);
        $response->assertDontSee('id="products-show-notes"', false);
        $response->assertDontSee('id="products-show-faq"', false);
        $response->assertDontSee('data-product-id="'.$product->id.'"', false);
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

    public function test_product_detail_gallery_renders_server_first_primary_image_without_javascript_dependency(): void
    {
        $destination = Destination::factory()->create(['name' => 'Lagoi']);
        $product = $this->createProduct([
            'name' => 'Server First Gallery Tour',
            'status' => 'published',
            'thumbnail' => 'products/server-first-primary.jpg',
        ], null, $destination);

        ProductImage::create([
            'product_id' => $product->id,
            'image' => 'products/server-first-gallery.jpg',
            'sort_order' => 10,
        ]);

        $response = $this->get(route('products.show', $product));
        $html = $response->getContent();
        $primaryImageTag = $this->galleryPrimaryImageTag($html);

        $response->assertOk();
        $this->assertStringNotContainsString('<template x-for="(image, index) in galleryImages"', $html);
        $this->assertStringContainsString('src="'.$product->thumbnail_url.'"', $primaryImageTag);
        $this->assertStringContainsString('alt="Server First Gallery Tour in Lagoi"', $primaryImageTag);
        $this->assertStringContainsString('width="1200"', $primaryImageTag);
        $this->assertStringContainsString('height="900"', $primaryImageTag);
        $this->assertStringContainsString('x-bind:src=', $primaryImageTag);
        $this->assertStringContainsString('x-bind:alt=', $primaryImageTag);
        $this->assertStringNotContainsString('loading="lazy"', $primaryImageTag);
    }

    public function test_product_detail_gallery_thumbnails_counter_and_accessible_active_state_render_for_multiple_images(): void
    {
        $product = $this->createProduct([
            'name' => 'Accessible Gallery Tour',
            'status' => 'published',
            'thumbnail' => 'products/accessible-gallery-primary.jpg',
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image' => 'products/accessible-gallery-second.jpg',
            'sort_order' => 10,
        ]);
        ProductImage::create([
            'product_id' => $product->id,
            'image' => 'products/accessible-gallery-third.jpg',
            'sort_order' => 20,
        ]);

        $response = $this->get(route('products.show', $product));
        $html = $response->getContent();

        $response->assertOk();
        $this->assertSame(3, preg_match_all('/<button[^>]*class="product-detail-gallery__thumb/i', $html));
        $response->assertSee('aria-label="View image 1 of Accessible Gallery Tour"', false);
        $response->assertSee('aria-label="View image 2 of Accessible Gallery Tour"', false);
        $response->assertSee('aria-label="View image 3 of Accessible Gallery Tour"', false);
        $response->assertSee('aria-pressed="true"', false);
        $response->assertSee('x-bind:aria-pressed=', false);
        $response->assertSee('x-on:click="selectGalleryImage(1)"', false);
        $response->assertSee('aria-live="polite"', false);
        $response->assertSee('1 / 3');
        $response->assertSee('loading="lazy"', false);
        $this->assertStringNotContainsString('<a class="product-detail-gallery__thumb', $html);
    }

    public function test_product_detail_gallery_hides_controls_for_single_or_empty_media_states(): void
    {
        $category = Category::factory()->create(['name' => 'Single Empty Gallery Category']);
        $destination = Destination::factory()->create(['name' => 'Single Empty Gallery Destination']);

        $singleImageProduct = $this->createProduct([
            'name' => 'Single Image Gallery Tour',
            'status' => 'published',
            'thumbnail' => 'products/single-image-gallery.jpg',
        ], $category, $destination);

        $singleImageResponse = $this->get(route('products.show', $singleImageProduct));
        $singleImageHtml = $singleImageResponse->getContent();

        $singleImageResponse->assertOk();
        $this->assertStringContainsString('src="'.$singleImageProduct->thumbnail_url.'"', $this->galleryPrimaryImageTag($singleImageHtml));
        $this->assertStringNotContainsString('product-detail-gallery__thumbs', $singleImageHtml);
        $this->assertStringNotContainsString('product-detail-gallery__count', $singleImageHtml);
        $this->assertStringNotContainsString('product-detail-gallery__nav', $singleImageHtml);

        $emptyMediaProduct = $this->createProduct([
            'name' => 'Empty Media Gallery Tour',
            'status' => 'published',
            'thumbnail' => null,
        ], $category, $destination);

        $emptyMediaResponse = $this->get(route('products.show', $emptyMediaProduct));
        $emptyMediaHtml = $emptyMediaResponse->getContent();

        $emptyMediaResponse->assertOk();
        $this->assertStringContainsString('product-detail-gallery__placeholder', $emptyMediaHtml);
        $this->assertStringContainsString('No Image', $emptyMediaHtml);
        $this->assertStringNotContainsString('product-detail-gallery__thumbs', $emptyMediaHtml);
        $this->assertStringNotContainsString('product-detail-gallery__count', $emptyMediaHtml);
    }

    public function test_product_detail_gallery_uses_ordered_gallery_primary_when_thumbnail_absent_and_does_not_count_fallback_controls(): void
    {
        $category = Category::factory()->create(['name' => 'Gallery Primary Category']);
        $destination = Destination::factory()->create(['name' => 'Gallery Primary Destination']);
        $productWithGallery = $this->createProduct([
            'name' => 'Ordered Gallery Primary Tour',
            'status' => 'published',
            'thumbnail' => null,
        ], $category, $destination);

        ProductImage::create([
            'product_id' => $productWithGallery->id,
            'image' => 'products/ordered-gallery-second.jpg',
            'sort_order' => 20,
        ]);
        ProductImage::create([
            'product_id' => $productWithGallery->id,
            'image' => 'products/ordered-gallery-first.jpg',
            'sort_order' => 10,
        ]);

        $galleryResponse = $this->get(route('products.show', $productWithGallery));
        $galleryMediaState = $galleryResponse->viewData('mediaState');

        $galleryResponse->assertOk();
        $this->assertSame('gallery', $galleryMediaState['primary']['source']);
        $this->assertStringContainsString('products/ordered-gallery-first.jpg', $galleryMediaState['primary']['url']);
        $this->assertStringContainsString('products/ordered-gallery-first.jpg', $this->galleryPrimaryImageTag($galleryResponse->getContent()));

        SiteAsset::create([
            'key' => 'default_media.product',
            'label' => 'Product placeholder image',
            'path' => 'site-assets/default_media-product/product.jpg',
            'alt' => 'Default product placeholder',
            'is_active' => true,
        ]);

        $fallbackProduct = $this->createProduct([
            'name' => 'Fallback Gallery Control Tour',
            'status' => 'published',
            'thumbnail' => null,
        ], $category, $destination);

        $fallbackResponse = $this->get(route('products.show', $fallbackProduct));
        $fallbackHtml = $fallbackResponse->getContent();
        $fallbackMediaState = $fallbackResponse->viewData('mediaState');

        $fallbackResponse->assertOk();
        $this->assertTrue($fallbackMediaState['uses_fallback']);
        $this->assertSame(1, $fallbackMediaState['count']);
        $this->assertStringContainsString('site-assets/default_media-product/product.jpg', $this->galleryPrimaryImageTag($fallbackHtml));
        $this->assertStringNotContainsString('product-detail-gallery__thumbs', $fallbackHtml);
        $this->assertStringNotContainsString('product-detail-gallery__count', $fallbackHtml);
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
        $this->assertStringStartsWith('https://wa.me/6281200001111?text=', $productNumberState['chat_url']);
        $this->assertStringStartsWith('https://wa.me/6281200001111?text=', $productNumberState['booking_url']);
        $this->assertStringContainsString('Product: Product Number Tour', $productNumberState['booking_message']);
        $this->assertStringContainsString('Destination: WhatsApp State Destination', $productNumberState['booking_message']);
        $this->assertStringContainsString('Product URL: '.route('products.show', $productNumberProduct), $productNumberState['booking_message']);

        SiteSetting::create([
            'key' => 'contact.whatsapp_number',
            'label' => 'WhatsApp number',
            'value' => '+62 899-9888-777',
            'type' => 'text',
            'group' => 'contact_information',
            'is_active' => true,
        ]);
        app(GlobalSettingsService::class)->forgetAll();

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

        $malformedProductNumberProduct = $this->createProduct([
            'name' => 'Malformed Product Number Tour',
            'status' => 'published',
            'whatsapp_number' => 'call me on WhatsApp',
        ], $category, $destination);

        $malformedProductNumberResponse = $this->get(route('products.show', $malformedProductNumberProduct));
        $malformedProductNumberState = $malformedProductNumberResponse->viewData('whatsappState');

        $malformedProductNumberResponse->assertOk();
        $this->assertTrue($malformedProductNumberState['available']);
        $this->assertSame('global', $malformedProductNumberState['source']);
        $this->assertSame('628999888777', $malformedProductNumberState['phone']);

        SiteSetting::where('key', 'contact.whatsapp_number')->delete();
        app(GlobalSettingsService::class)->forgetAll();

        $missingNumberProduct = $this->createProduct([
            'name' => 'Malformed Missing Number Tour',
            'status' => 'published',
            'whatsapp_number' => '123',
        ], $category, $destination);

        $missingNumberResponse = $this->get(route('products.show', $missingNumberProduct));
        $missingNumberState = $missingNumberResponse->viewData('whatsappState');

        $missingNumberResponse->assertOk();
        $this->assertFalse($missingNumberState['available']);
        $this->assertSame('none', $missingNumberState['source']);
        $this->assertNull($missingNumberState['chat_url']);
        $this->assertNull($missingNumberState['booking_url']);
        $missingNumberResponse->assertDontSee('data-product-id="'.$missingNumberProduct->id.'"', false);
    }

    public function test_product_detail_whatsapp_ctas_are_server_rendered_accessible_anchors(): void
    {
        $category = Category::factory()->create(['name' => 'WhatsApp CTA Category']);
        $destination = Destination::factory()->create(['name' => 'Lagoi']);
        $product = $this->createProduct([
            'name' => 'No JavaScript WhatsApp Tour',
            'status' => 'published',
            'duration' => '4 Hours',
            'whatsapp_number' => '+62 812-4444-5555',
        ], $category, $destination);

        $response = $this->get(route('products.show', $product));
        $html = $response->getContent();
        $whatsappState = $response->viewData('whatsappState');

        $response->assertOk();
        $this->assertTrue($whatsappState['available']);
        $this->assertSame('product', $whatsappState['source']);
        $response->assertSee('href="'.$whatsappState['chat_url'].'"', false);
        $response->assertSee('href="'.$whatsappState['booking_url'].'"', false);
        $response->assertSee('target="_blank"', false);
        $response->assertSee('rel="noopener noreferrer"', false);
        $response->assertSee('data-whatsapp-tracking="product"', false);
        $response->assertSee('aria-label="'.$whatsappState['chat_accessible_label'].'"', false);
        $response->assertSee('aria-label="'.$whatsappState['booking_accessible_label'].'"', false);
        $response->assertSee($whatsappState['booking_note']);

        $this->assertStringContainsString('Product: No JavaScript WhatsApp Tour', $whatsappState['booking_message']);
        $this->assertStringContainsString('Destination: Lagoi', $whatsappState['booking_message']);
        $this->assertStringContainsString('Duration: 4 Hours', $whatsappState['booking_message']);
        $this->assertStringContainsString('Product URL: '.route('products.show', $product), $whatsappState['booking_message']);
        $this->assertStringNotContainsString('confirmed', strtolower($whatsappState['booking_message']));
        $this->assertStringNotContainsString('payment', strtolower($whatsappState['booking_message']));
        $this->assertStringNotContainsString('checkout', strtolower($whatsappState['booking_message']));

        $this->assertStringNotContainsString('bookingWhatsappUrl()', $html);
        $this->assertStringNotContainsString('waNumber', $html);
        $this->assertStringNotContainsString('https://wa.me/${', $html);
        $this->assertStringNotContainsString('x-bind:href="bookingWhatsappUrl()"', $html);
    }

    public function test_product_card_does_not_require_product_detail_whatsapp_state(): void
    {
        $contents = file_get_contents(
            resource_path('views/frontend/components/product-card.blade.php')
        );

        $this->assertStringContainsString('View details for', $contents);
        $this->assertStringNotContainsString('whatsappState', $contents);
        $this->assertStringNotContainsString('data-whatsapp-tracking', $contents);
        $this->assertStringNotContainsString('booking_url', $contents);
    }

    public function test_product_detail_filters_malformed_optional_rows_and_preserves_partial_content(): void
    {
        $category = Category::factory()->create(['name' => 'Malformed Optional Category']);
        $destination = Destination::factory()->create(['name' => 'Malformed Optional Destination']);
        $product = $this->createProduct([
            'name' => 'Malformed Optional Tour',
            'status' => 'published',
            'description' => '   ',
            'short_description' => '   ',
            'duration' => '   ',
            'meeting_point' => '   ',
            'cta_title' => '   ',
            'cta_description' => '   ',
            'whatsapp_number' => '',
            'pickup_available' => false,
        ], $category, $destination);

        ProductFeature::create([
            'product_id' => $product->id,
            'label' => 'included',
            'value' => '   ',
            'sort_order' => 10,
        ]);
        ProductFeature::create([
            'product_id' => $product->id,
            'label' => 'included',
            'value' => 'Clean included feature',
            'sort_order' => 20,
        ]);
        ProductItinerary::create([
            'product_id' => $product->id,
            'time' => '   ',
            'title' => '   ',
            'description' => 'Description-only itinerary item',
            'sort_order' => 10,
            'start_time' => 900,
        ]);
        ProductItinerary::create([
            'product_id' => $product->id,
            'time' => '25:99',
            'title' => 'Invalid clock itinerary item',
            'description' => 'Invalid clock-shaped time should not render.',
            'sort_order' => 15,
            'start_time' => 950,
        ]);
        ProductItinerary::create([
            'product_id' => $product->id,
            'time' => '   ',
            'title' => '   ',
            'description' => '   ',
            'sort_order' => 20,
            'start_time' => 1000,
        ]);
        ProductNote::create([
            'product_id' => $product->id,
            'title' => '   ',
            'description' => 'Description-only note',
            'sort_order' => 10,
        ]);
        ProductNote::create([
            'product_id' => $product->id,
            'title' => '   ',
            'description' => '   ',
            'sort_order' => 20,
        ]);
        ProductFaq::create([
            'product_id' => $product->id,
            'question' => 'Question without answer?',
            'answer' => '   ',
            'sort_order' => 10,
        ]);
        ProductFaq::create([
            'product_id' => $product->id,
            'question' => '   ',
            'answer' => 'Answer without question should not render.',
            'sort_order' => 20,
        ]);

        $response = $this->get(route('products.show', $product));
        $html = $response->getContent();
        $featureGroups = $response->viewData('featureGroups');
        $itineraryItems = $response->viewData('itineraryItems');
        $noteItems = $response->viewData('noteItems');
        $faqItems = $response->viewData('faqItems');
        $sectionState = $response->viewData('sectionState');

        $response->assertOk();
        $this->assertFalse($sectionState['has_overview']);
        $this->assertTrue($sectionState['has_features']);
        $this->assertTrue($sectionState['has_itineraries']);
        $this->assertTrue($sectionState['has_notes']);
        $this->assertTrue($sectionState['has_faqs']);
        $this->assertSame(['Clean included feature'], $featureGroups->firstWhere('label', 'included')['items']->pluck('value')->all());
        $this->assertCount(2, $itineraryItems);
        $this->assertFalse($itineraryItems->first()['has_time']);
        $this->assertFalse($itineraryItems->first()['has_title']);
        $this->assertSame('Description-only itinerary item', $itineraryItems->first()['description']);
        $this->assertFalse($itineraryItems->last()['has_time']);
        $this->assertSame('Invalid clock itinerary item', $itineraryItems->last()['title']);
        $this->assertCount(1, $noteItems);
        $this->assertFalse($noteItems->first()['has_title']);
        $this->assertSame('Description-only note', $noteItems->first()['description']);
        $this->assertCount(1, $faqItems);
        $this->assertSame('Question without answer?', $faqItems->first()['question']);
        $this->assertFalse($faqItems->first()['has_answer']);
        $response->assertSee('Clean included feature');
        $response->assertSee('Description-only itinerary item');
        $response->assertSee('Invalid clock itinerary item');
        $response->assertSee('Description-only note');
        $response->assertSee('Question without answer?');
        $response->assertDontSee('25:99');
        $response->assertDontSee('Answer without question should not render.');
        $response->assertDontSee('product-detail-timeline__time', false);
        $response->assertDontSee('product-detail-faq__answer', false);
        $response->assertDontSee('product-detail-cta-note', false);
        $this->assertSame(1, preg_match_all('/<li class="product-detail-list__item">/', $html));
        $this->assertSame(2, preg_match_all('/<li class="[^"]*\bproduct-detail-timeline__item--no-time\b[^"]*">/', $html));
        $this->assertSame(1, preg_match_all('/<details class="product-detail-faq">/', $html));
    }

    public function test_product_detail_metadata_uses_safe_fallbacks_for_whitespace_values(): void
    {
        $product = $this->createProduct([
            'name' => 'Whitespace Metadata Tour',
            'status' => 'published',
            'short_description' => '   ',
            'description' => '   ',
            'meta_title' => '   ',
            'meta_description' => '   ',
            'meta_keywords' => '   ',
            'canonical_url' => '   ',
        ]);

        $response = $this->get(route('products.show', $product));
        $metadataState = $response->viewData('metadataState');

        $response->assertOk();
        $this->assertSame('Whitespace Metadata Tour', $metadataState['title']);
        $this->assertSame('Plan your Bintan experience with Bintan Prestige.', $metadataState['description']);
        $this->assertNull($metadataState['keywords']);
        $this->assertSame(route('products.show', $product), $metadataState['canonical']);
    }

    public function test_product_detail_renders_accessible_metadata_and_semantic_controls(): void
    {
        $product = $this->createProduct([
            'name' => 'Accessible SEO Tour',
            'status' => 'published',
            'thumbnail' => 'products/accessible-seo-primary.jpg',
            'short_description' => 'Accessible SEO summary.',
            'meta_title' => 'Accessible SEO Tour Meta',
            'meta_description' => 'Plain accessible SEO meta description.',
            'whatsapp_number' => '+62 812-3456-7890',
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image' => 'products/accessible-seo-second.jpg',
            'sort_order' => 10,
        ]);
        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_IDR,
            'price' => 1250000,
        ]);

        $response = $this->get(route('products.show', $product));
        $html = $response->getContent();

        $response->assertOk();
        $this->assertSame(1, preg_match_all('/<h1\b/i', $html));
        $this->assertStringContainsString('<title>', $html);
        $response->assertSee('Accessible SEO Tour Meta', false);
        $response->assertSee('<meta name="description" content="Plain accessible SEO meta description.">', false);
        $response->assertSee('<link rel="canonical" href="'.route('products.show', $product).'">', false);
        $response->assertSee('<meta name="robots" content="index, follow">', false);
        $response->assertSee('<meta property="og:type" content="product">', false);
        $response->assertSee('<nav class="product-breadcrumb product-detail-breadcrumb" aria-label="Breadcrumb">', false);
        $response->assertSee('<ol class="product-breadcrumb__list">', false);
        $response->assertSee('aria-current="page"', false);
        $response->assertSee('aria-label="Previous image of Accessible SEO Tour"', false);
        $response->assertSee('aria-label="Next image of Accessible SEO Tour"', false);
        $response->assertSee('aria-label="View image 1 of Accessible SEO Tour"', false);
        $response->assertSee('aria-pressed="true"', false);
        $response->assertSee('aria-label="Chat via WhatsApp about Accessible SEO Tour"', false);
        $response->assertSee('<span class="sr-only">Start from Indonesian Rupiah </span>', false);
    }

    public function test_product_detail_outputs_valid_schema_from_actual_product_data_only(): void
    {
        $category = Category::factory()->create(['name' => 'Schema Tour Category']);
        $destination = Destination::factory()->create(['name' => 'Schema Destination']);
        $product = $this->createProduct([
            'name' => 'Schema Detail Tour',
            'status' => 'published',
            'thumbnail' => 'products/schema-detail-primary.jpg',
            'short_description' => 'Schema detail summary.',
        ], $category, $destination);

        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_IDR,
            'price' => 1250000,
        ]);
        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_SGD,
            'price' => 85,
        ]);
        ProductFaq::create([
            'product_id' => $product->id,
            'question' => 'Is this schema FAQ visible?',
            'answer' => 'Yes, this answer is visible on the page.',
            'sort_order' => 10,
        ]);
        ProductFaq::create([
            'product_id' => $product->id,
            'question' => 'Question without schema answer?',
            'answer' => '   ',
            'sort_order' => 20,
        ]);

        $response = $this->get(route('products.show', $product));
        $html = $response->getContent();
        $graph = collect($this->structuredDataGraph($html));
        $productSchema = $graph->firstWhere('@type', 'Product');
        $breadcrumbSchema = $graph->firstWhere('@type', 'BreadcrumbList');
        $faqSchema = $graph->firstWhere('@type', 'FAQPage');
        $offers = collect($productSchema['offers'] ?? [])->sortBy('priceCurrency')->values();

        $response->assertOk();
        $this->assertNotNull($productSchema);
        $this->assertSame('Schema Detail Tour', $productSchema['name']);
        $this->assertSame('Schema detail summary.', $productSchema['description']);
        $this->assertSame(route('products.show', $product), $productSchema['url']);
        $this->assertSame('Schema Tour Category', $productSchema['category']);
        $this->assertContains($product->thumbnail_url, $productSchema['image']);
        $this->assertCount(2, $offers);
        $this->assertSame('IDR', $offers[0]['priceCurrency']);
        $this->assertEquals(1250000, $offers[0]['price']);
        $this->assertSame('SGD', $offers[1]['priceCurrency']);
        $this->assertEquals(85, $offers[1]['price']);
        $this->assertArrayNotHasKey('availability', $offers[0]);
        $this->assertArrayNotHasKey('availability', $offers[1]);
        $this->assertNotNull($breadcrumbSchema);
        $this->assertSame([1, 2, 3], collect($breadcrumbSchema['itemListElement'])->pluck('position')->all());
        $this->assertSame(route('products.show', $product), $breadcrumbSchema['itemListElement'][2]['item']);
        $this->assertNotNull($faqSchema);
        $this->assertCount(1, $faqSchema['mainEntity']);
        $this->assertSame('Is this schema FAQ visible?', $faqSchema['mainEntity'][0]['name']);
        $this->assertSame('Yes, this answer is visible on the page.', $faqSchema['mainEntity'][0]['acceptedAnswer']['text']);
        $this->assertStringNotContainsString('"availability"', $html);
        $this->assertStringNotContainsString('"aggregateRating"', $html);
        $this->assertStringNotContainsString('"review"', $html);
        $this->assertStringNotContainsString('"sku"', $html);
        $this->assertStringNotContainsString('"gtin"', $html);
    }

    public function test_product_detail_schema_omits_zero_offer_and_faqpage_when_data_is_missing(): void
    {
        $product = $this->createProduct([
            'name' => 'No Offer Schema Tour',
            'status' => 'published',
            'short_description' => 'No offer schema summary.',
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_IDR,
            'price' => 0,
        ]);
        ProductFaq::create([
            'product_id' => $product->id,
            'question' => 'Visible question without answer?',
            'answer' => '',
            'sort_order' => 10,
        ]);

        $response = $this->get(route('products.show', $product));
        $graph = collect($this->structuredDataGraph($response->getContent()));
        $productSchema = $graph->firstWhere('@type', 'Product');

        $response->assertOk();
        $this->assertNotNull($productSchema);
        $this->assertArrayNotHasKey('offers', $productSchema);
        $this->assertNull($graph->firstWhere('@type', 'FAQPage'));
        $response->assertDontSee('"price":0', false);
        $response->assertDontSee('"priceCurrency":"IDR"', false);
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
        $this->assertStringNotContainsString('bookingWhatsappUrl()', $contents);
        $this->assertStringNotContainsString('waNumber', $contents);
        $this->assertStringNotContainsString('https://wa.me/${', $contents);
        $this->assertStringNotContainsString('x-bind:href="bookingWhatsappUrl()"', $contents);
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

        // One bulk menu lookup is expected on a cold cache. The previous
        // per-location composer implementation added three separate queries.
        // +1 for HandleRedirects middleware (cold cache on first request).
        // Phase 7 (B6): +3 for translations eager-load on product + category +
        // destination (traded per-attribute lazy queries for one per-relation).
        $this->assertLessThanOrEqual(25, $queryCount);
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

    private function galleryPrimaryImageTag(string $html): string
    {
        preg_match('/<img\s+[^>]*class="product-detail-gallery__image"[^>]*>/i', $html, $matches);

        $this->assertNotEmpty($matches, 'The Product Detail gallery primary image tag was not rendered.');

        return $matches[0];
    }

    private function structuredDataGraph(string $html): array
    {
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

        $this->assertNotEmpty($matches[1] ?? null, 'Expected JSON-LD script to be present.');

        $data = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);

        return $data['@graph'] ?? [];
    }
}
