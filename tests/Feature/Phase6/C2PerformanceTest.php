<?php

namespace Tests\Feature\Phase6;

use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\Field;
use App\Models\FieldGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * C2 — Performance audit: the public entry routes must not issue O(N) queries as
 * the number of entries or content_field blocks grows.
 */
class C2PerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_archive_does_not_n_plus_one_on_content_type(): void
    {
        $type = $this->type();

        // 15 published entries — publicUrl() per card must not query per entry.
        for ($i = 0; $i < 15; $i++) {
            $this->entry($type, ['title' => "Post {$i}", 'slug' => "post-{$i}"]);
        }

        $count = $this->countTableQueries('content_types', fn () => $this->get(url('/blog'))->assertOk());

        // Type resolution + one batched eager load — bounded, not ~15.
        $this->assertLessThanOrEqual(3, $count, "Archive issued {$count} content_types queries (N+1).");
    }

    public function test_content_field_blocks_share_one_fields_query(): void
    {
        [$type, $group] = $this->typeWithGroup(['route_base' => 'hotel', 'supports' => ['title', 'slug', 'editor']]);
        $this->field($group, 'tagline', 'text', 'Tagline');
        $this->field($group, 'region', 'text', 'Region');

        $entry = $this->entry($type, [
            'title' => 'Bintan', 'slug' => 'bintan', 'route_base' => 'hotel',
            'data' => ['tagline' => 'Escape', 'region' => 'North'],
        ]);

        // Body with 6 content_field blocks (all this entry's type).
        for ($i = 0; $i < 6; $i++) {
            $entry->blocks()->create([
                'block_type' => 'content_field',
                'data'       => ['field_key' => $i % 2 === 0 ? 'tagline' : 'region', 'show_label' => true],
                'sort_order' => $i,
                'is_visible' => true,
            ]);
        }

        $count = $this->countTableQueries('fields', fn () => $this->get(url('/hotel/bintan'))->assertOk()->assertSee('Escape'));

        // The field definitions are cached per content type → a single query.
        $this->assertSame(1, $count, "content_field issued {$count} fields queries (expected 1, cached).");
    }

    // ---------------------------------------------------------------- helpers

    private function countTableQueries(string $table, callable $action): int
    {
        $count = 0;
        DB::listen(function ($query) use (&$count, $table): void {
            if (str_contains(strtolower($query->sql), 'from "'.$table.'"')) {
                $count++;
            }
        });

        $action();

        return $count;
    }

    private function type(array $attrs = []): ContentType
    {
        return ContentType::create(array_merge([
            'slug'           => 'blog-' . uniqid(),
            'label_singular' => 'Blog Post',
            'label_plural'   => 'Blog Posts',
            'is_public'      => true,
            'is_active'      => true,
            'has_archive'    => true,
            'route_base'     => 'blog',
            'supports'       => ['title', 'slug'],
        ], $attrs));
    }

    /**
     * @return array{0: ContentType, 1: FieldGroup}
     */
    private function typeWithGroup(array $attrs = []): array
    {
        $type  = $this->type($attrs);
        $group = FieldGroup::create(['content_type_id' => $type->id, 'label' => 'Details', 'key' => 'details']);

        return [$type, $group];
    }

    private function field(FieldGroup $group, string $key, string $type, string $label): Field
    {
        return Field::create(['field_group_id' => $group->id, 'type' => $type, 'key' => $key, 'label' => $label]);
    }

    private function entry(ContentType $type, array $attrs = []): ContentEntry
    {
        return $type->entries()->create(array_merge([
            'title'        => 'Entry ' . uniqid(),
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ], $attrs));
    }
}
