<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageBlock;
use App\Services\PageBlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageBlockController extends Controller
{
    /** @return list<string> */
    public static function blockTypes(): array
    {
        return array_keys(config('blocks'));
    }

    public function apiTypes(): JsonResponse
    {
        return response()->json(config('blocks'));
    }

    public function __construct(
        protected PageBlockService $blockService,
    ) {}

    public function store(Request $request, Page $page)
    {
        $request->validate([
            'block_type' => ['required', 'in:'.implode(',', self::blockTypes())],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        $page->blocks()->create([
            'block_type' => $request->block_type,
            'label' => $request->label ?: ucfirst(str_replace('_', ' ', $request->block_type)),
            'data' => $this->blockService->defaultDataFor($request->block_type),
            'sort_order' => $this->blockService->nextSortOrder($page),
            'is_visible' => true,
        ]);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('success', 'Block added. Edit its content below.')
            ->with('open_section', 'blocks');
    }

    public function update(Request $request, Page $page, PageBlock $block)
    {
        $this->ensureBlockBelongsToPage($page, $block);

        $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'data' => ['nullable', 'array'],
            'parent_block_id' => ['nullable', 'integer'],
        ]);

        $data = $this->blockService->validateAndSanitizeData(
            $block->block_type,
            $request->input('data', [])
        );
        $parentId = $this->blockService->validateParent(
            $page,
            $block,
            $request->integer('parent_block_id') ?: null,
        );

        $block->update([
            'label' => $request->label ?? $block->label,
            'data' => $data,
            'parent_block_id' => $parentId,
            'sort_order' => $block->parent_block_id !== $parentId
                ? $this->blockService->nextSortOrder($page, $parentId)
                : $block->sort_order,
        ]);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('success', 'Block updated.')
            ->with('open_section', 'blocks');
    }

    public function destroy(Page $page, PageBlock $block)
    {
        $this->ensureBlockBelongsToPage($page, $block);

        $block->delete();

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('success', 'Block deleted.')
            ->with('open_section', 'blocks');
    }

    public function reorder(Request $request, Page $page)
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $this->blockService->reorder($page, $request->ids);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('success', 'Block order saved.')
            ->with('open_section', 'blocks');
    }

    public function toggleVisible(Page $page, PageBlock $block)
    {
        $this->ensureBlockBelongsToPage($page, $block);

        $block->update(['is_visible' => ! $block->is_visible]);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('success', $block->is_visible ? 'Block is now visible.' : 'Block is now hidden.')
            ->with('open_section', 'blocks');
    }

    private function ensureBlockBelongsToPage(Page $page, PageBlock $block): void
    {
        abort_unless($block->page_id === $page->id, 404);
    }
}
