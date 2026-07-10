<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Category;
use App\Models\Destination;
use App\Models\FormDefinition;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageRevision;
use App\Models\PageTemplate;
use App\Services\PageService;
use Illuminate\Support\Facades\DB;
use App\Support\Locales;
use App\Support\PageTemplateRegistry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function __construct(
        protected PageService $pageService,
    ) {}

    public function index(Request $request)
    {
        $localeFilter = $request->query('locale');
        $activeCodes  = Locales::activeCodes();

        if ($localeFilter !== null && ! in_array($localeFilter, $activeCodes, true)) {
            $localeFilter = null;
        }

        $pages = Page::query()
            ->when(
                $request->search,
                fn ($q) => $q->where('title', 'like', '%'.$request->search.'%')
            )
            ->when(
                $request->status,
                fn ($q) => $q->where('status', $request->status)
            )
            ->when($localeFilter, fn ($q) => $q->where('locale', $localeFilter))
            // Phase 7 (B8) — one lookup for every sibling's status; per-locale
            // badges rendered from this collection without N+1.
            ->with(['translationSiblings' => fn ($q) => $q->select('id', 'translation_group_id', 'locale', 'status')])
            ->ordered()
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('backend.pages.index', [
            'pages'        => $pages,
            'localeFilter' => $localeFilter,
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
            'blockTypes'      => PageBlockController::blockTypes(),
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

    /** Phase 7 (B4) — create/open a translation of this page in another locale. */
    public function translate(Request $request, Page $page)
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(Locales::nonDefaultActive())],
        ]);

        $translation = $this->pageService->translateTo($page, $validated['locale']);

        return redirect()
            ->route('admin.pages.edit', $translation)
            ->with('success', 'Translation ('.strtoupper($validated['locale']).') ready as a draft — translate the copy and publish it.');
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

        $restoredBlocks = [];
        $snapshotIdToIndex = collect($revision->content_snapshot)
            ->mapWithKeys(fn (array $blockData, int $index): array => isset($blockData['id']) ? [(int) $blockData['id'] => $index] : [])
            ->all();

        foreach ($revision->content_snapshot as $snapshotIndex => $blockData) {
            $restoredBlocks[$snapshotIndex] = PageBlock::create([
                'page_id'         => $page->id,
                'parent_block_id' => null,
                'block_type'      => $blockData['block_type'],
                'label'           => $blockData['label'] ?? null,
                'data'            => $blockData['data'] ?? null,
                'sort_order'      => $blockData['sort_order'] ?? 0,
                'is_visible'      => $blockData['is_visible'] ?? true,
            ]);
        }

        // Remap parent IDs in one upsert instead of N individual updates.
        $parentRows = [];
        foreach ($revision->content_snapshot as $snapshotIndex => $blockData) {
            $parentSnapshotId = $blockData['parent_block_id'] ?? null;
            $parentIndex = $parentSnapshotId !== null ? ($snapshotIdToIndex[(int) $parentSnapshotId] ?? null) : null;

            if ($parentIndex !== null && isset($restoredBlocks[$parentIndex])) {
                $parentRows[] = [
                    'id'              => $restoredBlocks[$snapshotIndex]->id,
                    'parent_block_id' => $restoredBlocks[$parentIndex]->id,
                ];
            }
        }

        if (! empty($parentRows)) {
            // Build a single CASE-WHEN update instead of N individual UPDATE queries.
            // IDs are all integers from our own database — no injection risk.
            $cases = collect($parentRows)
                ->map(fn (array $row): string => "WHEN {$row['id']} THEN {$row['parent_block_id']}")
                ->join(' ');
            $childIds = array_column($parentRows, 'id');

            DB::table('page_blocks')
                ->whereIn('id', $childIds)
                ->update(['parent_block_id' => DB::raw("CASE id {$cases} END")]);
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
