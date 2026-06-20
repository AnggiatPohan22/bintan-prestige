<?php

namespace Tests\Feature\Phase4;

use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageRevision;
use App\Models\User;
use App\Services\PageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PageRevisionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PageService $pageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin       = User::factory()->admin()->create();
        $this->pageService = $this->app->make(PageService::class);
    }

    // =========================================================================
    // K1–K3 — saveRevision() on update
    // =========================================================================

    /** @test */
    public function test_updating_a_page_creates_a_page_revision_row(): void // K1
    {
        $page = Page::factory()->create(['title' => 'Original', 'slug' => 'original', 'status' => 'draft']);

        $this->actingAs($this->admin)
            ->put(route('admin.pages.update', $page), [
                'title'  => 'Updated Title',
                'slug'   => 'updated-title',
                'status' => 'draft',
            ]);

        $this->assertDatabaseHas('page_revisions', ['page_id' => $page->id]);
    }

    /** @test */
    public function test_revision_captures_meta_snapshot_before_update(): void // K2
    {
        $page = Page::factory()->create([
            'title'  => 'Before Update',
            'slug'   => 'before-update',
            'status' => 'draft',
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.pages.update', $page), [
                'title'  => 'After Update',
                'slug'   => 'after-update',
                'status' => 'published',
            ]);

        $revision = PageRevision::where('page_id', $page->id)->first();

        $this->assertEquals('Before Update', $revision->meta_snapshot['title']);
        $this->assertEquals('before-update', $revision->meta_snapshot['slug']);
        $this->assertEquals('draft', $revision->meta_snapshot['status']);
    }

    /** @test */
    public function test_revision_captures_content_snapshot_of_blocks(): void // K3
    {
        $page = Page::factory()->create(['title' => 'With Blocks', 'slug' => 'with-blocks', 'status' => 'draft']);

        PageBlock::factory()->create([
            'page_id'    => $page->id,
            'block_type' => 'hero',
            'label'      => 'Hero Block',
            'data'       => ['heading' => 'Hello'],
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.pages.update', $page), [
                'title'  => 'With Blocks',
                'slug'   => 'with-blocks',
                'status' => 'draft',
            ]);

        $revision = PageRevision::where('page_id', $page->id)->first();

        $this->assertCount(1, $revision->content_snapshot);
        $this->assertEquals('hero', $revision->content_snapshot[0]['block_type']);
        $this->assertEquals('Hero Block', $revision->content_snapshot[0]['label']);
    }

    // =========================================================================
    // K4 — revision_number increments
    // =========================================================================

    /** @test */
    public function test_revision_number_increments_per_page(): void // K4
    {
        $page = Page::factory()->create(['title' => 'Counter', 'slug' => 'counter', 'status' => 'draft']);

        $this->actingAs($this->admin)
            ->put(route('admin.pages.update', $page), ['title' => 'Counter', 'slug' => 'counter', 'status' => 'draft']);

        $this->actingAs($this->admin)
            ->put(route('admin.pages.update', $page), ['title' => 'Counter 2', 'slug' => 'counter-2', 'status' => 'draft']);

        $numbers = PageRevision::where('page_id', $page->id)->pluck('revision_number')->sort()->values();

        $this->assertEquals([1, 2], $numbers->all());
    }

    // =========================================================================
    // K5 — max 20 revisions
    // =========================================================================

    /** @test */
    public function test_max_20_revisions_are_kept_per_page(): void // K5
    {
        $page = Page::factory()->create(['title' => 'Pruned', 'slug' => 'pruned', 'status' => 'draft']);

        // Seed 21 revisions directly.
        for ($i = 1; $i <= 21; $i++) {
            PageRevision::create([
                'page_id'          => $page->id,
                'revision_number'  => $i,
                'content_snapshot' => [],
                'meta_snapshot'    => ['title' => "Rev {$i}"],
                'created_by'       => $this->admin->id,
                'created_at'       => now(),
            ]);
        }

        // Trigger update — saveRevision runs pruning.
        $this->actingAs($this->admin)
            ->put(route('admin.pages.update', $page), [
                'title'  => 'Pruned',
                'slug'   => 'pruned',
                'status' => 'draft',
            ]);

        $this->assertLessThanOrEqual(20, PageRevision::where('page_id', $page->id)->count());
    }

    // =========================================================================
    // K6–K9 — restore
    // =========================================================================

    /** @test */
    public function test_restore_redirects_to_page_edit(): void // K6
    {
        $page     = Page::factory()->create(['title' => 'Original', 'slug' => 'original', 'status' => 'draft']);
        $revision = PageRevision::create([
            'page_id'          => $page->id,
            'revision_number'  => 1,
            'content_snapshot' => [],
            'meta_snapshot'    => ['title' => 'Snapshot Title', 'slug' => 'snapshot-slug', 'status' => 'draft'],
            'created_by'       => $this->admin->id,
            'created_at'       => now(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.pages.revisions.restore', [$page, $revision]))
            ->assertRedirect(route('admin.pages.edit', $page));
    }

    /** @test */
    public function test_restore_updates_page_meta_from_snapshot(): void // K7
    {
        $page = Page::factory()->create(['title' => 'Current Title', 'slug' => 'current', 'status' => 'draft']);

        $revision = PageRevision::create([
            'page_id'          => $page->id,
            'revision_number'  => 1,
            'content_snapshot' => [],
            'meta_snapshot'    => ['title' => 'Old Title', 'slug' => 'old-slug', 'status' => 'published'],
            'created_by'       => $this->admin->id,
            'created_at'       => now(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.pages.revisions.restore', [$page, $revision]));

        $this->assertDatabaseHas('pages', [
            'id'    => $page->id,
            'title' => 'Old Title',
            'slug'  => 'old-slug',
        ]);
    }

    /** @test */
    public function test_restore_recreates_blocks_from_snapshot(): void // K8
    {
        $page = Page::factory()->create(['title' => 'Blocks Page', 'slug' => 'blocks-page', 'status' => 'draft']);

        // Existing block that should be wiped.
        PageBlock::factory()->create(['page_id' => $page->id, 'block_type' => 'text']);

        $revision = PageRevision::create([
            'page_id'         => $page->id,
            'revision_number' => 1,
            'content_snapshot' => [
                ['block_type' => 'hero', 'label' => 'Hero', 'data' => [], 'sort_order' => 0, 'is_visible' => true],
            ],
            'meta_snapshot'   => ['title' => 'Blocks Page', 'slug' => 'blocks-page', 'status' => 'draft'],
            'created_by'      => $this->admin->id,
            'created_at'      => now(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.pages.revisions.restore', [$page, $revision]));

        $blocks = PageBlock::where('page_id', $page->id)->get();

        $this->assertCount(1, $blocks);
        $this->assertEquals('hero', $blocks->first()->block_type);
    }

    /** @test */
    public function test_restore_saves_current_state_as_new_revision_first(): void // K9
    {
        $page = Page::factory()->create(['title' => 'Before Restore', 'slug' => 'before-restore', 'status' => 'draft']);

        $revision = PageRevision::create([
            'page_id'          => $page->id,
            'revision_number'  => 1,
            'content_snapshot' => [],
            'meta_snapshot'    => ['title' => 'Snapshot', 'slug' => 'snapshot', 'status' => 'draft'],
            'created_by'       => $this->admin->id,
            'created_at'       => now(),
        ]);

        $countBefore = PageRevision::where('page_id', $page->id)->count();

        $this->actingAs($this->admin)
            ->post(route('admin.pages.revisions.restore', [$page, $revision]));

        $this->assertGreaterThan($countBefore, PageRevision::where('page_id', $page->id)->count());
    }

    // =========================================================================
    // K10 — edit view shows revisions section
    // =========================================================================

    /** @test */
    public function test_edit_view_shows_revisions_accordion_when_revisions_exist(): void // K10
    {
        $page = Page::factory()->create(['title' => 'Has Revisions', 'slug' => 'has-revisions', 'status' => 'draft']);

        PageRevision::create([
            'page_id'          => $page->id,
            'revision_number'  => 1,
            'content_snapshot' => [],
            'meta_snapshot'    => ['title' => 'Old', 'slug' => 'old', 'status' => 'draft'],
            'created_by'       => $this->admin->id,
            'created_at'       => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.pages.edit', $page))
            ->assertOk()
            ->assertSee('Revisions')
            ->assertSee('Revision #1');
    }

    // =========================================================================
    // K11 — revisions are page-scoped
    // =========================================================================

    /** @test */
    public function test_revisions_are_scoped_to_their_page(): void // K11
    {
        $pageA = Page::factory()->create(['title' => 'Page A', 'slug' => 'page-a', 'status' => 'draft']);
        $pageB = Page::factory()->create(['title' => 'Page B', 'slug' => 'page-b', 'status' => 'draft']);

        PageRevision::create([
            'page_id'          => $pageA->id,
            'revision_number'  => 1,
            'content_snapshot' => [],
            'meta_snapshot'    => ['title' => 'Page A', 'slug' => 'page-a', 'status' => 'draft'],
            'created_by'       => $this->admin->id,
            'created_at'       => now(),
        ]);

        $this->assertEquals(0, PageRevision::where('page_id', $pageB->id)->count());
        $this->assertEquals(1, PageRevision::where('page_id', $pageA->id)->count());
    }

    // =========================================================================
    // K12 — cascading delete
    // =========================================================================

    /** @test */
    public function test_deleting_a_page_removes_its_revisions(): void // K12
    {
        $page = Page::factory()->create(['title' => 'To Delete', 'slug' => 'to-delete', 'status' => 'draft']);

        PageRevision::create([
            'page_id'          => $page->id,
            'revision_number'  => 1,
            'content_snapshot' => [],
            'meta_snapshot'    => [],
            'created_by'       => $this->admin->id,
            'created_at'       => now(),
        ]);

        $page->delete();

        $this->assertDatabaseMissing('page_revisions', ['page_id' => $page->id]);
    }
}
