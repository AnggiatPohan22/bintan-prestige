<?php

namespace Tests\Feature\Phase6;

use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\Page;
use App\Support\ContentQueryResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B13 — Builder bridge: content_query block.
 *
 * A builder block that queries and renders a list of published content entries,
 * usable on pages and on entry bodies alike.
 */
class B13ContentQueryBlockTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- registry

    public function test_content_query_block_is_registered(): void
    {
        $blocks = config('blocks');
        $this->assertArrayHasKey('content_query', $blocks);
        $this->assertNotNull(
            collect($blocks['content_query']['fields'])->firstWhere('key', 'content_type'),
            'content_query must expose a content_type field'
        );
    }

    // ---------------------------------------------------------------- resolver (unit)

    public function test_resolver_returns_published_entries_of_the_type(): void
    {
        $type = $this->type();
        $this->entry($type, ['title' => 'Live One', 'status' => 'published']);
        $this->entry($type, ['title' => 'Draft One', 'status' => 'draft']);

        $result = (new ContentQueryResolver())->resolve(['content_type' => (string) $type->id, 'limit' => 6]);

        $titles = $result->pluck('title')->all();
        $this->assertContains('Live One', $titles);
        $this->assertNotContains('Draft One', $titles);
    }

    public function test_resolver_returns_empty_for_missing_or_invalid_type(): void
    {
        $this->assertCount(0, (new ContentQueryResolver())->resolve([]));
        $this->assertCount(0, (new ContentQueryResolver())->resolve(['content_type' => '']));
        $this->assertCount(0, (new ContentQueryResolver())->resolve(['content_type' => '999999']));
    }

    public function test_resolver_respects_limit(): void
    {
        $type = $this->type();
        for ($i = 0; $i < 5; $i++) {
            $this->entry($type, ['title' => "Post {$i}", 'status' => 'published']);
        }

        $result = (new ContentQueryResolver())->resolve(['content_type' => (string) $type->id, 'limit' => 3]);
        $this->assertCount(3, $result);
    }

    public function test_resolver_orders_by_title(): void
    {
        $type = $this->type();
        $this->entry($type, ['title' => 'Banana', 'status' => 'published']);
        $this->entry($type, ['title' => 'Apple', 'status' => 'published']);
        $this->entry($type, ['title' => 'Cherry', 'status' => 'published']);

        $result = (new ContentQueryResolver())->resolve([
            'content_type' => (string) $type->id, 'orderby' => 'title', 'limit' => 10,
        ]);

        $this->assertSame(['Apple', 'Banana', 'Cherry'], $result->pluck('title')->all());
    }

    public function test_resolver_ignores_non_public_type(): void
    {
        $type = $this->type(['is_public' => false]);
        $this->entry($type, ['title' => 'Hidden', 'status' => 'published']);

        $result = (new ContentQueryResolver())->resolve(['content_type' => (string) $type->id]);
        $this->assertCount(0, $result);
    }

    // ---------------------------------------------------------------- render on a page

    public function test_content_query_block_renders_on_a_page(): void
    {
        $type = $this->type();
        $this->entry($type, ['title' => 'Rendered On Page', 'slug' => 'rendered-on-page', 'status' => 'published']);

        $page = Page::create(['title' => 'Home', 'slug' => 'home-b13', 'status' => 'published']);
        $page->blocks()->create([
            'block_type' => 'content_query',
            'data'       => ['content_type' => (string) $type->id, 'heading' => 'Latest', 'limit' => 6],
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('Latest')
            ->assertSee('Rendered On Page');
    }

    // ---------------------------------------------------------------- render on an entry body

    public function test_content_query_block_renders_on_an_entry_body(): void
    {
        $blogType = $this->type(['slug' => 'blog', 'route_base' => 'blog']);
        $this->entry($blogType, ['title' => 'Featured Post', 'slug' => 'featured-post', 'status' => 'published']);

        $pageType = $this->type(['slug' => 'landing', 'route_base' => 'landing', 'supports' => ['title', 'slug', 'editor']]);
        $host = $this->entry($pageType, ['title' => 'Landing', 'slug' => 'landing-home', 'status' => 'published']);
        $host->blocks()->create([
            'block_type' => 'content_query',
            'data'       => ['content_type' => (string) $blogType->id, 'heading' => 'From The Blog', 'limit' => 6],
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $this->get(url('/landing/landing-home'))
            ->assertOk()
            ->assertSee('From The Blog')
            ->assertSee('Featured Post');
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
            'route_base'     => 'blog-' . uniqid(),
            'supports'       => ['title', 'slug', 'seo'],
        ], $attrs));
    }

    private function entry(ContentType $type, array $attrs = []): ContentEntry
    {
        return $type->entries()->create(array_merge([
            'title'        => 'Entry ' . uniqid(),
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ], $attrs));
    }
}
