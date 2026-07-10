<?php

namespace Tests\Feature\Phase7;

use App\Models\PageSection;
use App\Models\SiteSetting;
use App\Services\GlobalSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * C1 — Static analysis / escape audit. Sidecar-translated values escape exactly
 * like the base column when rendered. This is a regression fence so a future
 * refactor cannot accidentally introduce a raw {!! !!} echo of a translation.
 */
class C1EscapeAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_translated_site_setting_is_html_escaped_in_frontend_chrome(): void
    {
        // A malicious CTA translation should render as escaped text, never as HTML.
        $cta = SiteSetting::create([
            'key'       => 'navigation.header.cta_label',
            'label'     => 'Header CTA label',
            'value'     => 'Plan Trip',
            'type'      => 'text',
            'group'     => 'navigation_settings',
            'is_active' => true,
        ]);
        $cta->setTranslation('value', 'id', 'Rencana <script>alert(1)</script>');
        Cache::flush(); // per-locale settings payload

        $html = (string) $this->get('/id')->assertOk()->getContent();

        // The script tag must not survive as executable HTML.
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        // Escaped angle brackets are the acceptable form.
        $this->assertStringContainsString('Rencana &lt;script&gt;', $html);
    }

    public function test_translated_page_section_title_is_html_escaped(): void
    {
        $section = PageSection::create([
            'page_key'    => 'home',
            'section_key' => 'home.hero',
            'title'       => 'Welcome',
            'is_active'   => true,
            'sort_order'  => 0,
        ]);
        $section->setTranslation('title', 'id', 'Selamat <img src=x onerror=alert(1)>');

        $html = (string) $this->get('/id')->assertOk()->getContent();

        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        // Sanity: the escaped form must be present (proves the translated value
        // reached the DOM at all — just as escaped text).
        $this->assertStringContainsString('Selamat &lt;img', $html);
    }

    public function test_structured_data_hex_escapes_translated_business_name(): void
    {
        // The site-wide JSON-LD graph uses JSON_HEX_TAG so a translated
        // business name containing </script> cannot break out of the ld+json
        // block. Regression fence in addition to A1's fix.
        $translation = SiteSetting::create([
            'key'       => 'business.identity.brand_name',
            'label'     => 'Brand name',
            'value'     => 'Bintan Prestige',
            'type'      => 'text',
            'group'     => 'business_identity',
            'is_active' => true,
        ]);
        $translation->setTranslation('value', 'id', 'Evil </script><script>alert(1)</script>');
        Cache::flush();

        $html = (string) $this->get('/id')->assertOk()->getContent();

        $this->assertStringNotContainsString('</script><script>alert(1)</script>', $html);
    }
}
