<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Page;
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

        return view('backend.pages.edit', [
            'page' => $page,
            'blockTypes' => PageBlockController::BLOCK_TYPES,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'destinations' => Destination::orderBy('name')->get(['id', 'name']),
            'templates' => PageTemplate::active()->whereIn('blade_file', PageTemplateRegistry::keys())->ordered()->get(['id', 'name']),
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
