<?php

namespace Tests\Feature\Phase6;

use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Support\ContentEntryTemplateRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B12 — Template resolution + render for content entries.
 */
class B12TemplateResolutionTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- registry (unit)

    public function test_registry_resolves_known_keys(): void
    {
        $this->assertSame('default', ContentEntryTemplateRegistry::keyFor('default'));
        $this->assertSame('full-width', ContentEntryTemplateRegistry::keyFor('full-width'));
        $this->assertSame('contained', ContentEntryTemplateRegistry::keyFor('contained'));
    }

    public function test_registry_falls_back_to_default_for_unknown_or_null(): void
    {
        $this->assertSame('default', ContentEntryTemplateRegistry::keyFor('nonsense'));
        $this->assertSame('default', ContentEntryTemplateRegistry::keyFor(''));
        $this->assertSame('default', ContentEntryTemplateRegistry::keyFor(null));
    }

    public function test_registry_schema_types(): void
    {
        $this->assertSame('Article', ContentEntryTemplateRegistry::schemaTypeFor('default'));
        $this->assertSame('Article', ContentEntryTemplateRegistry::schemaTypeFor('contained'));
        $this->assertSame('WebPage', ContentEntryTemplateRegistry::schemaTypeFor('full-width'));
    }

    // ---------------------------------------------------------------- render (feature)

    public function test_default_template_uses_narrow_container_and_article_schema(): void
    {
        $type = $this->type();
        $this->entry($type, ['slug' => 'post', 'template' => null]);

        $this->get(url('/blog/post'))
            ->assertOk()
            ->assertSee('max-w-3xl', false)
            ->assertSee('"@type":"Article"', false);
    }

    public function test_full_width_template_uses_full_container_and_webpage_schema(): void
    {
        $type = $this->type();
        $this->entry($type, ['slug' => 'wide', 'template' => 'full-width']);

        $this->get(url('/blog/wide'))
            ->assertOk()
            ->assertSee('"@type":"WebPage"', false);
    }

    public function test_contained_template_uses_wider_container(): void
    {
        $type = $this->type();
        $this->entry($type, ['slug' => 'mid', 'template' => 'contained']);

        $this->get(url('/blog/mid'))
            ->assertOk()
            ->assertSee('max-w-4xl', false)
            ->assertSee('"@type":"Article"', false);
    }

    public function test_invalid_template_string_falls_back_to_default(): void
    {
        $type = $this->type();
        $this->entry($type, ['slug' => 'weird', 'template' => 'does-not-exist']);

        $this->get(url('/blog/weird'))
            ->assertOk()
            ->assertSee('max-w-3xl', false); // default container
    }

    public function test_structured_data_includes_headline_and_url(): void
    {
        $type = $this->type();
        $this->entry($type, ['title' => 'Schema Post', 'slug' => 'schema-post']);

        $this->get(url('/blog/schema-post'))
            ->assertOk()
            ->assertSee('application/ld+json', false)
            ->assertSee('"headline":"Schema Post"', false)
            ->assertSee('"url":"'.url('blog/schema-post').'"', false);
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
