<?php

namespace Tests\Feature\Admin;

use App\Models\BuilderTemplate;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuilderTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_template_endpoints_require_an_authenticated_admin(): void
    {
        $page = $this->page();
        $template = $this->builderTemplate();

        $this->get(route('admin.builder-templates.index'))->assertRedirect(route('login'));
        $this->postJson(route('admin.builder-templates.store', $page), [])->assertUnauthorized();

        $user = User::factory()->create();
        $this->actingAs($user)->getJson(route('admin.builder-templates.index'))->assertForbidden();
        $this->actingAs($user)->getJson(route('admin.builder-templates.show', $template))->assertForbidden();
        $this->actingAs($user)->deleteJson(route('admin.builder-templates.destroy', $template))->assertForbidden();
    }

    public function test_admin_can_store_a_versioned_sanitized_full_page_template(): void
    {
        $admin = $this->admin();
        $layout = $this->layout('contained');
        $page = $this->page(['template_id' => $layout->id]);

        $response = $this->actingAs($admin)->postJson(route('admin.builder-templates.store', $page), [
            'name' => '<strong>Island Landing</strong>',
            'category' => '<b>Landing Page</b>',
            'description' => '<script>bad()</script>Reusable design',
            'base_template_id' => $layout->id,
            'template_data' => [[
                '_cid' => 88,
                'id' => 99,
                'editing' => true,
                'block_type' => 'group',
                'label' => '<em>Story Group</em>',
                'data' => ['width' => 'contained', 'spacing' => 'md'],
                'children' => [[
                    '_cid' => 89,
                    'block_type' => 'text',
                    'label' => 'Story',
                    'data' => [
                        'heading' => '<b>Our Island</b>',
                        'body_html' => '<p onclick="bad()">Safe <strong>copy</strong></p><script>alert(1)</script>',
                    ],
                    'children' => [],
                ]],
            ]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('template.name', 'Island Landing')
            ->assertJsonPath('template.template_type', 'page')
            ->assertJsonPath('template.schema_version', 1)
            ->assertJsonPath('template.base_template_id', $layout->id);

        $template = BuilderTemplate::query()->firstOrFail();
        $this->assertSame('island-landing', $template->slug);
        $this->assertSame('bad()Reusable design', $template->description);
        $this->assertSame($admin->id, $template->created_by);
        $this->assertArrayNotHasKey('_cid', $template->template_data[0]);
        $this->assertArrayNotHasKey('id', $template->template_data[0]);
        $this->assertArrayNotHasKey('editing', $template->template_data[0]);
        $this->assertSame('Our Island', $template->template_data[0]['children'][0]['data']['heading']);
        $this->assertSame('<p>Safe <strong>copy</strong></p>', $template->template_data[0]['children'][0]['data']['body_html']);
    }

    public function test_invalid_template_tree_and_base_layout_are_rejected(): void
    {
        $admin = $this->admin();
        $page = $this->page();
        $invalidLayout = $this->layout('not-allowlisted');

        $this->actingAs($admin)->postJson(route('admin.builder-templates.store', $page), [
            'name' => 'Invalid columns',
            'base_template_id' => $invalidLayout->id,
            'template_data' => [[
                'block_type' => 'columns',
                'data' => [],
                'children' => [[
                    'block_type' => 'text',
                    'data' => [],
                    'children' => [],
                ]],
            ]],
        ])->assertUnprocessable()->assertJsonValidationErrors('base_template_id');

        $this->actingAs($admin)->postJson(route('admin.builder-templates.store', $page), [
            'name' => 'Invalid tree',
            'template_data' => [[
                'block_type' => 'columns',
                'data' => [],
                'children' => [[
                    'block_type' => 'text',
                    'data' => [],
                    'children' => [],
                ]],
            ]],
        ])->assertUnprocessable()->assertJsonValidationErrors('template_data');

        $this->assertDatabaseCount('builder_templates', 0);
    }

    public function test_library_search_returns_summaries_and_show_returns_the_full_tree(): void
    {
        $admin = $this->admin();
        $this->builderTemplate(['name' => 'Island Landing', 'slug' => 'island-landing']);
        $this->builderTemplate(['name' => 'Corporate Page', 'slug' => 'corporate-page']);

        $this->actingAs($admin)->getJson(route('admin.builder-templates.index', ['search' => 'Island']))
            ->assertOk()
            ->assertJsonCount(1, 'templates')
            ->assertJsonPath('templates.0.name', 'Island Landing')
            ->assertJsonMissingPath('templates.0.template_data')
            ->assertJsonPath('meta.total', 1);

        $template = BuilderTemplate::query()->where('slug', 'island-landing')->firstOrFail();
        $this->actingAs($admin)->getJson(route('admin.builder-templates.show', $template))
            ->assertOk()
            ->assertJsonPath('template.template_data.0.block_type', 'heading');
    }

    public function test_deleting_a_saved_template_does_not_change_pages_or_blocks(): void
    {
        $page = $this->page();
        $block = PageBlock::create([
            'page_id' => $page->id,
            'block_type' => 'heading',
            'label' => 'Existing heading',
            'data' => ['text' => 'Still here', 'level' => 'h2', 'alignment' => 'left'],
            'sort_order' => 0,
            'is_visible' => true,
        ]);
        $template = $this->builderTemplate();

        $this->actingAs($this->admin())->deleteJson(route('admin.builder-templates.destroy', $template))
            ->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseMissing('builder_templates', ['id' => $template->id]);
        $this->assertDatabaseHas('pages', ['id' => $page->id]);
        $this->assertDatabaseHas('page_blocks', ['id' => $block->id, 'page_id' => $page->id]);
    }

    public function test_preview_can_switch_layout_transiently_without_persisting_the_page(): void
    {
        $default = $this->layout('default');
        $contained = $this->layout('contained');
        $page = $this->page(['template_id' => $default->id]);

        $this->actingAs($this->admin())
            ->post(route('admin.pages.preview-payload', $page), [
                'template_id' => $contained->id,
                'blocks' => [],
            ])
            ->assertOk()
            ->assertSee('max-w-3xl', false);

        $this->assertSame($default->id, $page->fresh()->template_id);
    }

    public function test_save_tree_persists_the_selected_layout_and_blocks_together(): void
    {
        $layout = $this->layout('full-width');
        $page = $this->page();

        $this->actingAs($this->admin())->postJson(route('admin.page-blocks.save-tree', $page), [
            'template_id' => $layout->id,
            'blocks' => [[
                'block_type' => 'heading',
                'label' => 'Hero title',
                'data' => ['text' => 'Explore Bintan', 'level' => 'h2', 'alignment' => 'left'],
                'children' => [],
            ]],
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('template_id', $layout->id);

        $this->assertSame($layout->id, $page->fresh()->template_id);
        $this->assertDatabaseHas('page_blocks', ['page_id' => $page->id, 'block_type' => 'heading']);
        $this->assertDatabaseCount('page_revisions', 1);
    }

    public function test_builder_screen_wires_the_lazy_template_library_without_changing_panel_ratios(): void
    {
        $page = $this->page();
        $this->layout('default');

        $this->actingAs($this->admin())->get(route('admin.pages.builder', $page))
            ->assertOk()
            ->assertSee('builderTemplatesUrl:', false)
            ->assertSee('storeBuilderTemplateUrl:', false)
            ->assertSee('Template Library')
            ->assertSee('Save Current Page')
            ->assertSee('lg:w-[20%]', false)
            ->assertDontSee('transform: scale', false)
            ->assertDontSee('ResizeObserver', false);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /** @param array<string, mixed> $overrides */
    private function page(array $overrides = []): Page
    {
        return Page::create($overrides + [
            'title' => 'Builder Page',
            'slug' => 'builder-page-'.uniqid(),
            'status' => 'draft',
        ]);
    }

    private function layout(string $bladeFile): PageTemplate
    {
        return PageTemplate::create([
            'name' => ucfirst(str_replace('-', ' ', $bladeFile)),
            'slug' => $bladeFile.'-'.uniqid(),
            'blade_file' => $bladeFile,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function builderTemplate(array $overrides = []): BuilderTemplate
    {
        return BuilderTemplate::create($overrides + [
            'name' => 'Page Template',
            'slug' => 'page-template-'.uniqid(),
            'template_type' => BuilderTemplate::TYPE_PAGE,
            'schema_version' => BuilderTemplate::SCHEMA_VERSION,
            'template_data' => [[
                'block_type' => 'heading',
                'label' => 'Heading',
                'data' => ['text' => 'Template heading', 'level' => 'h2', 'alignment' => 'left'],
                'is_visible' => true,
                'children' => [],
            ]],
            'is_active' => true,
        ]);
    }
}
