<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\View;

class PageController extends Controller
{
    /** Public route — published pages only. */
    public function show(Page $page)
    {
        abort_if(! $page->isPublished(), 404);

        return $this->renderPage($page);
    }

    /** Admin-only preview (route is behind auth+admin) — renders any status. */
    public function preview(Page $page)
    {
        return $this->renderPage($page, preview: true);
    }

    private function renderPage(Page $page, bool $preview = false)
    {
        $page->load([
            'template',
            'blocks' => fn ($q) => $q->visible()->ordered(),
        ]);

        foreach ($page->blocks as $block) {
            if ($block->block_type !== 'products_grid') {
                continue;
            }

            $data          = $block->data ?? [];
            $limit         = max(3, min(12, (int) ($data['limit'] ?? 6)));
            $categoryId    = ($data['category_id'] ?? null) ?: null;
            $destinationId = ($data['destination_id'] ?? null) ?: null;

            $block->resolvedProducts = Product::publiclyVisible()
                ->when($categoryId,    fn ($q) => $q->where('category_id', $categoryId))
                ->when($destinationId, fn ($q) => $q->where('destination_id', $destinationId))
                ->frontendListingReady()
                ->latest()
                ->limit($limit)
                ->get();
        }

        $templateView = 'frontend.templates.' . ($page->template?->blade_file ?: 'default');

        if (! View::exists($templateView)) {
            $templateView = 'frontend.templates.default';
        }

        return view('frontend.pages.show', compact('page', 'templateView', 'preview'));
    }
}
