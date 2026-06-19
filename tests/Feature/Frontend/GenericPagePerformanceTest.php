<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Faq;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GenericPagePerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_many_blocks_keep_relation_queries_batched_per_unique_configuration(): void
    {
        $category = Category::factory()->create([
            'slug' => 'performance-category',
            'is_active' => true,
        ]);
        $destination = Destination::factory()->create([
            'slug' => 'performance-destination',
            'is_active' => true,
        ]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'destination_id' => $destination->id,
            'status' => 'published',
        ]);
        $faq = Faq::create([
            'question' => 'Is query preparation batched?',
            'answer' => 'Yes, repeated relation-backed blocks reuse prepared data.',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $page = Page::create([
            'title' => 'Large CMS Page',
            'slug' => 'large-cms-page',
            'status' => 'published',
        ]);

        for ($index = 0; $index < 30; $index++) {
            $this->block($page, 'text', [
                'heading' => 'Section '.$index,
                'body_html' => '<p>Prepared content '.$index.'.</p>',
            ], $index);
        }

        for ($index = 0; $index < 6; $index++) {
            $this->block($page, 'products_grid', [
                'category_id' => $category->id,
                'destination_id' => $destination->id,
                'limit' => 6,
                'show_price' => true,
            ], 30 + $index);
        }

        for ($index = 0; $index < 3; $index++) {
            $this->block($page, 'faq', [
                'source' => 'ids',
                'faq_ids' => [$faq->id],
            ], 36 + $index);
        }

        $queryCounts = [
            'page_blocks' => 0,
            'products' => 0,
            'faqs' => 0,
        ];

        DB::listen(function ($query) use (&$queryCounts): void {
            $sql = strtolower($query->sql);

            foreach (array_keys($queryCounts) as $table) {
                if (str_contains($sql, 'from "'.$table.'"')) {
                    $queryCounts[$table]++;
                }
            }
        });

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('Section 29')
            ->assertSee($product->name)
            ->assertSee('Is query preparation batched?');

        $this->assertSame(1, $queryCounts['page_blocks']);
        $this->assertSame(1, $queryCounts['products']);
        $this->assertSame(1, $queryCounts['faqs']);
    }

    private function block(Page $page, string $type, array $data, int $sortOrder): PageBlock
    {
        return PageBlock::create([
            'page_id' => $page->id,
            'block_type' => $type,
            'label' => ucfirst(str_replace('_', ' ', $type)).' '.$sortOrder,
            'data' => $data,
            'sort_order' => $sortOrder,
            'is_visible' => true,
        ]);
    }
}
