<?php

namespace Tests\Feature\Phase6;

use App\Models\ContentEntry;
use App\Models\ContentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B11 — Public frontend routing for content entries via Route::fallback().
 *
 * Key guarantee: the fallback is the lowest-priority match, so it never shadows
 * an explicit route (home, products, pages, forms) or the admin routes.
 */
class B11FrontendRoutingTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- archive

    public function test_archive_lists_published_entries(): void
    {
        $type = $this->type(['route_base' => 'blog', 'has_archive' => true]);
        $this->entry($type, ['title' => 'Visible Post', 'slug' => 'visible-post', 'status' => 'published']);
        $this->entry($type, ['title' => 'Draft Post', 'slug' => 'draft-post', 'status' => 'draft']);

        $this->get(url('/blog'))
            ->assertOk()
            ->assertSee('Visible Post')
            ->assertDontSee('Draft Post');
    }

    public function test_archive_is_404_when_type_has_no_archive(): void
    {
        $this->type(['route_base' => 'noarchive', 'has_archive' => false]);

        $this->get(url('/noarchive'))->assertNotFound();
    }

    public function test_archive_is_404_for_non_public_type(): void
    {
        $this->type(['route_base' => 'secret', 'is_public' => false]);

        $this->get(url('/secret'))->assertNotFound();
    }

    public function test_archive_is_404_for_unknown_route_base(): void
    {
        $this->get(url('/does-not-exist'))->assertNotFound();
    }

    // ---------------------------------------------------------------- single

    public function test_single_renders_published_entry(): void
    {
        $type = $this->type(['route_base' => 'blog']);
        $this->entry($type, ['title' => 'Hello World', 'slug' => 'hello-world', 'status' => 'published']);

        $this->get(url('/blog/hello-world'))
            ->assertOk()
            ->assertSee('Hello World');
    }

    public function test_single_is_404_for_draft_entry(): void
    {
        $type = $this->type(['route_base' => 'blog']);
        $this->entry($type, ['title' => 'Draft', 'slug' => 'draft', 'status' => 'draft']);

        $this->get(url('/blog/draft'))->assertNotFound();
    }

    public function test_single_is_404_for_future_scheduled_entry(): void
    {
        $type = $this->type(['route_base' => 'blog']);
        $this->entry($type, [
            'title' => 'Future', 'slug' => 'future',
            'status' => 'scheduled', 'published_at' => now()->addWeek(),
        ]);

        $this->get(url('/blog/future'))->assertNotFound();
    }

    public function test_single_is_404_for_unknown_slug(): void
    {
        $this->type(['route_base' => 'blog']);

        $this->get(url('/blog/nope'))->assertNotFound();
    }

    public function test_single_renders_builder_block_body_for_editor_type(): void
    {
        $type  = $this->type(['route_base' => 'article', 'supports' => ['title', 'slug', 'editor']]);
        $entry = $this->entry($type, ['title' => 'Article One', 'slug' => 'article-one', 'status' => 'published']);
        $entry->blocks()->create([
            'block_type' => 'heading',
            'data'       => ['text' => 'Rendered From Builder'],
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $this->get(url('/article/article-one'))
            ->assertOk()
            ->assertSee('Rendered From Builder');
    }

    // ---------------------------------------------------------------- route ordering (regression)

    public function test_fallback_does_not_shadow_home(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_fallback_does_not_shadow_products_index(): void
    {
        // /products must still hit the product listing, not the entry fallback,
        // even if a mischievous type somehow used it (blocked by RESERVED_PREFIXES).
        $this->get(route('products.index'))->assertOk();
    }

    public function test_fallback_does_not_shadow_a_real_page(): void
    {
        $page = \App\Models\Page::create([
            'title'  => 'About Us',
            'slug'   => 'about-us',
            'status' => 'published',
        ]);

        $this->get(route('pages.show', $page->slug))->assertOk()->assertSee('About Us');
    }

    // ---------------------------------------------------------------- model helper

    public function test_public_url_helper(): void
    {
        $type  = $this->type(['route_base' => 'blog']);
        $entry = $this->entry($type, ['slug' => 'my-slug', 'status' => 'published']);

        $this->assertSame(url('blog/my-slug'), $entry->publicUrl());
    }

    public function test_public_url_is_null_without_route_base(): void
    {
        $type  = $this->type(['route_base' => null, 'is_public' => false]);
        $entry = $this->entry($type, ['slug' => 'x', 'status' => 'published']);

        $this->assertNull($entry->publicUrl());
    }

    // ---------------------------------------------------------------- helpers

    private function type(array $attrs = []): ContentType
    {
        return ContentType::create(array_merge([
            'slug'           => 'blog-' . uniqid(),
            'label_singular' => 'Blog Post',
            'label_plural'   => 'Blog Posts',
            'is_public'      => true,
            'is_active'      => true,
            'has_archive'    => true,
            'route_base'     => 'blog',
            'supports'       => ['title', 'slug', 'seo'],
        ], $attrs));
    }

    private function entry(ContentType $type, array $attrs = []): ContentEntry
    {
        return $type->entries()->create(array_merge([
            'title'  => 'Entry ' . uniqid(),
            'status' => 'published',
        ], $attrs));
    }
}
