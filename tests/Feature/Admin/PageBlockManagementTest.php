<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\PageBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageBlockManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_block_routes_require_an_authenticated_admin(): void
    {
        $page = $this->page();

        $this->post(route('admin.page-blocks.store', $page), [
            'block_type' => 'text',
        ])->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->post(route('admin.page-blocks.store', $page), ['block_type' => 'text'])
            ->assertForbidden();
    }

    public function test_admin_can_add_supported_blocks_with_defaults_and_next_sort_order(): void
    {
        $page = $this->page();
        PageBlock::create([
            'page_id' => $page->id,
            'block_type' => 'divider',
            'label' => 'Existing divider',
            'data' => ['style' => 'line'],
            'sort_order' => 4,
        ]);

        $this->actingAs($this->admin())->post(route('admin.page-blocks.store', $page), [
            'block_type' => 'hero',
            'label' => 'Opening Hero',
        ])->assertRedirect(route('admin.pages.edit', $page));

        $block = PageBlock::query()->where('label', 'Opening Hero')->firstOrFail();

        $this->assertSame($page->id, $block->page_id);
        $this->assertSame('hero', $block->block_type);
        $this->assertSame(5, $block->sort_order);
        $this->assertTrue($block->is_visible);
        $this->assertSame('', $block->data['title']);
        $this->assertSame('cover', $block->data['background']['size']);
    }

    public function test_block_store_rejects_an_unknown_block_type(): void
    {
        $page = $this->page();

        $this->actingAs($this->admin())
            ->from(route('admin.pages.edit', $page))
            ->post(route('admin.page-blocks.store', $page), [
                'block_type' => 'unknown_block',
            ])->assertRedirect(route('admin.pages.edit', $page))
            ->assertSessionHasErrors('block_type');

        $this->assertDatabaseCount('page_blocks', 0);
    }

    public function test_text_block_update_preserves_allowed_markup_and_removes_disallowed_tags(): void
    {
        $page = $this->page();
        $block = $this->block($page, 'text');

        $this->actingAs($this->admin())->put(route('admin.page-blocks.update', [$page, $block]), [
            'label' => 'Story block',
            'data' => [
                'heading' => 'Our Story',
                'body_html' => '<p onclick="alert(1)">Hello <strong>World</strong><script>alert(1)</script> '
                    .'<a href="javascript:alert(2)" style="color:red">Unsafe</a> '
                    .'<a href="https://example.com" target="_blank" onclick="alert(3)">Safe</a></p>',
                'unexpected' => 'must not be stored',
            ],
        ])->assertRedirect(route('admin.pages.edit', $page));

        $block->refresh();

        $this->assertSame('Story block', $block->label);
        $this->assertSame('Our Story', $block->data['heading']);
        $this->assertSame(
            '<p>Hello <strong>World</strong>alert(1) <a>Unsafe</a> '
                .'<a href="https://example.com" target="_blank" rel="noopener noreferrer">Safe</a></p>',
            $block->data['body_html']
        );
        $this->assertArrayNotHasKey('unexpected', $block->data);
    }

    public function test_admin_can_reorder_toggle_and_delete_blocks_for_a_page(): void
    {
        $page = $this->page();
        $first = $this->block($page, 'text', 0);
        $second = $this->block($page, 'cta', 1);
        $otherPage = $this->page('Other Page', 'other-page');
        $foreign = $this->block($otherPage, 'divider', 8);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.page-blocks.reorder', $page), [
            'ids' => [$second->id, $first->id],
        ])->assertRedirect(route('admin.pages.edit', $page));

        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);
        $this->assertSame(8, $foreign->fresh()->sort_order);

        $this->actingAs($admin)
            ->from(route('admin.pages.edit', $page))
            ->post(route('admin.page-blocks.reorder', $page), [
                'ids' => [$first->id, $second->id, $foreign->id],
                '_editor_context' => 'blocks',
            ])->assertRedirect(route('admin.pages.edit', $page))
            ->assertSessionHasErrors('ids');

        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);
        $this->assertSame(8, $foreign->fresh()->sort_order);

        $this->actingAs($admin)->post(route('admin.page-blocks.toggle-visible', [$page, $first]))
            ->assertRedirect(route('admin.pages.edit', $page));
        $this->assertFalse($first->fresh()->is_visible);

        $this->actingAs($admin)->delete(route('admin.page-blocks.destroy', [$page, $second]))
            ->assertRedirect(route('admin.pages.edit', $page));
        $this->assertDatabaseMissing('page_blocks', ['id' => $second->id]);
    }

    public function test_reorder_requires_the_complete_unique_block_set_and_preserves_order_on_failure(): void
    {
        $page = $this->page();
        $first = $this->block($page, 'text', 0);
        $second = $this->block($page, 'cta', 1);
        $admin = $this->admin();

        foreach ([[$second->id], [$second->id, $second->id]] as $ids) {
            $this->actingAs($admin)
                ->from(route('admin.pages.edit', $page))
                ->post(route('admin.page-blocks.reorder', $page), [
                    'ids' => $ids,
                    '_editor_context' => 'blocks',
                ])->assertRedirect(route('admin.pages.edit', $page))
                ->assertSessionHasErrors('ids');

            $this->assertSame(0, $first->fresh()->sort_order);
            $this->assertSame(1, $second->fresh()->sort_order);
        }
    }

    public function test_block_editor_exposes_phase_two_controls_media_preview_and_accessible_save_delete_states(): void
    {
        $page = $this->page();
        $this->block($page, 'text', 0);
        $block = $this->block($page, 'image', 1);
        $block->update([
            'label' => 'About image',
            'data' => ['src' => 'media/about.webp', 'alt' => 'About Bintan'],
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.pages.edit', $page))
            ->assertOk()
            ->assertSee('src="http://localhost/storage/media/about.webp"', false)
            ->assertSee('aria-label="Move About image up"', false)
            ->assertSee('aria-controls="block-editor-panel-'.$block->id.'"', false)
            ->assertSee('Saving&hellip;', false)
            ->assertSee('This permanently removes its content and cannot be undone.');
    }

    public function test_block_validation_reopens_the_failed_editor_with_feedback(): void
    {
        $page = $this->page();
        $block = $this->block($page, 'map');

        $this->actingAs($this->admin())
            ->from(route('admin.pages.edit', $page))
            ->followingRedirects()
            ->put(route('admin.page-blocks.update', [$page, $block]), [
                '_editor_context' => 'blocks',
                '_block_id' => $block->id,
                'label' => 'Map block',
                'data' => ['embed_url' => 'javascript:alert(1)', 'zoom' => 30],
            ])->assertOk()
            ->assertSee('This block could not be saved.')
            ->assertSee('Your changes were not saved.')
            ->assertSee('x-data="{ open: true, submitting: false }"', false);
    }

    public function test_empty_block_editor_guides_the_admin_to_the_first_block_control(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.pages.edit', $this->page()))
            ->assertOk()
            ->assertSee('Build this page one block at a time.')
            ->assertSee('href="#block_type"', false)
            ->assertDontSee('x-sort', false);
    }

    public function test_nested_block_update_must_not_mutate_a_block_owned_by_another_page(): void
    {
        $routePage = $this->page('Route Page', 'route-page');
        $ownerPage = $this->page('Owner Page', 'owner-page');
        $foreignBlock = $this->block($ownerPage, 'text');

        $this->actingAs($this->admin())
            ->put(route('admin.page-blocks.update', [$routePage, $foreignBlock]), [
                'label' => 'Cross-page mutation',
                'data' => ['heading' => 'Must not be saved'],
            ])->assertNotFound();

        $foreignBlock->refresh();
        $this->assertNotSame('Cross-page mutation', $foreignBlock->label);
        $this->assertNotSame('Must not be saved', $foreignBlock->data['heading'] ?? null);
    }

    public function test_nested_block_delete_and_toggle_must_not_mutate_a_block_owned_by_another_page(): void
    {
        $routePage = $this->page('Route Page', 'route-page');
        $ownerPage = $this->page('Owner Page', 'owner-page');
        $foreignBlock = $this->block($ownerPage, 'text');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.page-blocks.toggle-visible', [$routePage, $foreignBlock]))
            ->assertNotFound();
        $this->assertTrue($foreignBlock->fresh()->is_visible);

        $this->actingAs($admin)
            ->delete(route('admin.page-blocks.destroy', [$routePage, $foreignBlock]))
            ->assertNotFound();
        $this->assertDatabaseHas('page_blocks', ['id' => $foreignBlock->id]);
    }

    public function test_block_schema_rejects_unsafe_urls_paths_ranges_relations_and_enums(): void
    {
        $page = $this->page();
        $admin = $this->admin();
        $cases = [
            ['hero', ['cta_url' => 'javascript:alert(1)'], 'cta_url'],
            ['image', ['src' => '../../.env'], 'src'],
            ['gallery', ['columns' => 9], 'columns'],
            ['cta', ['style' => 'neon'], 'style'],
            ['products_grid', ['category_id' => 999999, 'limit' => 99], 'category_id'],
            ['faq', ['faq_ids' => [999999]], 'faq_ids.0'],
            ['testimonials', ['items' => [['rating' => 6]]], 'items.0.rating'],
            ['map', ['embed_url' => 'javascript:alert(1)', 'zoom' => 30], 'embed_url'],
            ['divider', ['style' => 'script'], 'style'],
        ];

        foreach ($cases as [$type, $data, $errorKey]) {
            $block = $this->block($page, $type);

            $this->actingAs($admin)
                ->from(route('admin.pages.edit', $page))
                ->put(route('admin.page-blocks.update', [$page, $block]), ['data' => $data])
                ->assertRedirect(route('admin.pages.edit', $page))
                ->assertSessionHasErrors($errorKey);
        }
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function page(string $title = 'Builder Page', string $slug = 'builder-page'): Page
    {
        return Page::create([
            'title' => $title,
            'slug' => $slug,
            'status' => 'draft',
        ]);
    }

    private function block(Page $page, string $type, int $sortOrder = 0): PageBlock
    {
        return PageBlock::create([
            'page_id' => $page->id,
            'block_type' => $type,
            'label' => ucfirst($type).' block',
            'data' => $type === 'text' ? ['heading' => 'Original heading'] : [],
            'sort_order' => $sortOrder,
            'is_visible' => true,
        ]);
    }
}
