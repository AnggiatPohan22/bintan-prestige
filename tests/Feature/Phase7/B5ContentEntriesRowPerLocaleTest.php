<?php

namespace Tests\Feature\Phase7;

use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\PageBlock;
use App\Models\User;
use App\Support\ContentQueryResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B5 — Content Entries row-per-locale. Same pattern as B4 (pages): each locale is
 * a full entry linked by translation_group_id. Archive + single are locale-aware;
 * builder body copies on "Translate to…"; content_query filters by locale.
 */
class B5ContentEntriesRowPerLocaleTest extends TestCase
{
    use RefreshDatabase;

    private function blogType(): ContentType
    {
        return ContentType::create([
            'slug' => 'blog', 'label_singular' => 'Post', 'label_plural' => 'Posts',
            'is_public' => true, 'is_active' => true, 'has_archive' => true,
            'route_base' => 'blog', 'supports' => ['title', 'slug', 'editor'],
        ]);
    }

    private function entry(ContentType $type, array $attrs = []): ContentEntry
    {
        return $type->entries()->create(array_merge([
            'title'  => 'Hello World',
            'slug'   => 'hello-world',
            'status' => 'published',
        ], $attrs));
    }

    // ------------------------------------------------------------- model defaults

    public function test_new_entry_gets_default_locale_and_group(): void
    {
        $e = $this->entry($this->blogType());

        $this->assertSame('en', $e->locale);
        $this->assertSame(26, strlen((string) $e->translation_group_id));
    }

    // ------------------------------------------------------------- slug uniqueness

    public function test_same_slug_allowed_across_locales(): void
    {
        $type = $this->blogType();
        $en = $this->entry($type);
        $id = $type->entries()->create([
            'title' => 'Halo Dunia', 'slug' => 'hello-world', 'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $this->assertNotSame($en->id, $id->id);
    }

    public function test_duplicate_slug_in_same_locale_and_type_is_rejected(): void
    {
        $type = $this->blogType();
        $this->entry($type);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $type->entries()->create(['title' => 'Dup', 'slug' => 'hello-world', 'status' => 'draft']);
    }

    // ------------------------------------------------------------- routing

    public function test_archive_lists_only_current_locale_entries(): void
    {
        $type = $this->blogType();
        $en = $this->entry($type, ['title' => 'English Post']);
        $type->entries()->create([
            'title' => 'Postingan ID', 'slug' => 'postingan-id', 'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $this->get('/blog')->assertOk()->assertSee('English Post')->assertDontSee('Postingan ID');
        $this->get('/id/blog')->assertOk()->assertSee('Postingan ID')->assertDontSee('English Post');
    }

    public function test_single_entry_served_under_current_locale(): void
    {
        $type = $this->blogType();
        $en = $this->entry($type, ['title' => 'English Post']);
        $type->entries()->create([
            'title' => 'Postingan ID', 'slug' => 'hello-world', 'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $this->get('/blog/hello-world')->assertOk()->assertSee('English Post');
        $this->get('/id/blog/hello-world')->assertOk()->assertSee('Postingan ID');
    }

    public function test_untranslated_entry_is_404_in_that_locale(): void
    {
        $this->entry($this->blogType()); // en only
        $this->get('/id/blog/hello-world')->assertNotFound();
    }

    // ------------------------------------------------------------- publicUrl

    public function test_public_url_is_locale_aware(): void
    {
        $type = $this->blogType();
        $en = $this->entry($type);
        $id = $type->entries()->create([
            'title' => 'X', 'slug' => 'hello-world', 'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $this->assertSame(url('/blog/hello-world'), $en->publicUrl());
        $this->assertSame(url('/id/blog/hello-world'), $id->publicUrl());
    }

    // ------------------------------------------------------------- content_query

    public function test_content_query_filters_by_current_locale(): void
    {
        $type = $this->blogType();
        $en = $this->entry($type, ['title' => 'English Post']);
        $type->entries()->create([
            'title' => 'Postingan ID', 'slug' => 'p-id', 'status' => 'published',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        app()->setLocale('id');
        $ids = (new ContentQueryResolver())->resolve(['content_type' => $type->id])->pluck('locale')->unique()->all();
        $this->assertSame(['id'], $ids);

        app()->setLocale('en');
        $ens = (new ContentQueryResolver())->resolve(['content_type' => $type->id])->pluck('locale')->unique()->all();
        $this->assertSame(['en'], $ens);
    }

    // ------------------------------------------------------------- admin translate action

    public function test_admin_translate_copies_entry_and_blocks_as_draft(): void
    {
        $type = $this->blogType();
        $en = $this->entry($type, ['title' => 'English Post']);
        $en->blocks()->create([
            'blockable_type' => $en->getMorphClass(),
            'blockable_id' => $en->id,
            'block_type' => 'heading',
            'data' => ['text' => 'Hello'],
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.content-types.entries.translate', [$type, $en]), ['locale' => 'id'])
            ->assertRedirect();

        $id = $en->translationIn('id');
        $this->assertNotNull($id);
        $this->assertSame('draft', $id->status);
        $this->assertSame($en->translation_group_id, $id->translation_group_id);
        $this->assertSame(1, $id->blocks()->count());
    }

    public function test_admin_translate_reopens_existing_translation(): void
    {
        $type = $this->blogType();
        $en = $this->entry($type);
        $type->entries()->create([
            'title' => 'ID', 'slug' => 'hello-world', 'status' => 'draft',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.content-types.entries.translate', [$type, $en]), ['locale' => 'id']);

        // Still exactly one ID sibling — no second copy.
        $this->assertSame(1, $type->entries()->where('locale', 'id')->count());
    }

    public function test_admin_translate_rejects_default_locale(): void
    {
        $type = $this->blogType();
        $en = $this->entry($type);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.content-types.entries.translate', [$type, $en]), ['locale' => 'en'])
            ->assertSessionHasErrors('locale');
    }
}
