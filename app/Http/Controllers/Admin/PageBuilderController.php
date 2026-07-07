<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ContentType;
use App\Models\Destination;
use App\Models\FormDefinition;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageTemplate;
use App\Services\BuilderTreeSanitizer;
use App\Services\PageBlockService;
use App\Services\PageService;
use App\Support\PageTemplateRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PageBuilderController extends Controller
{
    public function __construct(
        private readonly PageBlockService $blockService,
        private readonly PageService $pageService,
        private readonly BuilderTreeSanitizer $treeSanitizer,
    ) {}

    public function show(Page $page): mixed
    {
        $flatBlocks = $page->blocks()->ordered()->get();
        $tree = $this->buildBuilderTree($flatBlocks);
        $registry = config('blocks');
        $layoutTemplates = PageTemplate::query()
            ->active()
            ->whereIn('blade_file', PageTemplateRegistry::keys())
            ->ordered()
            ->get(['id', 'name', 'blade_file', 'description'])
            ->map(fn (PageTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'blade_file' => $template->blade_file,
                'description' => $template->description,
            ])->all();

        // Dynamic <select> option sources for schema fields that use `optionsFrom`
        // (products_grid → category/destination, contact_form → form definitions).
        $fieldOptions = [
            'categories' => Category::orderBy('name')->get(['id', 'name'])
                ->map(fn (Category $c): array => ['value' => (string) $c->id, 'label' => $c->name])->all(),
            'destinations' => Destination::orderBy('name')->get(['id', 'name'])
                ->map(fn (Destination $d): array => ['value' => (string) $d->id, 'label' => $d->name])->all(),
            'forms' => FormDefinition::orderBy('name')->get(['id', 'name'])
                ->map(fn (FormDefinition $f): array => ['value' => (string) $f->id, 'label' => $f->name])->all(),
            'content_types' => ContentType::query()->public()->orderBy('label_plural')->get(['id', 'label_plural'])
                ->map(fn (ContentType $t): array => ['value' => (string) $t->id, 'label' => $t->label_plural])->all(),
            // No "current entry" on a page, so the content_field picker is empty
            // here — use the block's Entry ID field to target a specific entry.
            'entry_fields' => [],
        ];

        return view('backend.builder.index', compact('page', 'tree', 'registry', 'fieldOptions', 'layoutTemplates'));
    }

    public function saveTree(Request $request, Page $page): JsonResponse
    {
        $validated = $request->validate([
            'blocks' => ['nullable', 'array', 'max:200'],
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('page_templates', 'id')->where(
                    fn ($query) => $query
                        ->where('is_active', true)
                        ->whereIn('blade_file', PageTemplateRegistry::keys())
                ),
            ],
        ]);

        $rawNodes = $validated['blocks'] ?? [];
        $nodes = $this->treeSanitizer->sanitizeTree(is_array($rawNodes) ? $rawNodes : [], 'blocks');
        $updatesTemplate = $request->exists('template_id');
        $templateId = $validated['template_id'] ?? null;

        DB::transaction(function () use ($page, $nodes, $updatesTemplate, $templateId): void {
            $this->pageService->saveRevision($page);
            if ($updatesTemplate) {
                $page->update(['template_id' => $templateId]);
            }
            $page->blocks()->delete();
            $this->insertNodes($page, $nodes);
        });

        $fresh = $this->buildBuilderTree($page->blocks()->ordered()->get());

        return response()->json([
            'success' => true,
            'tree' => $fresh,
            'template_id' => $page->fresh()->template_id,
        ]);
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
