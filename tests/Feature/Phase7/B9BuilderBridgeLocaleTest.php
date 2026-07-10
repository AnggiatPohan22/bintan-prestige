<?php

namespace Tests\Feature\Phase7;

use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\Page;
use App\Models\User;
use App\Support\ContentFieldResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B9 — Builder bridge locale-aware. content_query filter was landed at B5; this
 * verifies content_field resolves the SIBLING entry in the current locale (via
 * translation group) with graceful fallback, and that admin previews render the
 * builder in the target document's own locale (translated chrome + catalog).
 */
class B9BuilderBridgeLocaleTest extends TestCase
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

    private function textField(ContentType $type, string $key = 'summary'): Field
    {
        $group = FieldGroup::create([
            'content_type_id' => $type->id,
            'key'   => 'general',
            'label' => 'General',
            'sort_order' => 0,
        ]);

        return Field::create([
            'field_group_id' => $group->id,
            'key' => $key,
            'label' => 'Summary',
            'type' => 'text',
            'sort_order' => 0,
            'is_required' => false,
            'is_filterable' => false,
        ]);
    }

    // ------------------------------------------------------------- content_field locale-aware

    public function test_content_field_resolves_sibling_in_current_locale(): void
    {
        $type = $this->blogType();
        $this->textField($type);

        $en = $type->entries()->create([
            'title' => 'EN Post', 'slug' => 'hello', 'status' => 'published',
            'data'  => ['summary' => 'English summary'],
        ]);
        $id = $type->entries()->create([
            'title' => 'ID Post', 'slug' => 'hello', 'status' => 'published',
            'data'  => ['summary' => 'Ringkasan Indonesia'],
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        $resolver = new ContentFieldResolver();

        app()->setLocale('en');
        $out = $resolver->resolve(['field_key' => 'summary', 'entry_id' => $en->id]);
        $this->assertSame('English summary', $out['text']);

        // Even when the block references the EN entry_id, the ID visitor gets the
        // ID sibling's field value (via translation_group_id).
        app()->setLocale('id');
        $out = $resolver->resolve(['field_key' => 'summary', 'entry_id' => $en->id]);
        $this->assertSame('Ringkasan Indonesia', $out['text']);
    }

    public function test_content_field_falls_back_to_referenced_row_when_sibling_absent(): void
    {
        $type = $this->blogType();
        $this->textField($type);
        $en = $type->entries()->create([
            'title' => 'EN Post', 'slug' => 'hello', 'status' => 'published',
            'data'  => ['summary' => 'English summary'],
        ]);

        app()->setLocale('id');
        $out = (new ContentFieldResolver())->resolve(['field_key' => 'summary', 'entry_id' => $en->id]);

        // No ID sibling → serve the default-locale content so the block doesn't
        // silently disappear (A0 fallback: attributes fall back to base).
        $this->assertSame('English summary', $out['text']);
    }

    public function test_content_field_hides_when_sibling_is_draft_and_source_unpublished(): void
    {
        $type = $this->blogType();
        $this->textField($type);
        $en = $type->entries()->create([
            'title' => 'EN Post', 'slug' => 'hello', 'status' => 'draft',
            'data'  => ['summary' => 'English summary'],
        ]);
        $type->entries()->create([
            'title' => 'ID Post', 'slug' => 'hello', 'status' => 'draft',
            'data'  => ['summary' => 'Ringkasan Indonesia'],
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);

        app()->setLocale('id');
        $this->assertNull((new ContentFieldResolver())->resolve(['field_key' => 'summary', 'entry_id' => $en->id]));
    }

    public function test_content_field_uses_current_entry_when_no_entry_id_given(): void
    {
        $type = $this->blogType();
        $this->textField($type);
        $current = $type->entries()->create([
            'title' => 'Current', 'slug' => 'current', 'status' => 'published',
            'data'  => ['summary' => 'Current entry summary'],
            'locale' => 'id',
        ]);

        app()->setLocale('id');
        $out = (new ContentFieldResolver())->resolve(['field_key' => 'summary'], $current);
        $this->assertSame('Current entry summary', $out['text']);
    }

    // ------------------------------------------------------------- preview locale sync

    public function test_page_preview_renders_in_pages_own_locale(): void
    {
        $en = Page::create(['title' => 'About Us', 'slug' => 'about', 'status' => 'published']);
        Page::create([
            'title' => 'Tentang Kami', 'slug' => 'about', 'status' => 'draft',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);
        $idPage = Page::query()->where('locale', 'id')->first();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pages.preview', $idPage))
            ->assertOk()
            ->assertSee('Tentang Kami');
    }

    public function test_entry_preview_payload_sets_locale_to_entry_locale(): void
    {
        $type = $this->blogType();
        $en = $type->entries()->create([
            'title' => 'EN', 'slug' => 'hello', 'status' => 'published',
        ]);
        $type->entries()->create([
            'title' => 'ID', 'slug' => 'hello', 'status' => 'draft',
            'locale' => 'id', 'translation_group_id' => $en->translation_group_id,
        ]);
        $idEntry = $type->entries()->where('locale', 'id')->first();

        app()->setLocale('en');
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.content-types.entries.builder.preview-payload', [$type, $idEntry]), [
                'blocks' => [],
            ])
            ->assertOk();

        $this->assertSame('id', app()->getLocale());
    }
}
