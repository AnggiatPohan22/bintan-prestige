<?php

namespace Tests\Feature\Frontend;

use App\Models\Faq;
use App\Models\PageSection;
use App\Models\SiteSetting;
use App\Services\GlobalSettingsService;
use App\Support\BookingCtaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
