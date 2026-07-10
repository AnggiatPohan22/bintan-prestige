<?php

namespace Tests\Feature\Phase7;

use App\Http\Requests\Admin\StoreContentTypeRequest;
use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * A2 — Locale foundation (Phase 7). Chrome only: routes are locale-prefixed and
 * the app locale is set per request, but nothing is translated yet. Guarantees:
 * default-locale URLs are byte-identical to before, /{locale}/... serves the site
 * and sets the app locale, and the fallback order is correct per prefix.
 */
class A2LocaleFoundationTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------- legacy URLs

    public function test_default_locale_home_is_unprefixed_and_ok(): void
    {
        $this->get('/')->assertOk();
        $this->assertSame(url('/'), route('home'));
    }

    public function test_default_locale_products_route_name_is_canonical(): void
    {
        $this->assertSame(url('/products'), route('products.index'));
        $this->get(route('products.index'))->assertOk();
    }

    // ------------------------------------------------------------- localized URLs

    public function test_localized_home_is_served_under_prefix(): void
    {
        $this->get('/id')->assertOk();
    }

    public function test_localized_route_name_is_prefixed(): void
    {
        $this->assertSame(url('/id'), route('id.home'));
        $this->assertSame(url('/id/products'), route('id.products.index'));
    }

    public function test_setlocale_middleware_sets_app_locale_on_prefixed_request(): void
    {
        $this->get('/id/products')->assertOk();
        $this->assertSame('id', app()->getLocale());
    }

    public function test_default_group_keeps_default_locale(): void
    {
        $this->get('/products')->assertOk();
        $this->assertSame(Locales::default(), app()->getLocale());
    }

    // ------------------------------------------------------------- fallback ordering

    public function test_localized_fallback_resolves_entry_without_shadowing_default(): void
    {
        $type = ContentType::create([
            'slug' => 'blog', 'label_singular' => 'Post', 'label_plural' => 'Posts',
            'is_public' => true, 'is_active' => true, 'has_archive' => true,
            'route_base' => 'blog', 'supports' => ['title', 'slug'],
        ]);
        // Under B5 (row-per-locale), each locale needs its own entry row; the
        // prefixed fallback wins over the bare `.*` fallback for /{locale}/... paths.
        $en = ContentEntry::create([
            'content_type_id' => $type->id, 'title' => 'Hello', 'slug' => 'hello',
            'status' => 'published',
        ]);
        ContentEntry::create([
            'content_type_id' => $type->id, 'title' => 'Halo', 'slug' => 'hello',
            'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        // Default locale (bare) resolves the EN entry.
        $this->get('/blog/hello')->assertOk()->assertSee('Hello');
        // Localized prefix resolves the ID entry (prefixed fallback wins over bare .*).
        $this->get('/id/blog/hello')->assertOk()->assertSee('Halo');
        // Unknown localized path still 404s.
        $this->get('/id/nope')->assertNotFound();
    }

    // ------------------------------------------------------------- reserved prefixes

    public function test_locale_codes_are_reserved_route_bases(): void
    {
        $reserved = ContentType::reservedPrefixes();

        $this->assertContains('id', $reserved);
        $this->assertContains('en', $reserved);
    }

    public function test_content_type_route_base_cannot_be_a_locale_code(): void
    {
        $rules = (new StoreContentTypeRequest())->rules();

        $validator = Validator::make(
            ['slug' => 'id', 'label_singular' => 'X', 'label_plural' => 'X', 'route_base' => 'id'],
            ['slug' => $rules['slug'], 'route_base' => $rules['route_base']]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
        $this->assertArrayHasKey('route_base', $validator->errors()->toArray());
    }

    // ------------------------------------------------------------- switcher chrome

    public function test_switcher_renders_both_locales_on_default_home(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-locale-switcher', false)
            ->assertSee('hreflang="id"', false)
            ->assertSee('href="'.url('/id').'"', false);   // switch-to-Indonesian link
    }

    public function test_switcher_points_back_to_default_from_localized_page(): void
    {
        $this->get('/id')
            ->assertOk()
            ->assertSee('href="'.url('/').'"', false);      // switch-to-English link
    }

    // ------------------------------------------------------------- Locales helper

    public function test_locales_helper_reads_config(): void
    {
        $this->assertSame('en', Locales::default());
        $this->assertSame(['id'], Locales::nonDefaultActive());
        $this->assertTrue(Locales::isActive('id'));
        $this->assertFalse(Locales::isActive('zz'));
    }

    public function test_localized_url_swaps_prefix(): void
    {
        // From a default-locale path → add the target prefix.
        $this->assertSame(url('/id/products'), Locales::localizedUrl('id', 'products'));
        $this->assertSame(url('/id'), Locales::localizedUrl('id', '/'));

        // From a localized path → strip when switching back to default.
        $this->assertSame(url('/products'), Locales::localizedUrl('en', 'id/products'));
        $this->assertSame(url('/'), Locales::localizedUrl('en', 'id'));
    }
}
