<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageBlock;
use App\Services\PageBlockService;
use Illuminate\Http\Request;

class PageBlockController extends Controller
{
    public const BLOCK_TYPES = [
        'hero', 'text', 'image', 'gallery', 'cta',
        'products_grid', 'faq', 'testimonials', 'map', 'divider',
    ];

    public function __construct(
        protected PageBlockService $blockService,
    ) {}

    public function store(Request $request, Page $page)
    {
        $request->validate([
            'block_type' => ['required', 'in:' . implode(',', self::BLOCK_TYPES)],
            'label'      => ['nullable', 'string', 'max:255'],
        ]);

        $page->blocks()->create([
            'block_type' => $request->block_type,
            'label'      => $request->label ?: ucfirst(str_replace('_', ' ', $request->block_type)),
            'data'       => $this->blockService->defaultDataFor($request->block_type),
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
        $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'data'  => ['nullable', 'array'],
        ]);

        $data = $this->blockService->sanitizeData(
            $block->block_type,
            $request->input('data', [])
        );

        $block->update([
            'label' => $request->label ?? $block->label,
            'data'  => $data,
        ]);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('success', 'Block updated.')
            ->with('open_section', 'blocks');
    }

    public function destroy(Page $page, PageBlock $block)
    {
        $block->delete();

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('success', 'Block deleted.')
            ->with('open_section', 'blocks');
    }

    public function reorder(Request $request, Page $page)
    {
        $request->validate([
            'ids'   => ['required', 'array'],
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
        $block->update(['is_visible' => ! $block->is_visible]);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('success', $block->is_visible ? 'Block is now visible.' : 'Block is now hidden.')
            ->with('open_section', 'blocks');
    }
}
