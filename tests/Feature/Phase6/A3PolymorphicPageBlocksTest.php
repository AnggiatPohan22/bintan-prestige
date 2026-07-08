<?php

namespace Tests\Feature\Phase6;

use App\Models\Page;
use App\Models\PageBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 6 — A3: polymorphic page_blocks (dual-rail, Strategy 1).
 *
 * Pages keep page_id/hasMany (Phase 5 builder untouched); the new blockable_*
 * morph lets non-Page owners share the table, and page_id is nullable for them.
 */
class A3PolymorphicPageBlocksTest extends TestCase
{
    use RefreshDatabase;

    public function test_blockable_morph_columns_exist(): void
    {
        $this->assertTrue(
            Schema::hasColumns('page_blocks', ['blockable_type', 'blockable_id']),
            'page_blocks must expose the blockable morph columns.'
        );
    }

    public function test_page_id_is_now_nullable_for_non_page_owners(): void
    {
        $page = $this->page();

        // A block with a NULL page_id (a future ContentEntry-owned block) must be
        // storable now that page_id is relaxed to nullable.
        $block = PageBlock::create([
            'page_id' => null,
            'blockable_type' => Page::class,
            'blockable_id' => $page->id,
            'block_type' => 'text',
            'label' => 'Morph-owned block',
            'data' => [],
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $this->assertNull($block->fresh()->page_id);
        $this->assertDatabaseHas('page_blocks', [
            'id' => $block->id,
            'page_id' => null,
            'blockable_type' => Page::class,
            'blockable_id' => $page->id,
        ]);
    }

    public function test_blockable_relation_resolves_the_owner(): void
    {
        $page = $this->page();
        $block = PageBlock::create([
            'page_id' => $page->id,
            'blockable_type' => Page::class,
            'blockable_id' => $page->id,
            'block_type' => 'text',
            'data' => [],
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $owner = $block->blockable;

        $this->assertInstanceOf(Page::class, $owner);
        $this->assertTrue($owner->is($page));
    }

    public function test_existing_page_hasmany_rail_is_unchanged(): void
    {
        // The Phase 5 builder path (page_id / hasMany) keeps working exactly as
        // before — dual-rail leaves it untouched.
        $page = $this->page();
        $page->blocks()->create([
            'block_type' => 'heading',
            'label' => 'Heading',
            'data' => ['text' => 'Still works', 'level' => 'h2'],
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $this->assertCount(1, $page->fresh()->blocks);
        $this->assertSame('heading', $page->fresh()->blocks->first()->block_type);
    }

    private function page(): Page
    {
        return Page::create([
            'title' => 'Morph Page',
            'slug' => 'morph-page-'.Page::query()->count(),
            'status' => 'published',
        ]);
    }
}
