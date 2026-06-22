<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Destination;
use App\Models\FormDefinition;
use App\Models\Page;
use App\Models\PageBlock;
use App\Services\PageBlockService;
use App\Services\PageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PageBuilderController extends Controller
{
    public function __construct(
        private readonly PageBlockService $blockService,
        private readonly PageService $pageService,
    ) {}

    public function show(Page $page): mixed
    {
        $flatBlocks = $page->blocks()->ordered()->get();
        $tree = $this->buildBuilderTree($flatBlocks);
        $registry = config('blocks');

        // Dynamic <select> option sources for schema fields that use `optionsFrom`
        // (products_grid → category/destination, contact_form → form definitions).
        $fieldOptions = [
            'categories' => Category::orderBy('name')->get(['id', 'name'])
                ->map(fn (Category $c): array => ['value' => (string) $c->id, 'label' => $c->name])->all(),
            'destinations' => Destination::orderBy('name')->get(['id', 'name'])
                ->map(fn (Destination $d): array => ['value' => (string) $d->id, 'label' => $d->name])->all(),
            'forms' => FormDefinition::orderBy('name')->get(['id', 'name'])
                ->map(fn (FormDefinition $f): array => ['value' => (string) $f->id, 'label' => $f->name])->all(),
        ];

        return view('backend.builder.index', compact('page', 'tree', 'registry', 'fieldOptions'));
    }

    public function saveTree(Request $request, Page $page): JsonResponse
    {
        $request->validate(['blocks' => ['nullable', 'array', 'max:200']]);

        $nodes = $request->input('blocks', []);
        $nodeCount = 0;
        $this->validateNodes($nodes, $nodeCount);

        DB::transaction(function () use ($page, $nodes): void {
            $this->pageService->saveRevision($page);
            $page->blocks()->delete();
            $this->insertNodes($page, $nodes);
        });

        $fresh = $this->buildBuilderTree($page->blocks()->ordered()->get());

        return response()->json(['success' => true, 'tree' => $fresh]);
    }

    /**
     * @param  Collection<int, PageBlock>  $blocks
     * @return array<int, mixed>
     */
    private function buildBuilderTree(Collection $blocks): array
    {
        $byParent = $blocks->groupBy(
            fn (PageBlock $b): string => $b->parent_block_id === null
                ? 'root'
                : (string) $b->parent_block_id
        );

        $attach = function (?int $parentId) use (&$attach, $byParent): array {
            $key = $parentId === null ? 'root' : (string) $parentId;

            return ($byParent->get($key) ?? collect())
                ->map(fn (PageBlock $b): array => [
                    'id' => $b->id,
                    'type' => $b->block_type,
                    'label' => $b->label,
                    'data' => $b->data ?? [],
                    'is_visible' => $b->is_visible,
                    'sort_order' => $b->sort_order,
                    'children' => $attach($b->id),
                ])
                ->values()
                ->all();
        };

        return $attach(null);
    }

    /**
     * @param  array<int, mixed>  $nodes
     */
    private function validateNodes(array $nodes, int &$nodeCount, int $depth = 0, ?string $parentType = null): void
    {
        if ($depth > 5) {
            throw ValidationException::withMessages(['blocks' => 'Block nesting is limited to five levels.']);
        }

        $validTypes = implode(',', PageBlockController::blockTypes());

        foreach ($nodes as $node) {
            if (! is_array($node) || ++$nodeCount > 200) {
                throw ValidationException::withMessages(['blocks' => 'Block tree must contain at most 200 valid nodes.']);
            }

            Validator::make($node, [
                'block_type' => ['required', 'string', 'in:'.$validTypes],
                'label' => ['nullable', 'string', 'max:255'],
                'data' => ['nullable', 'array'],
                'is_visible' => ['nullable', 'boolean'],
                'children' => ['nullable', 'array'],
            ])->validate();

            if ($parentType === 'columns' && $node['block_type'] !== 'group') {
                throw ValidationException::withMessages(['blocks' => 'Columns blocks may only contain Group blocks.']);
            }

            if (! empty($node['children']) && ! in_array($node['block_type'], ['group', 'columns'], true)) {
                throw ValidationException::withMessages(['blocks' => 'Only Group and Columns blocks may contain children.']);
            }

            $this->validateNodes($node['children'] ?? [], $nodeCount, $depth + 1, $node['block_type']);
        }
    }

    /**
     * @param  array<int, mixed>  $nodes
     */
    private function insertNodes(Page $page, array $nodes, ?int $parentId = null): void
    {
        foreach ($nodes as $sortOrder => $node) {
            $data = $this->blockService->validateAndSanitizeData($node['block_type'], $node['data'] ?? []);

            $block = $page->blocks()->create([
                'block_type' => $node['block_type'],
                'label' => $node['label'] ?? ucfirst(str_replace('_', ' ', $node['block_type'])),
                'data' => $data,
                'sort_order' => $sortOrder,
                'is_visible' => $node['is_visible'] ?? true,
                'parent_block_id' => $parentId,
            ]);

            if (! empty($node['children'])) {
                $this->insertNodes($page, $node['children'], $block->id);
            }
        }
    }
}
