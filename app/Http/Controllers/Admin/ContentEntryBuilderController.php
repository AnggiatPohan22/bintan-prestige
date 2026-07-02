<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\Destination;
use App\Models\FormDefinition;
use App\Models\PageBlock;
use App\Services\BuilderTreeSanitizer;
use App\Services\PageBlockService;
use App\Support\ContentEntryRevisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Opens the Phase 5 visual builder on a ContentEntry, storing the block tree on
 * the polymorphic page_blocks rail (blockable morph, page_id NULL). Mirrors
 * PageBuilderController but on the morph rail — the Phase 5 page builder stays
 * untouched (dual-rail, per A3).
 */
class ContentEntryBuilderController extends Controller
{
    public function __construct(
        private readonly PageBlockService $blockService,
        private readonly BuilderTreeSanitizer $treeSanitizer,
        private readonly ContentEntryRevisionService $revisions,
    ) {}

    public function show(ContentType $contentType, ContentEntry $entry): mixed
    {
        $this->guard($contentType, $entry);

        $tree     = $this->buildBuilderTree($entry->blocks()->ordered()->get());
        $registry = config('blocks');

        // Dynamic <select> option sources for schema fields that use optionsFrom.
        $fieldOptions = [
            'categories' => Category::orderBy('name')->get(['id', 'name'])
                ->map(fn (Category $c): array => ['value' => (string) $c->id, 'label' => $c->name])->all(),
            'destinations' => Destination::orderBy('name')->get(['id', 'name'])
                ->map(fn (Destination $d): array => ['value' => (string) $d->id, 'label' => $d->name])->all(),
            'forms' => FormDefinition::orderBy('name')->get(['id', 'name'])
                ->map(fn (FormDefinition $f): array => ['value' => (string) $f->id, 'label' => $f->name])->all(),
        ];

        return view('backend.builder.entry', compact('contentType', 'entry', 'tree', 'registry', 'fieldOptions'));
    }

    public function saveTree(Request $request, ContentType $contentType, ContentEntry $entry): JsonResponse
    {
        $this->guard($contentType, $entry);

        $validated = $request->validate([
            'blocks'      => ['nullable', 'array', 'max:200'],
            // Entries use the string `template` column, not a PageTemplate id.
            // The shared builder JS still posts template_id; accept + ignore it.
            'template_id' => ['nullable'],
        ]);

        $rawNodes = $validated['blocks'] ?? [];
        $nodes    = $this->treeSanitizer->sanitizeTree(is_array($rawNodes) ? $rawNodes : [], 'blocks');

        DB::transaction(function () use ($entry, $nodes): void {
            $this->revisions->snapshot($entry);
            $entry->blocks()->delete();
            $this->insertNodes($entry, $nodes);
        });

        $fresh = $this->buildBuilderTree($entry->blocks()->ordered()->get());

        return response()->json([
            'success'     => true,
            'tree'        => $fresh,
            'template_id' => null,
        ]);
    }

    public function previewPayload(Request $request, ContentType $contentType, ContentEntry $entry): mixed
    {
        $this->guard($contentType, $entry);

        $validated = $request->validate([
            'blocks'      => ['nullable', 'array', 'max:200'],
            'template_id' => ['nullable'],
        ]);

        $rawNodes = $validated['blocks'] ?? [];
        $nodes    = $this->treeSanitizer->sanitizeTree(is_array($rawNodes) ? $rawNodes : [], 'blocks');
        $blocks   = $this->transientTree($nodes);

        return view('frontend.content-entries.preview', compact('entry', 'blocks'));
    }

    // ---------------------------------------------------------------- helpers

    private function guard(ContentType $contentType, ContentEntry $entry): void
    {
        if ($entry->content_type_id !== $contentType->id) {
            abort(404);
        }

        if (! $contentType->supports('editor')) {
            abort(404);
        }
    }

    /**
     * Flat blocks → nested builder tree array (for the Alpine component).
     *
     * @param  Collection<int, PageBlock>  $blocks
     * @return array<int, mixed>
     */
    private function buildBuilderTree(Collection $blocks): array
    {
        $byParent = $blocks->groupBy(
            fn (PageBlock $b): string => $b->parent_block_id === null ? 'root' : (string) $b->parent_block_id
        );

        $attach = function (?int $parentId) use (&$attach, $byParent): array {
            $key = $parentId === null ? 'root' : (string) $parentId;

            return ($byParent->get($key) ?? collect())
                ->map(fn (PageBlock $b): array => [
                    'id'         => $b->id,
                    'type'       => $b->block_type,
                    'label'      => $b->label,
                    'data'       => $b->data ?? [],
                    'is_visible' => $b->is_visible,
                    'sort_order' => $b->sort_order,
                    'children'   => $attach($b->id),
                ])
                ->values()
                ->all();
        };

        return $attach(null);
    }

    /**
     * Persist a sanitized nested-node tree onto the entry's morph rail.
     *
     * @param  array<int, mixed>  $nodes
     */
    private function insertNodes(ContentEntry $entry, array $nodes, ?int $parentId = null): void
    {
        foreach ($nodes as $sortOrder => $node) {
            $data = $this->blockService->validateAndSanitizeData($node['block_type'], $node['data'] ?? []);

            $block = $entry->blocks()->create([
                'block_type'      => $node['block_type'],
                'label'           => $node['label'] ?? ucfirst(str_replace('_', ' ', $node['block_type'])),
                'data'            => $data,
                'sort_order'      => $sortOrder,
                'is_visible'      => $node['is_visible'] ?? true,
                'parent_block_id' => $parentId,
            ]);

            if (! empty($node['children'])) {
                $this->insertNodes($entry, $node['children'], $block->id);
            }
        }
    }

    /**
     * Build in-memory (non-persisted) PageBlock models for live preview.
     *
     * @param  array<int, mixed>  $nodes
     * @return Collection<int, PageBlock>
     */
    private function transientTree(array $nodes, int &$counter = 0): Collection
    {
        return collect($nodes)
            ->map(function (mixed $node) use (&$counter): PageBlock {
                /** @var array<string, mixed> $node */
                $block = new PageBlock([
                    'block_type' => $node['block_type'],
                    'label'      => $node['label'] ?? null,
                    'data'       => $node['data'] ?? [],
                    'sort_order' => $node['sort_order'] ?? 0,
                    'is_visible' => $node['is_visible'] ?? true,
                ]);
                $block->id = --$counter; // negative synthetic id, never collides
                $block->setRelation('children', $this->transientTree($node['children'] ?? [], $counter));

                return $block;
            })
            ->filter(fn (PageBlock $b): bool => $b->is_visible)
            ->values();
    }
}
