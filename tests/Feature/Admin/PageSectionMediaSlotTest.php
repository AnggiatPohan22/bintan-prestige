<?php

namespace Tests\Feature\Admin;

use App\Models\PageSection;
use App\Models\SiteAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PageSectionMediaSlotTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_page_sections_syncs_registered_product_pages(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.page-sections.index', ['page' => 'products.show']));

        $response->assertOk();
        $response->assertSee('Product Listing');
        $response->assertSee('Product Detail');
        $response->assertSee('products.show.hero');
        $response->assertSee('products.show.gallery');
        $response->assertSee('products.show.booking');
        $response->assertSee('No image input');
        $response->assertDontSee('0 / 10 media item(s)');

        $this->assertDatabaseHas('page_sections', [
            'page_key' => 'products.index',
            'section_key' => 'products.index.hero',
        ]);
        $this->assertDatabaseHas('page_sections', [
            'page_key' => 'products.show',
            'section_key' => 'products.show.booking',
        ]);
    }

    public function test_admin_page_sections_index_can_filter_by_page_key(): void
    {
        $admin = User::factory()->create();

        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.hero',
            'label' => 'Luxury Bintan Travel',
            'title' => 'BINTAN PRESTIGE',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        PageSection::create([
            'page_key' => 'products',
            'section_key' => 'products.index.hero',
            'label' => 'Packages',
            'title' => 'Explore Bintan Packages',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.page-sections.index', ['page' => 'products']));

        $response->assertOk();
        $response->assertSee('Home');
        $response->assertSee('Products');
        $response->assertSee('products.index.hero');
        $response->assertSee('Explore Bintan Packages');
        $response->assertDontSee('home.hero');
        $response->assertDontSee('BINTAN PRESTIGE');
    }

    public function test_admin_page_sections_index_defaults_to_first_available_page(): void
    {
        $admin = User::factory()->create();

        PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.hero',
            'label' => 'Luxury Bintan Travel',
            'title' => 'BINTAN PRESTIGE',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        PageSection::create([
            'page_key' => 'products',
            'section_key' => 'products.index.hero',
            'label' => 'Packages',
            'title' => 'Explore Bintan Packages',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.page-sections.index'));

        $response->assertOk();
        $response->assertSee('home.hero');
        $response->assertDontSee('products.index.hero');
    }

    public function test_admin_can_see_homepage_media_slots_on_section_edit_screen(): void
    {
        $admin = User::factory()->create();
        $section = PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.popular_tour',
            'label' => 'Most Popular Tour',
            'title' => 'Popular Tour',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.page-sections.edit', $section));

        $response->assertOk();
        $response->assertSee('Logo website global');
        $response->assertSee('Frame kiri atas');
        $response->assertSee('Frame kanan bawah');
        $response->assertDontSee('Section gallery images');
    }

    public function test_admin_does_not_show_image_uploads_for_sections_without_frontend_media(): void
    {
        $admin = User::factory()->create();
        $section = PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.popular_products_intro',
            'label' => 'Most Popular Tour Packages',
            'title' => 'Something Amazing Waiting For You',
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.page-sections.edit', $section));

        $response->assertOk();
        $response->assertSee('No section image upload for this layout');
        $response->assertDontSee('Legacy image upload');
        $response->assertDontSee('Section gallery images');
    }

    public function test_admin_can_upload_section_frame_slot_without_updating_global_logo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $section = PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.popular_tour',
            'label' => 'Most Popular Tour',
            'title' => 'Popular Tour',
            'description' => 'Section description',
            'button_text' => 'Take a tour',
            'button_url' => '/products',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.page-sections.update', $section), [
                'label' => 'Most Popular Tour',
                'title' => 'Popular Tour',
                'description' => 'Section description',
                'button_text' => 'Take a tour',
                'button_url' => '/products',
                'is_active' => '1',
                'sort_order' => '10',
                'slot_uploads' => [
                    'frame' => [
                        'left_wide' => UploadedFile::fake()->image('left-wide.jpg', 800, 550),
                    ],
                ],
                'slot_object_fits' => [
                    'frame' => [
                        'left_wide' => 'contain',
                    ],
                ],
                'slot_object_positions' => [
                    'frame' => [
                        'left_wide' => 'center top',
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.page-sections.edit', $section));

        $this->assertDatabaseEmpty('site_assets');

        $this->assertDatabaseHas('page_section_media', [
            'page_section_id' => $section->id,
            'role' => 'frame',
            'slot_key' => 'left_wide',
            'label' => 'Frame kiri atas',
            'object_fit' => 'contain',
            'object_position' => 'center top',
            'is_active' => true,
        ]);

        Storage::disk('public')->assertExists(
            $section->fresh()->mediaSlot('frame', 'left_wide')->path
        );
    }

    public function test_admin_can_update_existing_slot_image_display_options_without_reupload(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $section = PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.popular_tour',
            'label' => 'Most Popular Tour',
            'title' => 'Popular Tour',
            'description' => 'Section description',
            'button_text' => 'Take a tour',
            'button_url' => '/products',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $section->media()->create([
            'role' => 'frame',
            'slot_key' => 'left_wide',
            'label' => 'Frame kiri atas',
            'path' => 'page-sections/home/popular-tour/frame/left-wide.jpg',
            'alt' => 'Frame kiri atas',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.page-sections.update', $section), [
                'label' => 'Most Popular Tour',
                'title' => 'Popular Tour',
                'description' => 'Section description',
                'button_text' => 'Take a tour',
                'button_url' => '/products',
                'is_active' => '1',
                'sort_order' => '10',
                'slot_object_fits' => [
                    'frame' => [
                        'left_wide' => 'scale-down',
                    ],
                ],
                'slot_object_positions' => [
                    'frame' => [
                        'left_wide' => 'right bottom',
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.page-sections.edit', $section));

        $this->assertDatabaseHas('page_section_media', [
            'page_section_id' => $section->id,
            'role' => 'frame',
            'slot_key' => 'left_wide',
            'path' => 'page-sections/home/popular-tour/frame/left-wide.jpg',
            'object_fit' => 'scale-down',
            'object_position' => 'right bottom',
        ]);
    }

    public function test_about_journey_slot_upload_is_rendered_on_frontend(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $section = PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.about_journey',
            'label' => 'Dream Your Next Trip',
            'title' => 'Discover Bintan',
            'description' => 'Journey section description',
            'button_text' => 'Book',
            'button_url' => '/products',
            'is_active' => true,
            'sort_order' => 40,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.page-sections.update', $section), [
                'label' => 'Dream Your Next Trip',
                'title' => 'Discover Bintan',
                'description' => 'Journey section description',
                'button_text' => 'Book',
                'button_url' => '/products',
                'is_active' => '1',
                'sort_order' => '40',
                'slot_uploads' => [
                    'frame' => [
                        'main_visual' => UploadedFile::fake()->image('journey-main.jpg', 900, 1100),
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.page-sections.edit', $section));

        $section = $section->fresh()->load('media');
        $mainVisual = $section->mediaSlot('frame', 'main_visual');

        $this->assertNotNull($mainVisual);
        Storage::disk('public')->assertExists($mainVisual->path);

        $view = $this->view('frontend.sections.about-journey', [
            'sections' => collect(['home.about_journey' => $section]),
            'siteAssets' => collect(),
            'defaultMediaSettings' => [],
        ]);

        $view->assertSee($mainVisual->path, false);
        $view->assertSee('object-fit: cover; object-position: center center', false);
        $view->assertDontSee('Section placeholder image');
    }

    public function test_explore_banner_renders_mobile_background_slot(): void
    {
        $section = PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.explore_banner',
            'label' => 'Next Adventure Destination',
            'title' => 'Explore Bintan',
            'is_active' => true,
            'sort_order' => 60,
        ]);

        $section->media()->create([
            'role' => 'background',
            'slot_key' => 'desktop_background',
            'label' => 'Background banner desktop',
            'path' => 'page-sections/home/explore-banner/desktop.jpg',
            'alt' => 'Desktop banner',
            'is_active' => true,
        ]);
        $section->media()->create([
            'role' => 'background',
            'slot_key' => 'mobile_background',
            'label' => 'Background banner mobile',
            'path' => 'page-sections/home/explore-banner/mobile.jpg',
            'alt' => 'Mobile banner',
            'is_active' => true,
        ]);

        $view = $this->view('frontend.sections.explore-banner', [
            'sections' => collect(['home.explore_banner' => $section->fresh()->load('media')]),
            'siteAssets' => collect(),
            'defaultMediaSettings' => [],
        ]);

        $view->assertSee('media="(max-width: 767px)"', false);
        $view->assertSee('page-sections/home/explore-banner/mobile.jpg', false);
        $view->assertSee('page-sections/home/explore-banner/desktop.jpg', false);
    }

    public function test_faq_section_uses_page_section_title_and_image_on_frontend(): void
    {
        $section = PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.faq',
            'label' => 'Before your journey',
            'title' => 'All you should know before embarking on your Bintan journey',
            'is_active' => true,
            'sort_order' => 80,
        ]);

        $section->media()->create([
            'role' => 'frame',
            'slot_key' => 'main_visual',
            'label' => 'FAQ preview image',
            'path' => 'page-sections/home/faq/faq.jpg',
            'alt' => 'FAQ preview image',
            'object_fit' => 'contain',
            'object_position' => 'center top',
            'is_active' => true,
        ]);

        $view = $this->view('frontend.home', [
            'sections' => collect(['home.faq' => $section->fresh()->load('media')]),
            'heroBackgroundUrl' => null,
            'categories' => collect(),
            'destinations' => collect(),
            'featuredProducts' => collect(),
            'popularProducts' => collect(),
            'homeProducts' => collect(),
            'homeProductCategories' => collect(),
            'faqs' => collect(),
            'siteAssets' => collect(),
            'defaultMediaSettings' => [],
        ]);

        $view->assertSee('All you should know before embarking on your Bintan journey');
        $view->assertSee('page-sections/home/faq/faq.jpg', false);
        $view->assertSee('object-fit: contain; object-position: center top', false);
    }

    public function test_footer_cta_uses_page_section_content_and_image_on_frontend(): void
    {
        $section = PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.footer_cta',
            'label' => 'Explore Tour',
            'title' => 'Plan Your Perfect Bintan Escape With Us',
            'description' => 'Tell us your arrival point, travel date, and preferred experience.',
            'is_active' => true,
            'sort_order' => 90,
        ]);

        $section->media()->create([
            'role' => 'frame',
            'slot_key' => 'main_visual',
            'label' => 'Footer CTA visual',
            'path' => 'page-sections/home/footer-cta/footer.jpg',
            'alt' => 'Footer CTA visual',
            'is_active' => true,
        ]);

        $view = $this->view('frontend.partials.footer', [
            'sections' => collect(['home.footer_cta' => $section->fresh()->load('media')]),
            'siteAssets' => collect(),
            'defaultMediaSettings' => [],
            'businessIdentity' => ['brand_name' => 'Bintan Prestige'],
            'contactInformation' => [],
            'activeSocialMediaLinks' => [],
        ]);

        $view->assertSee('Explore Tour');
        $view->assertSee('Plan Your Perfect Bintan Escape With Us');
        $view->assertSee('Tell us your arrival point, travel date, and preferred experience.');
        $view->assertSee('page-sections/home/footer-cta/footer.jpg', false);
    }

    public function test_admin_page_sections_index_uses_frontend_display_order(): void
    {
        $admin = User::factory()->create();

        $footerCta = PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.footer_cta',
            'label' => 'Explore Tour',
            'title' => 'Plan Your Perfect Bintan Escape With Us',
            'is_active' => true,
            'sort_order' => 80,
        ]);
        $faq = PageSection::create([
            'page_key' => 'home',
            'section_key' => 'home.faq',
            'label' => 'Before your journey',
            'title' => 'All you should know before embarking on your Bintan journey',
            'is_active' => true,
            'sort_order' => 90,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.page-sections.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'home.faq',
            'All you should know before embarking on your Bintan journey',
            'home.footer_cta',
            'Plan Your Perfect Bintan Escape With Us',
        ]);
    }

    public function test_admin_can_manage_global_site_logo_from_global_assets_settings(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.settings.global-assets.site-logo.update'), [
                'logos' => [
                    'main' => UploadedFile::fake()->image('logo.jpg', 400, 220),
                    'light' => UploadedFile::fake()->image('logo-light.png', 400, 220),
                ],
                'logo_alts' => [
                    'main' => 'Bintan Prestige logo',
                    'light' => 'Bintan Prestige light logo',
                ],
            ]);

        $response->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'site-logo']));

        $this->assertDatabaseHas('site_assets', [
            'key' => 'site.logo',
            'label' => 'Main website logo',
            'alt' => 'Bintan Prestige logo',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('site_assets', [
            'key' => 'site.logo.light',
            'label' => 'Light logo',
            'alt' => 'Bintan Prestige light logo',
            'is_active' => true,
        ]);

        $siteLogo = SiteAsset::where('key', 'site.logo')->firstOrFail();
        $lightLogo = SiteAsset::where('key', 'site.logo.light')->firstOrFail();
        Storage::disk('public')->assertExists($siteLogo->path);
        Storage::disk('public')->assertExists($lightLogo->path);

        $deleteResponse = $this->actingAs($admin)
            ->delete(route('admin.settings.global-assets.site-logo.destroy', 'light'));

        $deleteResponse->assertRedirect(route('admin.settings.global-assets.edit', ['tab' => 'site-logo']));

        $this->assertDatabaseHas('site_assets', [
            'key' => 'site.logo.light',
            'path' => null,
            'is_active' => false,
        ]);

        Storage::disk('public')->assertMissing($lightLogo->path);
        Storage::disk('public')->assertExists($siteLogo->path);
    }
}
