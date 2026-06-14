<?php

namespace Tests\Feature\Frontend;

use App\Models\Faq;
use App\Models\Category;
use App\Models\Destination;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\SiteAsset;
use App\Models\SiteSetting;
use App\Services\GlobalSettingsService;
use App\Support\BookingCtaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HomepageCmsContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(GlobalSettingsService::class)->forgetAll();
    }

    public function test_homepage_renders_page_section_keys_for_cms_mapping(): void
    {
        foreach ($this->homeSections() as $sectionKey => $sortOrder) {
            PageSection::create([
                'page_key' => 'home',
                'section_key' => $sectionKey,
                'label' => 'Label ' . $sectionKey,
                'title' => 'Title ' . $sectionKey,
                'description' => 'Description ' . $sectionKey,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]);
        }

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('data-section-key="home.hero"', false);
        $response->assertSee('data-section-key="home.popular_tour"', false);
        $response->assertSee('data-section-key="home.popular_products_intro"', false);
        $response->assertSee('data-section-key="home.manual_ads"', false);
        $response->assertSee('data-section-key="home.about_journey"', false);
        $response->assertSee('data-section-key="home.categories_intro"', false);
        $response->assertSee('data-section-key="home.explore_banner"', false);
        $response->assertSee('data-section-key="home.testimonials"', false);
        $response->assertSee('data-section-key="home.faq"', false);
        $response->assertSee('data-section-key="home.footer_cta"', false);
    }

    public function test_homepage_cta_sections_use_page_section_copy_and_global_booking_cta(): void
    {
        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.hero',
            'label' => 'CMS Hero Label',
            'title' => 'CMS Hero Title',
            'description' => 'CMS hero description.',
            'button_text' => 'Start CMS Trip',
            'button_url' => '/products?source=hero-cms',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.manual_ads',
            'label' => 'CMS Special Offer',
            'title' => 'CMS Journey Offer',
            'description' => 'CMS manual ad description for Bintan guests.',
            'button_text' => 'Explore CMS Offer',
            'button_url' => '/products?source=manual-cms',
            'is_active' => true,
            'sort_order' => 30,
        ]);

        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.footer_cta',
            'label' => 'CMS Footer CTA Label',
            'title' => 'CMS Footer CTA Title',
            'description' => 'CMS footer CTA description.',
            'button_text' => 'Open CMS CTA',
            'button_url' => '/products?source=footer-cms',
            'is_active' => true,
            'sort_order' => 90,
        ]);

        $this->siteSetting('booking_cta.enabled', '1', 'boolean', BookingCtaSettings::GROUP);
        $this->siteSetting('booking_cta.use_on_footer', '1', 'boolean', BookingCtaSettings::GROUP);
        $this->siteSetting('booking_cta.footer_label', 'Message CMS Concierge', 'text', BookingCtaSettings::GROUP);
        $this->siteSetting('booking_cta.whatsapp_number_source', 'override', 'select', BookingCtaSettings::GROUP);
        $this->siteSetting('booking_cta.whatsapp_number_override', '628111222333', 'text', BookingCtaSettings::GROUP);
        $this->siteSetting('booking_cta.default_message', 'Hello {site_name}, I want to plan from {page_url}.', 'textarea', BookingCtaSettings::GROUP);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('CMS Hero Label');
        $response->assertSee('CMS Hero Title');
        $response->assertSee('CMS hero description.');
        $response->assertSee('Start CMS Trip');
        $response->assertSee('href="/products?source=hero-cms"', false);
        $response->assertSee('CMS Special Offer');
        $response->assertSee('CMS Journey Offer');
        $response->assertSee('CMS manual ad description for Bintan guests.');
        $response->assertSee('Explore CMS Offer');
        $response->assertSee('href="/products?source=manual-cms"', false);
        $response->assertSee('CMS Footer CTA Label');
        $response->assertSee('CMS Footer CTA Title');
        $response->assertSee('CMS footer CTA description.');
        $response->assertSee('Open CMS CTA');
        $response->assertSee('href="/products?source=footer-cms"', false);
        $response->assertDontSee('href=""', false);
    }

    public function test_homepage_cta_invalid_cms_url_falls_back_safely(): void
    {
        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.hero',
            'label' => 'CMS Hero Label',
            'title' => 'CMS Hero Title',
            'button_text' => 'Unsafe Hero CTA',
            'button_url' => 'javascript:alert(1)',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.footer_cta',
            'label' => 'CMS Footer CTA Label',
            'title' => 'CMS Footer CTA Title',
            'button_text' => 'Unsafe Footer CTA',
            'button_url' => 'javascript:alert(1)',
            'is_active' => true,
            'sort_order' => 90,
        ]);

        $this->siteSetting('booking_cta.enabled', '1', 'boolean', BookingCtaSettings::GROUP);
        $this->siteSetting('booking_cta.use_on_footer', '1', 'boolean', BookingCtaSettings::GROUP);
        $this->siteSetting('booking_cta.whatsapp_number_source', 'override', 'select', BookingCtaSettings::GROUP);
        $this->siteSetting('booking_cta.whatsapp_number_override', '628111222333', 'text', BookingCtaSettings::GROUP);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Unsafe Hero CTA');
        $response->assertDontSee('javascript:alert', false);
        $response->assertSee('Unsafe Footer CTA');
        $response->assertSee('https://wa.me/628111222333', false);
        $response->assertDontSee('href=""', false);
    }

    public function test_homepage_faq_preview_uses_active_faq_module_data(): void
    {
        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.faq',
            'label' => 'CMS FAQ Label',
            'title' => 'CMS FAQ Title',
            'is_active' => true,
            'sort_order' => 80,
        ]);

        Faq::create([
            'question' => 'CMS FAQ question?',
            'answer' => 'CMS FAQ answer.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Faq::create([
            'question' => 'Inactive FAQ question?',
            'answer' => 'Inactive FAQ answer.',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('data-section-key="home.faq"', false);
        $response->assertSee('CMS FAQ Label');
        $response->assertSee('CMS FAQ Title');
        $response->assertSee('CMS FAQ question?');
        $response->assertSee('CMS FAQ answer.');
        $response->assertDontSee('Inactive FAQ question?');
    }

    public function test_homepage_uses_page_section_extra_data_for_supported_copy_cleanup(): void
    {
        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.hero',
            'label' => 'CMS Hero Label',
            'title' => 'CMS Hero Title',
            'description' => 'CMS hero description.',
            'extra_data' => [
                'search_destination_label' => 'CMS Destination Label',
                'search_destination_placeholder' => 'CMS All Destinations',
                'search_category_label' => 'CMS Package Label',
                'search_category_placeholder' => 'CMS All Packages',
                'search_submit_label' => 'Find CMS Packages',
                'search_softcopy' => 'CMS search support copy.',
            ],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.popular_products_intro',
            'label' => 'CMS Products Label',
            'title' => 'CMS Products Title',
            'button_text' => 'Browse CMS Packages',
            'button_url' => '/products?source=products-cms',
            'extra_data' => [
                'empty_title' => 'CMS product empty title',
                'empty_text' => 'CMS product empty text.',
            ],
            'is_active' => true,
            'sort_order' => 20,
        ]);

        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.about_journey',
            'label' => 'CMS Journey Label',
            'title' => 'CMS Journey Title',
            'description' => 'CMS Journey Description',
            'extra_data' => [
                'features' => [
                    [
                        'title' => 'CMS Journey Feature',
                        'text' => 'CMS journey feature text.',
                        'icon' => 'support',
                    ],
                ],
            ],
            'is_active' => true,
            'sort_order' => 40,
        ]);

        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.categories_intro',
            'label' => 'CMS Category Label',
            'title' => 'CMS Category Title',
            'description' => 'CMS Category Description',
            'extra_data' => [
                'empty_title' => 'CMS category empty title',
            ],
            'is_active' => true,
            'sort_order' => 50,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('CMS Destination Label');
        $response->assertSee('CMS All Destinations');
        $response->assertSee('CMS Package Label');
        $response->assertSee('CMS All Packages');
        $response->assertSee('Find CMS Packages');
        $response->assertSee('CMS search support copy.');
        $response->assertSee('Browse CMS Packages');
        $response->assertSee('href="/products?source=products-cms"', false);
        $response->assertSee('CMS product empty title');
        $response->assertSee('CMS product empty text.');
        $response->assertSee('CMS Journey Feature');
        $response->assertSee('CMS journey feature text.');
        $response->assertDontSee('Best Travel Agency');
        $response->assertSee('CMS category empty title');
    }

    public function test_homepage_copy_fallbacks_remain_when_page_section_extra_data_is_empty(): void
    {
        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.hero',
            'label' => 'CMS Hero Label',
            'title' => 'CMS Hero Title',
            'extra_data' => [
                'search_softcopy' => '',
            ],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.about_journey',
            'label' => 'CMS Journey Label',
            'title' => 'CMS Journey Title',
            'extra_data' => [
                'features' => [],
            ],
            'is_active' => true,
            'sort_order' => 40,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Discover premium Bintan packages with local assistance, flexible pickup, and simple WhatsApp booking.');
        $response->assertDontSee('Best Travel Agency');
        $response->assertSee('Products coming soon');
        $response->assertSee('Published tour packages will appear here.');
    }

    public function test_homepage_module_driven_data_stays_module_driven_after_copy_cleanup(): void
    {
        $category = Category::factory()->create([
            'name' => 'CMS Tour Category',
            'is_active' => true,
        ]);
        $destination = Destination::factory()->create([
            'name' => 'CMS Destination',
            'is_active' => true,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'destination_id' => $destination->id,
            'name' => 'Module Product Name',
            'status' => 'published',
        ]);
        Faq::create([
            'question' => 'Module FAQ question?',
            'answer' => 'Module FAQ answer.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('CMS Tour Category');
        $response->assertSee('CMS Destination');
        $response->assertSee('Module Product Name');
        $response->assertSee('Module FAQ question?');
        $response->assertSee('Module FAQ answer.');
    }

    public function test_homepage_product_cards_keep_compact_variant_contract(): void
    {
        $category = Category::factory()->create([
            'name' => 'Compact Tour Category',
            'is_active' => true,
        ]);
        $destination = Destination::factory()->create([
            'name' => 'Treasure Bay',
            'is_active' => true,
        ]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'destination_id' => $destination->id,
            'name' => 'Home Compact Product Card',
            'short_description' => 'Compact homepage product card summary.',
            'thumbnail' => 'products/home-compact-product.jpg',
            'duration' => '4 Hours',
            'status' => 'published',
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'currency' => ProductPrice::CURRENCY_IDR,
            'price' => 450000,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('class="bp-product-card"', false);
        $response->assertSee('data-product-card', false);
        $response->assertSee('data-category-id="' . $category->id . '"', false);
        $response->assertSee('alt="Home Compact Product Card in Treasure Bay"', false);
        $response->assertSee('Compact Tour Category');
        $response->assertSee('Home Compact Product Card');
        $response->assertSee('Compact homepage product card summary.');
        $response->assertSee('Treasure Bay');
        $response->assertSee('4 Hours');
        $response->assertSee('Rp 450.000');
        $response->assertSee('Details');
        $response->assertDontSee('class="product-card"', false);
    }

    public function test_homepage_renderer_handles_complete_cms_and_module_data(): void
    {
        foreach ($this->homeSections() as $sectionKey => $sortOrder) {
            PageSection::create([
                'page_key' => 'home',
                'section_key' => $sectionKey,
                'label' => 'Renderer Label ' . $sectionKey,
                'title' => 'Renderer Title ' . $sectionKey,
                'description' => 'Renderer Description ' . $sectionKey,
                'button_text' => in_array($sectionKey, [
                    'home.popular_tour',
                    'home.popular_products_intro',
                    'home.manual_ads',
                    'home.about_journey',
                    'home.explore_banner',
                    'home.footer_cta',
                ], true) ? 'Renderer CTA' : null,
                'button_url' => in_array($sectionKey, [
                    'home.popular_tour',
                    'home.popular_products_intro',
                    'home.manual_ads',
                    'home.about_journey',
                    'home.explore_banner',
                    'home.footer_cta',
                ], true) ? '/products?renderer=' . str_replace('home.', '', $sectionKey) : null,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]);
        }

        $category = Category::create([
            'name' => 'Renderer Category',
            'slug' => 'renderer-category',
            'is_active' => true,
        ]);

        $destination = Destination::create([
            'name' => 'Renderer Destination',
            'slug' => 'renderer-destination',
            'description' => 'Renderer destination description.',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'destination_id' => $destination->id,
            'name' => 'Renderer Published Product',
            'status' => 'published',
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'destination_id' => $destination->id,
            'name' => 'Renderer Draft Product',
            'status' => 'draft',
        ]);

        Faq::create([
            'question' => 'Renderer FAQ question?',
            'answer' => 'Renderer FAQ answer.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Renderer Title home.hero');
        $response->assertSee('Renderer CTA');
        $response->assertSee('Renderer Published Product');
        $response->assertDontSee('Renderer Draft Product');
        $response->assertSee('Price on request');
        $response->assertDontSee('Rp 0');
        $response->assertSee('Renderer Destination');
        $response->assertSee('Renderer FAQ question?');
        $response->assertSee('Floyd Miles');
        $response->assertDontSee('href=""', false);
    }

    public function test_homepage_missing_and_inactive_sections_use_existing_fallback_behavior(): void
    {
        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.hero',
            'label' => 'Inactive Hero Label',
            'title' => 'Inactive Hero Title',
            'description' => 'Inactive hero description.',
            'is_active' => false,
            'sort_order' => 0,
        ]);

        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.footer_cta',
            'label' => 'Inactive Footer CTA Label',
            'title' => 'Inactive Footer CTA Title',
            'description' => 'Inactive footer CTA description.',
            'is_active' => false,
            'sort_order' => 90,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('BINTAN PRESTIGE');
        $response->assertSee('Luxury Bintan Travel');
        $response->assertSee('Plan Your Perfect Bintan Escape With Us');
        $response->assertDontSee('Inactive Hero Title');
        $response->assertDontSee('Inactive Footer CTA Title');
    }

    public function test_homepage_uses_global_default_media_assets_for_missing_images(): void
    {
        SiteAsset::create([
            'key' => 'default_media.product',
            'label' => 'Product fallback',
            'path' => 'defaults/product.jpg',
            'alt' => 'Default product image',
            'is_active' => true,
        ]);

        SiteAsset::create([
            'key' => 'default_media.destination',
            'label' => 'Destination fallback',
            'path' => 'defaults/destination.jpg',
            'alt' => 'Default destination image',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Fallback Category',
            'slug' => 'fallback-category',
            'is_active' => true,
        ]);

        $destination = Destination::create([
            'name' => 'Fallback Destination',
            'slug' => 'fallback-destination',
            'image' => null,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'destination_id' => $destination->id,
            'name' => 'Fallback Product',
            'thumbnail' => null,
            'status' => 'published',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('storage/defaults/product.jpg', false);
        $response->assertSee('Default product image');
        $response->assertSee('storage/defaults/destination.jpg', false);
        $response->assertSee('Default destination image');
    }

    public function test_homepage_destination_section_uses_active_destination_module_data(): void
    {
        $category = Category::create([
            'name' => 'Tour Packages',
            'slug' => 'tour-packages',
            'is_active' => true,
        ]);

        $activeDestination = Destination::create([
            'name' => 'Lagoi Bay',
            'slug' => 'lagoi-bay',
            'description' => 'Premium resort coast in Bintan.',
            'image' => 'destinations/lagoi-bay.jpg',
            'is_active' => true,
        ]);

        Destination::create([
            'name' => 'Inactive Destination',
            'slug' => 'inactive-destination',
            'is_active' => false,
        ]);

        $deletedDestination = Destination::create([
            'name' => 'Deleted Destination',
            'slug' => 'deleted-destination',
            'is_active' => true,
        ]);
        $deletedDestination->delete();

        Product::factory()->create([
            'category_id' => $category->id,
            'destination_id' => $activeDestination->id,
            'name' => 'Lagoi Private Tour',
            'status' => 'published',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Popular Travel Destinations Available In Bintan');
        $response->assertSee('Lagoi Bay');
        $response->assertSee('data-destination-slug="lagoi-bay"', false);
        $response->assertSee('storage/destinations/lagoi-bay.jpg', false);
        $response->assertSee('destination%5B0%5D=' . $activeDestination->id, false);
        $response->assertSee('01 Package');
        $response->assertDontSee('Inactive Destination');
        $response->assertDontSee('Deleted Destination');
    }

    public function test_homepage_destination_section_has_safe_empty_and_missing_image_states(): void
    {
        $destination = Destination::create([
            'name' => 'Trikora Coast',
            'slug' => 'trikora-coast',
            'description' => 'Quiet coast for scenic island routes.',
            'image' => null,
            'is_active' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Trikora Coast');
        $response->assertSee('Destination Image');
        $response->assertSee('destination%5B0%5D=' . $destination->id, false);

        $destination->update(['is_active' => false]);

        $emptyResponse = $this->get(route('home'));

        $emptyResponse->assertOk();
        $emptyResponse->assertSee('No destinations available yet.');
        $emptyResponse->assertDontSee('Trikora Coast');
    }

    public function test_homepage_review_section_uses_static_fallback_when_no_review_module_exists(): void
    {
        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.testimonials',
            'label' => 'CMS Testimonial Label',
            'title' => 'CMS Testimonial Title',
            'description' => 'CMS testimonial intro.',
            'is_active' => true,
            'sort_order' => 70,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('CMS Testimonial Label');
        $response->assertSee('CMS Testimonial Title');
        $response->assertSee('CMS testimonial intro.');
        $response->assertSee('Floyd Miles');
        $response->assertSee('Guest Traveller');
        $response->assertSee('Our Bintan trip was smooth from pickup to the tour arrangement.');
    }

    public function test_homepage_footer_cta_uses_global_whatsapp_when_cms_url_is_empty(): void
    {
        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.footer_cta',
            'label' => 'CMS Footer CTA Label',
            'title' => 'CMS Footer CTA Title',
            'description' => 'CMS footer CTA description.',
            'button_text' => 'Chat With Concierge',
            'button_url' => '',
            'is_active' => true,
            'sort_order' => 90,
        ]);

        $this->siteSetting('booking_cta.enabled', '1', 'boolean', BookingCtaSettings::GROUP);
        $this->siteSetting('booking_cta.use_on_footer', '1', 'boolean', BookingCtaSettings::GROUP);
        $this->siteSetting('booking_cta.whatsapp_number_source', 'override', 'select', BookingCtaSettings::GROUP);
        $this->siteSetting('booking_cta.whatsapp_number_override', '628111222333', 'text', BookingCtaSettings::GROUP);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('CMS Footer CTA Label');
        $response->assertSee('CMS Footer CTA Title');
        $response->assertSee('CMS footer CTA description.');
        $response->assertSee('Chat With Concierge');
        $response->assertSee('https://wa.me/628111222333', false);
        $response->assertDontSee('href=""', false);
    }

    public function test_frontend_and_admin_route_contracts_remain_registered(): void
    {
        $this->assertTrue(Route::has('home'));
        $this->assertTrue(Route::has('products.index'));
        $this->assertTrue(Route::has('products.show'));
        $this->assertTrue(Route::has('admin.dashboard'));
        $this->assertSame(url('/'), route('home'));
        $this->assertSame(url('/products'), route('products.index'));
    }

    public function test_homepage_blade_files_do_not_query_database_directly(): void
    {
        $files = [
            resource_path('views/frontend/home.blade.php'),
            resource_path('views/frontend/sections/popular-products.blade.php'),
            resource_path('views/frontend/sections/about-journey.blade.php'),
            resource_path('views/frontend/sections/categories.blade.php'),
            resource_path('views/frontend/sections/popular-tour.blade.php'),
            resource_path('views/frontend/sections/explore-banner.blade.php'),
            resource_path('views/frontend/sections/testimonials.blade.php'),
            resource_path('views/frontend/partials/manual-ads.blade.php'),
            resource_path('views/frontend/partials/footer.blade.php'),
            resource_path('views/frontend/components/product-card.blade.php'),
            resource_path('views/frontend/components/product-price.blade.php'),
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertStringNotContainsString('::query(', $contents, $file);
            $this->assertStringNotContainsString('DB::', $contents, $file);
            $this->assertStringNotContainsString('->where(', $contents, $file);
            $this->assertDoesNotMatchRegularExpression('/\\\\App\\\\Models\\\\[A-Za-z]+::/', $contents, $file);
        }
    }

    private function siteSetting(string $key, string $value, string $type, string $group): SiteSetting
    {
        return SiteSetting::create([
            'key' => $key,
            'label' => $key,
            'value' => $value,
            'type' => $type,
            'group' => $group,
            'is_active' => true,
        ]);
    }

    private function homeSections(): array
    {
        return [
            'home.hero' => 0,
            'home.popular_tour' => 10,
            'home.popular_products_intro' => 20,
            'home.manual_ads' => 30,
            'home.about_journey' => 40,
            'home.categories_intro' => 50,
            'home.explore_banner' => 60,
            'home.testimonials' => 70,
            'home.faq' => 80,
            'home.footer_cta' => 90,
        ];
    }
}
