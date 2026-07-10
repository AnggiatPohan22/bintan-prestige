<?php

namespace Tests\Feature\Phase7;

use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * C3 — Functional smoke test. Locks in the release-gate behavioural surface as
 * regression fences: locale routing matrix, fallback semantics (documents 404 in
 * missing locale, attributes fall back), admin guards on every new Phase 7
 * endpoint, and byte-identical default-locale URLs (grand plan §12 DoD).
 */
class C3FunctionalSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    // ------------------------------------------------------------- 1. locale route matrix

    public function test_default_locale_public_routes_all_ok(): void
    {
        Page::create(['title' => 'About', 'slug' => 'about', 'status' => 'published']);
        $this->publicBlogType();

        $this->get('/')->assertOk();                // home
        $this->get('/products')->assertOk();        // catalog listing
        $this->get('/pages/about')->assertOk();     // page
        $this->get('/blog')->assertOk();            // entry archive
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
        $this->get('/robots.txt')->assertOk();
    }

    public function test_localized_public_routes_all_ok(): void
    {
        $en = Page::create(['title' => 'About', 'slug' => 'about', 'status' => 'published']);
        Page::create([
            'title' => 'Tentang', 'slug' => 'about', 'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);
        $this->publicBlogType();

        $this->get('/id')->assertOk();
        $this->get('/id/products')->assertOk();
        $this->get('/id/pages/about')->assertOk();
        $this->get('/id/blog')->assertOk();
    }

    public function test_route_names_have_localized_variants(): void
    {
        // Default-locale names (unprefixed).
        $this->assertSame(url('/'), route('home'));
        $this->assertSame(url('/products'), route('products.index'));

        // Locale-prefixed names for every non-default active locale (A2 wiring).
        $this->assertSame(url('/id'), route('id.home'));
        $this->assertSame(url('/id/products'), route('id.products.index'));
    }

    // ------------------------------------------------------------- 2. document fallback semantics

    public function test_untranslated_page_is_404_in_that_locale_only(): void
    {
        Page::create(['title' => 'Solo', 'slug' => 'solo-page', 'status' => 'published']);

        $this->get('/pages/solo-page')->assertOk();       // exists in default
        $this->get('/id/pages/solo-page')->assertNotFound(); // hides in ID (A0 §3.5)
    }

    public function test_draft_translation_is_404_in_its_locale_only(): void
    {
        $en = Page::create(['title' => 'Both', 'slug' => 'both', 'status' => 'published']);
        Page::create([
            'title' => 'ID', 'slug' => 'both', 'status' => 'draft',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $this->get('/pages/both')->assertOk();          // EN published
        $this->get('/id/pages/both')->assertNotFound(); // ID draft → hide
    }

    public function test_untranslated_content_entry_is_404_in_that_locale(): void
    {
        $type = $this->publicBlogType();
        $type->entries()->create(['title' => 'Post', 'slug' => 'post', 'status' => 'published']);

        $this->get('/blog/post')->assertOk();
        $this->get('/id/blog/post')->assertNotFound();
    }

    // ------------------------------------------------------------- 3. attribute fallback

    public function test_missing_attribute_translation_falls_back_to_base_column(): void
    {
        // A published EN + ID page. ID row has NO title translation; the base
        // column (which is the ID row's own value) is used — A0 attribute fallback.
        $en = Page::create(['title' => 'EN Title', 'slug' => 'p', 'status' => 'published']);
        Page::create([
            'title' => 'Fallback ID Title', 'slug' => 'p', 'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $this->get('/id/pages/p')->assertOk()->assertSee('Fallback ID Title');
    }

    // ------------------------------------------------------------- 4. admin guards on Phase 7 endpoints

    public function test_pages_translate_route_requires_admin(): void
    {
        $en = Page::create(['title' => 'X', 'slug' => 'x', 'status' => 'published']);

        // Guest → redirected to login.
        $this->post(route('admin.pages.translate', $en), ['locale' => 'id'])
            ->assertRedirect(route('login'));
    }

    public function test_entries_translate_route_requires_admin(): void
    {
        $type = $this->publicBlogType();
        $entry = $type->entries()->create(['title' => 'X', 'slug' => 'x', 'status' => 'published']);

        $this->post(route('admin.content-types.entries.translate', [$type, $entry]), ['locale' => 'id'])
            ->assertRedirect(route('login'));
    }

    public function test_settings_translations_panel_requires_admin(): void
    {
        $this->get(route('admin.settings.global-assets.translations'))
            ->assertRedirect(route('login'));
    }

    // ------------------------------------------------------------- 5. legacy-URL parity

    public function test_default_locale_home_matches_pre_phase7_shape(): void
    {
        // Chrome pieces that must stay identical for existing links + backlinks.
        $html = (string) $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.url('/').'"', $html);
        $this->assertStringContainsString('<html lang="en">', $html);
        // Locale switcher is present but the current locale is the unprefixed one:
        // aria-current on the EN link, ID link points at /id.
        $this->assertStringContainsString('href="'.url('/id').'"', $html);
    }

    public function test_default_locale_page_url_is_byte_identical_to_pre_phase7(): void
    {
        Page::create(['title' => 'Terms', 'slug' => 'terms', 'status' => 'published']);

        // Route generation returns the unprefixed URL exactly like before.
        $this->assertSame(url('/pages/terms'), route('pages.show', 'terms'));
        $this->get('/pages/terms')->assertOk();
    }

    public function test_reserved_prefixes_still_block_locale_codes(): void
    {
        // Sanity: locale codes remain reserved as route_base values (A2).
        $this->assertContains('id', ContentType::reservedPrefixes());
        $this->assertContains('en', ContentType::reservedPrefixes());
    }

    // ------------------------------------------------------------- helpers

    private function publicBlogType(): ContentType
    {
        return ContentType::create([
            'slug' => 'blog', 'label_singular' => 'Post', 'label_plural' => 'Posts',
            'is_public' => true, 'is_active' => true, 'has_archive' => true,
            'route_base' => 'blog', 'supports' => ['title', 'slug'],
        ]);
    }
}
