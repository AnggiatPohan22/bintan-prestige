<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Page;
use App\Models\FormDefinition;
use App\Models\PageBlock;
use App\Models\PageRevision;
use App\Models\PageTemplate;
use App\Services\PageService;
use App\Support\PageTemplateRegistry;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function __construct(
        protected PageService $pageService,
    ) {}

    public function index(Request $request)
    {
        $pages = Page::query()
            ->when(
                $request->search,
                fn ($q) => $q->where('title', 'like', '%'.$request->search.'%')
            )
            ->when(
                $request->status,
                fn ($q) => $q->where('status', $request->status)
            )
            ->ordered()
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('backend.pages.index', [
            'pages' => $pages,
        ]);
    }

    public function create()
    {
        return view('backend.pages.create', [
            'templates' => PageTemplate::active()->whereIn('blade_file', PageTemplateRegistry::keys())->ordered()->get(['id', 'name']),
        ]);
    }

    public function store(StorePageRequest $request)
    {
        $this->pageService->store($request);

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page created successfully.');
    }

    public function edit(Page $page)
    {
        $page->load(['blocks' => fn ($q) => $q->ordered()]);

        $revisions = $page->revisions()->with('author:id,name')->get();

        return view('backend.pages.edit', [
            'page'            => $page,
            'revisions'       => $revisions,
            'blockTypes'      => PageBlockController::BLOCK_TYPES,
            'categories'      => Category::orderBy('name')->get(['id', 'name']),
            'destinations'    => Destination::orderBy('name')->get(['id', 'name']),
            'templates'       => PageTemplate::active()->whereIn('blade_file', PageTemplateRegistry::keys())->ordered()->get(['id', 'name']),
            'formDefinitions' => FormDefinition::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdatePageRequest $request, Page $page)
    {
        $this->pageService->update($request, $page);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('success', 'Page updated successfully.');
    }

    public function destroy(Page $page)
    {
        $this->pageService->deleteOgImage($page);
        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page deleted successfully.');
    }

    public function duplicate(Page $page)
    {
        $copy = $this->pageService->duplicate($page);

        return redirect()
            ->route('admin.pages.edit', $copy)
            ->with('success', "Page duplicated. You are now editing \"{$copy->title}\".");
    }

    public function restoreRevision(Page $page, PageRevision $revision)
    {
        // Guard: revision must belong to this page.
        if ($revision->page_id !== $page->id) {
            abort(404);
        }

        // Snapshot current state before overwriting (so the restore itself is undoable).
        $this->pageService->saveRevision($page);

        // Restore meta fields.
        $meta = $revision->meta_snapshot ?? [];
        $page->update(array_filter([
            'title'            => $meta['title'] ?? null,
            'slug'             => $meta['slug'] ?? null,
            'status'           => $meta['status'] ?? null,
            'template_id'      => $meta['template_id'] ?? null,
            'meta_title'       => $meta['meta_title'] ?? null,
            'meta_description' => $meta['meta_description'] ?? null,
        ], fn ($v) => $v !== null));

        // Restore blocks: wipe current, recreate from snapshot.
        $page->blocks()->delete();

        foreach ($revision->content_snapshot as $blockData) {
            PageBlock::create([
                'page_id'    => $page->id,
                'block_type' => $blockData['block_type'],
                'label'      => $blockData['label'] ?? null,
                'data'       => $blockData['data'] ?? null,
                'sort_order' => $blockData['sort_order'] ?? 0,
                'is_visible' => $blockData['is_visible'] ?? true,
            ]);
        }

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('success', "Page restored to revision #{$revision->revision_number}.");
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:pages,id'],
        ]);

        $this->pageService->reorder($request->ids);

        return response()->json(['success' => true]);
    }
}
