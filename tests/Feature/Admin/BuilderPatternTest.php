<?php

namespace Tests\Feature\Admin;

use App\Models\BuilderPattern;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuilderPatternTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_pattern_endpoints_require_an_authenticated_admin(): void
    {
        $pattern = $this->pattern();

        $this->get(route('admin.builder-patterns.index'))->assertRedirect(route('login'));
        $this->postJson(route('admin.builder-patterns.store'), [])->assertUnauthorized();

        $user = User::factory()->create();
        $this->actingAs($user)->getJson(route('admin.builder-patterns.index'))->assertForbidden();
        $this->actingAs($user)->getJson(route('admin.builder-patterns.show', $pattern))->assertForbidden();
        $this->actingAs($user)->deleteJson(route('admin.builder-patterns.destroy', $pattern))->assertForbidden();
    }

    public function test_admin_can_store_a_sanitized_block_subtree_as_a_pattern(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->postJson(route('admin.builder-patterns.store'), [
            'name' => '<strong>Story Section</strong>',
            'category' => '<b>Landing</b>',
            'description' => '<script>bad()</script>Reusable story layout',
            'pattern_data' => [
                '_cid' => 91,
                'id' => 123,
                'editing' => true,
                'block_type' => 'group',
                'label' => '<em>Story Group</em>',
                'data' => ['width' => 'contained', 'spacing' => 'md'],
                'children' => [[
                    '_cid' => 92,
                    'block_type' => 'text',
                    'label' => 'Story copy',
                    'data' => [
                        'heading' => '<b>Our Story</b>',
                        'body_html' => '<p onclick="bad()">Safe <strong>content</strong></p><script>alert(1)</script>',
                    ],
                    'children' => [],
                ]],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pattern.name', 'Story Section')
            ->assertJsonPath('pattern.category', 'Landing')
            ->assertJsonPath('pattern.block_type', 'group');

        $pattern = BuilderPattern::query()->firstOrFail();
        $this->assertSame('story-section', $pattern->slug);
        $this->assertSame('bad()Reusable story layout', $pattern->description);
        $this->assertSame($admin->id, $pattern->created_by);
        $this->assertFalse($pattern->is_global);
        $this->assertArrayNotHasKey('_cid', $pattern->pattern_data);
        $this->assertArrayNotHasKey('id', $pattern->pattern_data);
        $this->assertArrayNotHasKey('editing', $pattern->pattern_data);
        $this->assertSame('Our Story', $pattern->pattern_data['children'][0]['data']['heading']);
        $this->assertSame(
            '<p>Safe <strong>content</strong></p>',
            $pattern->pattern_data['children'][0]['data']['body_html']
        );
    }

    public function test_invalid_pattern_data_is_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('admin.builder-patterns.store'), [
            'name' => 'Unknown block',
            'pattern_data' => ['block_type' => 'not_registered', 'data' => [], 'children' => []],
        ])->assertUnprocessable()->assertJsonValidationErrors('block_type');

        $this->actingAs($admin)->postJson(route('admin.builder-patterns.store'), [
            'name' => 'Invalid columns',
            'pattern_data' => [
                'block_type' => 'columns',
                'data' => [],
                'children' => [[
                    'block_type' => 'text',
                    'data' => [],
                    'children' => [],
                ]],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors('pattern_data');

        $this->assertDatabaseCount('builder_patterns', 0);
    }

    public function test_pattern_library_lists_and_shows_saved_patterns(): void
    {
        $admin = $this->admin();
        $older = $this->pattern(['name' => 'Older', 'slug' => 'older']);
        $newer = $this->pattern(['name' => 'Newer', 'slug' => 'newer']);

        $this->actingAs($admin)->getJson(route('admin.builder-patterns.index'))
            ->assertOk()
            ->assertJsonCount(2, 'patterns')
            ->assertJsonFragment(['name' => 'Older', 'slug' => 'older'])
            ->assertJsonFragment(['name' => 'Newer', 'slug' => 'newer']);

        $this->actingAs($admin)->getJson(route('admin.builder-patterns.show', $older))
            ->assertOk()
            ->assertJsonPath('pattern.slug', 'older')
            ->assertJsonPath('pattern.pattern_data.block_type', 'heading');

        $this->assertNotSame($older->id, $newer->id);
    }

    public function test_admin_can_delete_a_pattern_without_affecting_existing_page_blocks(): void
    {
        $admin = $this->admin();
        $pattern = $this->pattern();
        $page = Page::create(['title' => 'Pattern Page', 'slug' => 'pattern-page', 'status' => 'draft']);
        $block = PageBlock::create([
            'page_id' => $page->id,
            'block_type' => 'heading',
            'label' => 'Inserted heading',
            'data' => ['text' => 'Still here', 'level' => 'h2', 'alignment' => 'left'],
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $this->actingAs($admin)->deleteJson(route('admin.builder-patterns.destroy', $pattern))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('builder_patterns', ['id' => $pattern->id]);
        $this->assertDatabaseHas('page_blocks', ['id' => $block->id, 'page_id' => $page->id]);
    }

    public function test_builder_screen_wires_patterns_without_changing_the_layout_contract(): void
    {
        $page = Page::create(['title' => 'Builder', 'slug' => 'builder', 'status' => 'draft']);

        $this->actingAs($this->admin())->get(route('admin.pages.builder', $page))
            ->assertOk()
            ->assertSee('patternsUrl:', false)
            ->assertSee('builder-patterns', false)
            ->assertSee('Patterns')
            ->assertSee('Save as Pattern')
            ->assertSee('lg:w-[20%]', false)
            ->assertSee("tablet: '768px', mobile: '375px'", false)
            ->assertDontSee('transform: scale', false)
            ->assertDontSee('ResizeObserver', false);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /** @param array<string, mixed> $overrides */
    private function pattern(array $overrides = []): BuilderPattern
    {
        return BuilderPattern::create($overrides + [
            'name' => 'Heading Pattern',
            'slug' => 'heading-pattern',
            'pattern_data' => [
                'block_type' => 'heading',
                'label' => 'Heading',
                'data' => ['text' => 'Pattern title', 'level' => 'h2', 'alignment' => 'left'],
                'is_visible' => true,
                'children' => [],
            ],
            'block_type' => 'heading',
            'is_global' => false,
        ]);
    }
}
