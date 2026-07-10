<?php

namespace Tests\Feature\Phase7;

use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B10 — SEO i18n. Every locale-observable SEO surface localizes: <html lang>,
 * hreflang alternates + x-default, canonical (locale-prefixed for non-default),
 * OG locale, JSON-LD inLanguage, per-locale sitemap URLs with xhtml:link, and a
 * localized 404 view.
 */
class B10SeoI18nTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------- head lang + OG

    public function test_html_lang_is_dynamic_per_locale(): void
    {
        $this->get('/')->assertOk()->assertSee('<html lang="en">', false);
        $this->get('/id')->assertOk()->assertSee('<html lang="id">', false);
    }

    public function test_og_locale_reflects_current_locale(): void
    {
        $this->get('/')->assertOk()->assertSee('property="og:locale" content="en_US"', false);
        $this->get('/id')->assertOk()->assertSee('property="og:locale" content="id_ID"', false);
    }

    // ------------------------------------------------------------- hreflang alternates

    public function test_hreflang_alternates_include_all_active_locales_on_chrome(): void
    {
        // Home page has no page/entry — falls back to a generic path swap:
        // every active locale gets its own URL prefix.
        $html = (string) $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('rel="alternate" hreflang="en" href="', $html);
        $this->assertStringContainsString('rel="alternate" hreflang="id" href="', $html);
        $this->assertStringContainsString('rel="alternate" hreflang="x-default"', $html);
    }

    public function test_hreflang_on_page_only_lists_published_translations(): void
    {
        $en = Page::create(['title' => 'About', 'slug' => 'about', 'status' => 'published']);
        // ID sibling is draft — must NOT appear in hreflang.
        Page::create([
            'title' => 'Tentang', 'slug' => 'about', 'status' => 'draft',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $html = (string) $this->get('/pages/about')->assertOk()->getContent();

        $this->assertStringContainsString('hreflang="en" href="'.url('/pages/about').'"', $html);
        $this->assertStringNotContainsString('hreflang="id"', $html);
    }

    // ------------------------------------------------------------- canonical

    public function test_localized_page_canonical_is_locale_prefixed(): void
    {
        $en = Page::create(['title' => 'About', 'slug' => 'about', 'status' => 'published']);
        Page::create([
            'title' => 'Tentang', 'slug' => 'about', 'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $this->get('/id/pages/about')
            ->assertOk()
            ->assertSee('rel="canonical" href="'.url('/id/pages/about').'"', false);
    }

    // ------------------------------------------------------------- JSON-LD inLanguage

    public function test_jsonld_page_schema_has_inlanguage(): void
    {
        Page::create(['title' => 'About', 'slug' => 'about', 'status' => 'published']);

        $html = (string) $this->get('/pages/about')->assertOk()->getContent();
        $this->assertStringContainsString('"inLanguage":"en"', $html);

        $en = Page::first();
        Page::create([
            'title' => 'Tentang', 'slug' => 'about', 'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $html = (string) $this->get('/id/pages/about')->assertOk()->getContent();
        $this->assertStringContainsString('"inLanguage":"id"', $html);
    }

    // ------------------------------------------------------------- sitemap

    public function test_sitemap_lists_every_locale_and_emits_xhtml_alternates(): void
    {
        $en = Page::create(['title' => 'About', 'slug' => 'about', 'status' => 'published']);
        Page::create([
            'title' => 'Tentang', 'slug' => 'about', 'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $xml = (string) $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<loc>'.url('/pages/about').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.url('/id/pages/about').'</loc>', $xml);
        $this->assertStringContainsString('hreflang="en" href="'.url('/pages/about').'"', $xml);
        $this->assertStringContainsString('hreflang="id" href="'.url('/id/pages/about').'"', $xml);
        $this->assertStringContainsString('hreflang="x-default"', $xml);
    }

    public function test_sitemap_includes_content_entries_per_locale(): void
    {
        $type = ContentType::create([
            'slug' => 'blog', 'label_singular' => 'Post', 'label_plural' => 'Posts',
            'is_public' => true, 'is_active' => true, 'has_archive' => true,
            'route_base' => 'blog', 'supports' => ['title', 'slug'],
        ]);
        $en = ContentEntry::create([
            'content_type_id' => $type->id, 'title' => 'Hello', 'slug' => 'hello',
            'status' => 'published',
        ]);
        ContentEntry::create([
            'content_type_id' => $type->id, 'title' => 'Halo', 'slug' => 'hello',
            'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $xml = (string) $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<loc>'.url('/blog/hello').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.url('/id/blog/hello').'</loc>', $xml);
    }

    // ------------------------------------------------------------- localized 404

    public function test_404_view_is_localized(): void
    {
        $this->get('/pages/does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found');

        $this->get('/id/pages/does-not-exist')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan');
    }
}
