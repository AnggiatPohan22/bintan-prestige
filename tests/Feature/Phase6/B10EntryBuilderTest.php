<?php

namespace Tests\Feature\Phase6;

use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\PageBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B10 — Entry body via the visual builder (polymorphic page_blocks morph rail).
 */
class B10EntryBuilderTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- morph relation

    public function test_entry_blocks_are_stored_on_the_morph_rail(): void
    {
        $type  = $this->type(['supports' => ['title', 'editor']]);
        $entry = $this->entry($type);

        $entry->blocks()->create([
            'block_type' => 'text',
            'label'      => 'Intro',
            'data'       => ['html' => '<p>Hi</p>'],
            'sort_order' => 0,
        ]);

        $this->assertDatabaseHas('page_blocks', [
            'blockable_type' => ContentEntry::class,
            'blockable_id'   => $entry->id,
            'block_type'     => 'text',
            'page_id'        => null,
        ]);
        $this->assertSame(1, $entry->blocks()->count());
    }

    // ---------------------------------------------------------------- access guard

    public function test_builder_opens_for_editor_supporting_type(): void
    {
        $type  = $this->type(['supports' => ['title', 'editor']]);
        $entry = $this->entry($type);

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.entries.builder', [$type, $entry]))
            ->assertOk();
    }

    public function test_builder_is_not_found_for_non_editor_type(): void
    {
        $type  = $this->type(['supports' => ['title']]); // no editor
        $entry = $this->entry($type);

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.entries.builder', [$type, $entry]))
            ->assertNotFound();
    }

    public function test_builder_is_not_found_for_cross_type_entry(): void
    {
        $typeA = $this->type(['slug' => 'a', 'supports' => ['title', 'editor']]);
        $typeB = $this->type(['slug' => 'b', 'supports' => ['title', 'editor']]);
        $entryB = $this->entry($typeB);

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.entries.builder', [$typeA, $entryB]))
            ->assertNotFound();
    }

    // ---------------------------------------------------------------- save tree

    public function test_save_tree_persists_blocks_on_morph_rail(): void
    {
        $type  = $this->type(['supports' => ['title', 'editor']]);
        $entry = $this->entry($type);

        $this->actingAs($this->admin())
            ->postJson(route('admin.content-types.entries.builder.save-tree', [$type, $entry]), [
                'blocks' => [
                    ['block_type' => 'heading', 'label' => 'H', 'data' => ['text' => 'Hello'], 'sort_order' => 0, 'is_visible' => true, 'children' => []],
                    ['block_type' => 'text', 'label' => 'T', 'data' => ['html' => '<p>Body</p>'], 'sort_order' => 1, 'is_visible' => true, 'children' => []],
                ],
                'template_id' => null,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(2, $entry->blocks()->count());
        $this->assertDatabaseHas('page_blocks', [
            'blockable_type' => ContentEntry::class,
            'blockable_id'   => $entry->id,
            'block_type'     => 'heading',
            'page_id'        => null,
        ]);
    }

    public function test_save_tree_replaces_existing_blocks(): void
    {
        $type  = $this->type(['supports' => ['title', 'editor']]);
        $entry = $this->entry($type);
        $entry->blocks()->create(['block_type' => 'text', 'data' => ['html' => 'old'], 'sort_order' => 0]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.content-types.entries.builder.save-tree', [$type, $entry]), [
                'blocks' => [
                    ['block_type' => 'heading', 'data' => ['text' => 'new'], 'sort_order' => 0, 'is_visible' => true, 'children' => []],
                ],
            ])
            ->assertOk();

        $this->assertSame(1, $entry->blocks()->count());
        $this->assertSame('heading', $entry->blocks()->first()->block_type);
    }

    public function test_save_tree_persists_nested_children(): void
    {
        $type  = $this->type(['supports' => ['title', 'editor']]);
        $entry = $this->entry($type);

        $this->actingAs($this->admin())
            ->postJson(route('admin.content-types.entries.builder.save-tree', [$type, $entry]), [
                'blocks' => [
                    [
                        'block_type' => 'group',
                        'data'       => [],
                        'sort_order' => 0,
                        'is_visible' => true,
                        'children'   => [
                            ['block_type' => 'text', 'data' => ['html' => '<p>nested</p>'], 'sort_order' => 0, 'is_visible' => true, 'children' => []],
                        ],
                    ],
                ],
            ])
            ->assertOk();

        $group = $entry->blocks()->where('block_type', 'group')->first();
        $this->assertNotNull($group);
        $child = $entry->blocks()->where('block_type', 'text')->first();
        $this->assertNotNull($child);
        $this->assertSame($group->id, $child->parent_block_id);
    }

    public function test_save_tree_records_a_revision(): void
    {
        $type  = $this->type(['supports' => ['title', 'editor']]);
        $entry = $this->entry($type);

        $this->actingAs($this->admin())
            ->postJson(route('admin.content-types.entries.builder.save-tree', [$type, $entry]), [
                'blocks' => [
                    ['block_type' => 'text', 'data' => ['html' => 'x'], 'sort_order' => 0, 'is_visible' => true, 'children' => []],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('content_entry_revisions', ['content_entry_id' => $entry->id]);
    }

    // ---------------------------------------------------------------- cascade cleanup

    public function test_force_deleting_entry_removes_its_blocks(): void
    {
        $type  = $this->type(['supports' => ['title', 'editor']]);
        $entry = $this->entry($type);
        $entry->blocks()->create(['block_type' => 'text', 'data' => [], 'sort_order' => 0]);
        $entryId = $entry->id;

        $this->assertDatabaseHas('page_blocks', ['blockable_id' => $entryId, 'blockable_type' => ContentEntry::class]);

        $entry->forceDelete();

        $this->assertDatabaseMissing('page_blocks', ['blockable_id' => $entryId, 'blockable_type' => ContentEntry::class]);
    }

    public function test_soft_deleting_entry_keeps_its_blocks(): void
    {
        $type  = $this->type(['supports' => ['title', 'editor']]);
        $entry = $this->entry($type);
        $entry->blocks()->create(['block_type' => 'text', 'data' => [], 'sort_order' => 0]);

        $entry->delete(); // soft

        $this->assertDatabaseHas('page_blocks', ['blockable_id' => $entry->id, 'blockable_type' => ContentEntry::class]);
    }

    // ---------------------------------------------------------------- preview

    public function test_preview_payload_renders_blocks(): void
    {
        $type  = $this->type(['supports' => ['title', 'editor']]);
        $entry = $this->entry($type, ['title' => 'Preview Me']);

        $this->actingAs($this->admin())
            ->post(route('admin.content-types.entries.builder.preview-payload', [$type, $entry]), [
                'blocks' => [
                    ['block_type' => 'heading', 'data' => ['text' => 'Rendered Heading'], 'sort_order' => 0, 'is_visible' => true, 'children' => []],
                ],
            ])
            ->assertOk();
    }

    public function test_preview_payload_empty_shows_placeholder(): void
    {
        $type  = $this->type(['supports' => ['title', 'editor']]);
        $entry = $this->entry($type, ['title' => 'Empty Entry']);

        $this->actingAs($this->admin())
            ->post(route('admin.content-types.entries.builder.preview-payload', [$type, $entry]), ['blocks' => []])
            ->assertOk()
            ->assertSee('Empty Entry');
    }

    // ---------------------------------------------------------------- publish from builder

    public function test_can_publish_entry_from_the_builder(): void
    {
        $type  = $this->type(['supports' => ['title', 'editor']]);
        $entry = $this->entry($type, ['status' => 'draft', 'published_at' => null]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.content-types.entries.builder.status', [$type, $entry]), [
                'status' => 'published',
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'status' => 'published', 'is_published' => true]);

        $entry->refresh();
        $this->assertSame('published', $entry->status);
        $this->assertNotNull($entry->published_at); // set on publish so it goes live
    }

    public function test_builder_status_rejects_invalid_value(): void
    {
        $type  = $this->type(['supports' => ['title', 'editor']]);
        $entry = $this->entry($type);

        $this->actingAs($this->admin())
            ->postJson(route('admin.content-types.entries.builder.status', [$type, $entry]), [
                'status' => 'bogus',
            ])
            ->assertStatus(422);
    }

    public function test_builder_status_is_guarded_for_non_editor_type(): void
    {
        $type  = $this->type(['supports' => ['title']]); // no editor
        $entry = $this->entry($type);

        $this->actingAs($this->admin())
            ->postJson(route('admin.content-types.entries.builder.status', [$type, $entry]), [
                'status' => 'published',
            ])
            ->assertNotFound();
    }

    // ---------------------------------------------------------------- edit page button

    public function test_edit_page_shows_builder_button_for_editor_type(): void
    {
        $type  = $this->type(['supports' => ['title', 'editor']]);
        $entry = $this->entry($type);

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.entries.edit', [$type, $entry]))
            ->assertOk()
            ->assertSee('Edit Body in Builder');
    }

    public function test_edit_page_hides_builder_button_for_non_editor_type(): void
    {
        $type  = $this->type(['supports' => ['title']]);
        $entry = $this->entry($type);

        $this->actingAs($this->admin())
            ->get(route('admin.content-types.entries.edit', [$type, $entry]))
            ->assertOk()
            ->assertDontSee('Edit Body in Builder');
    }

    // ---------------------------------------------------------------- helpers

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function type(array $attrs = []): ContentType
    {
        return ContentType::create(array_merge([
            'slug'           => 'article',
            'label_singular' => 'Article',
            'label_plural'   => 'Articles',
            'supports'       => ['title', 'editor'],
        ], $attrs));
    }

    private function entry(ContentType $type, array $attrs = []): ContentEntry
    {
        return $type->entries()->create(array_merge([
            'title'  => 'Entry ' . uniqid(),
            'status' => 'draft',
        ], $attrs));
    }
}
