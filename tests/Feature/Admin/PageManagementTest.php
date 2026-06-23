<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_admin_routes_require_an_authenticated_admin(): void
    {
        $this->get(route('admin.pages.index'))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.pages.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pages.index'))
            ->assertOk();
    }

    public function test_admin_can_create_a_page_with_generated_slug_and_template(): void
    {
        $template = $this->template();

        $response = $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'About Bintan Prestige',
            'slug' => '',
            'status' => 'draft',
            'template_id' => $template->id,
            'meta_title' => 'About our team',
            'meta_description' => 'Meet the Bintan Prestige travel team.',
            'sort_order' => 4,
        ]);

        $response->assertRedirect(route('admin.pages.index'));

        $this->assertDatabaseHas('pages', [
            'title' => 'About Bintan Prestige',
            'slug' => 'about-bintan-prestige',
            'status' => 'draft',
            'template_id' => $template->id,
            'sort_order' => 4,
        ]);
    }

    public function test_page_validation_rejects_duplicate_and_reserved_slugs(): void
    {
        Page::create([
            'title' => 'Existing Page',
            'slug' => 'existing-page',
            'status' => 'draft',
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)->from(route('admin.pages.create'))->post(route('admin.pages.store'), [
            'title' => 'Duplicate Page',
            'slug' => 'existing-page',
            'status' => 'draft',
        ])->assertRedirect(route('admin.pages.create'))
            ->assertSessionHasErrors('slug');

        $this->actingAs($admin)->from(route('admin.pages.create'))->post(route('admin.pages.store'), [
            'title' => 'Reserved Page',
            'slug' => 'products',
            'status' => 'published',
        ])->assertRedirect(route('admin.pages.create'))
            ->assertSessionHasErrors('slug');
    }

    public function test_page_admin_clearly_distinguishes_draft_preview_from_live_pages(): void
    {
        $draft = Page::create([
            'title' => 'Draft Workflow Page',
            'slug' => 'draft-workflow-page',
            'status' => 'draft',
        ]);
        Page::create([
            'title' => 'Published Workflow Page',
            'slug' => 'published-workflow-page',
            'status' => 'published',
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.pages.index'))
            ->assertOk()
            ->assertSee('Preview Draft')
            ->assertSee('View Live')
            ->assertSee('Admin preview only; hidden from public pages and menus.')
            ->assertSee('Live publicly and eligible for managed menus.');

        $this->actingAs($admin)
            ->get(route('admin.pages.edit', $draft))
            ->assertOk()
            ->assertSee('Preview Draft')
            ->assertSee('Draft:')
            ->assertSee('Published:');
    }

    public function test_admin_can_update_reorder_and_delete_pages(): void
    {
        $first = Page::create([
            'title' => 'First Page',
            'slug' => 'first-page',
            'status' => 'draft',
            'sort_order' => 0,
        ]);
        $second = Page::create([
            'title' => 'Second Page',
            'slug' => 'second-page',
            'status' => 'draft',
            'sort_order' => 1,
        ]);
        PageBlock::create([
            'page_id' => $first->id,
            'block_type' => 'text',
            'label' => 'Page child block',
            'data' => ['heading' => 'Child'],
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.pages.update', $first), [
            'title' => 'Updated First Page',
            'slug' => 'updated-first-page',
            'status' => 'published',
            'sort_order' => 3,
        ])->assertRedirect(route('admin.pages.edit', $first));

        $this->assertDatabaseHas('pages', [
            'id' => $first->id,
            'title' => 'Updated First Page',
            'slug' => 'updated-first-page',
            'status' => 'published',
        ]);

        $this->actingAs($admin)->postJson(route('admin.pages.reorder'), [
            'ids' => [$second->id, $first->id],
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);

        $this->actingAs($admin)->delete(route('admin.pages.destroy', $first))
            ->assertRedirect(route('admin.pages.index'));

        $this->assertDatabaseMissing('pages', ['id' => $first->id]);
        $this->assertDatabaseMissing('page_blocks', ['page_id' => $first->id]);
    }

    public function test_page_duplicate_preserves_nested_block_mapping_without_reusing_source_ids(): void
    {
        $page = Page::create(['title' => 'Nested Source', 'slug' => 'nested-source', 'status' => 'draft']);
        $group = PageBlock::create([
            'page_id' => $page->id, 'block_type' => 'group', 'label' => 'Source Group',
            'data' => ['width' => 'contained'], 'sort_order' => 0, 'is_visible' => true,
        ]);
        PageBlock::create([
            'page_id' => $page->id, 'parent_block_id' => $group->id, 'block_type' => 'heading',
            'label' => 'Nested Heading', 'data' => ['text' => 'Hello'], 'sort_order' => 0, 'is_visible' => true,
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.pages.duplicate', $page));
        $copy = Page::where('id', '!=', $page->id)->firstOrFail();
        $response->assertRedirect(route('admin.pages.edit', $copy));

        $copyGroup = $copy->blocks()->where('block_type', 'group')->firstOrFail();
        $copyHeading = $copy->blocks()->where('block_type', 'heading')->firstOrFail();
        $this->assertNotSame($group->id, $copyGroup->id);
        $this->assertSame($copyGroup->id, $copyHeading->parent_block_id);
    }

    public function test_create_page_only_lists_active_templates_in_display_order(): void
    {
        PageTemplate::create([
            'name' => 'Later Active Template',
            'slug' => 'later-active',
            'blade_file' => 'default',
            'is_active' => true,
            'sort_order' => 20,
        ]);
        PageTemplate::create([
            'name' => 'First Active Template',
            'slug' => 'first-active',
            'blade_file' => 'contained',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        PageTemplate::create([
            'name' => 'Inactive Template',
            'slug' => 'inactive',
            'blade_file' => 'default',
            'is_active' => false,
            'sort_order' => 0,
        ]);
        PageTemplate::create([
            'name' => 'Unsupported Active Template',
            'slug' => 'unsupported-active',
            'blade_file' => 'unexpected-template',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.pages.create'))
            ->assertOk()
            ->assertSeeInOrder(['First Active Template', 'Later Active Template'])
            ->assertDontSee('Inactive Template')
            ->assertDontSee('Unsupported Active Template');
    }

    public function test_page_rejects_inactive_or_unsupported_templates(): void
    {
        $inactive = PageTemplate::create([
            'name' => 'Inactive Template',
            'slug' => 'inactive-validation',
            'blade_file' => 'default',
            'is_active' => false,
            'sort_order' => 0,
        ]);
        $unsupported = PageTemplate::create([
            'name' => 'Unsupported Template',
            'slug' => 'unsupported-validation',
            'blade_file' => 'unexpected-template',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        foreach ([$inactive, $unsupported] as $template) {
            $this->actingAs($this->admin())
                ->from(route('admin.pages.create'))
                ->post(route('admin.pages.store'), [
                    'title' => 'Rejected Template Page '.$template->id,
                    'slug' => 'rejected-template-page-'.$template->id,
                    'status' => 'draft',
                    'template_id' => $template->id,
                ])->assertRedirect(route('admin.pages.create'))
                ->assertSessionHasErrors('template_id');
        }

        $this->assertDatabaseCount('pages', 0);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function template(): PageTemplate
    {
        return PageTemplate::create([
            'name' => 'Standard',
            'slug' => 'standard-test',
            'blade_file' => 'default',
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }
}
