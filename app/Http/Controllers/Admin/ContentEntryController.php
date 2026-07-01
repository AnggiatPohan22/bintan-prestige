<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreContentEntryRequest;
use App\Http\Requests\Admin\UpdateContentEntryRequest;
use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\Taxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContentEntryController extends Controller
{
    public function index(Request $request, ContentType $contentType)
    {
        $status = $request->query('status');

        $active = $contentType->entries()
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->filled('search'), fn ($q) => $q->where(
                fn ($q2) => $q2->where('title', 'like', '%'.$request->search.'%')
                               ->orWhere('slug', 'like', '%'.$request->search.'%')
            ))
            ->with('author')
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        $archived = $contentType->entries()
            ->onlyTrashed()
            ->ordered()
            ->get();

        $counts = ContentEntry::STATUSES;

        return view('backend.content-entries.index', compact('contentType', 'active', 'archived', 'counts'));
    }

    public function create(ContentType $contentType)
    {
        $groups = $contentType->fieldGroups()
            ->with(['fields' => fn ($q) => $q->ordered()])
            ->ordered()
            ->get();

        $taxonomies = $this->taxonomiesForType($contentType);

        return view('backend.content-entries.create', compact('contentType', 'groups', 'taxonomies'));
    }

    public function store(StoreContentEntryRequest $request, ContentType $contentType)
    {
        $validated = $request->validated();
        $termIds   = array_map('intval', (array) ($validated['terms'] ?? []));
        $data      = collect($validated)->except('terms')->toArray();

        $entry = $contentType->entries()->create(array_merge($data, [
            'author_id' => Auth::id(),
        ]));

        if ($termIds !== []) {
            $entry->terms()->sync($termIds);
        }

        return redirect()
            ->route('admin.content-types.entries.edit', [$contentType, $entry])
            ->with('success', 'Entry created.');
    }

    public function edit(ContentType $contentType, ContentEntry $entry)
    {
        $this->authorizeEntry($contentType, $entry);

        $groups = $contentType->fieldGroups()
            ->with(['fields' => fn ($q) => $q->ordered()])
            ->ordered()
            ->get();

        $taxonomies    = $this->taxonomiesForType($contentType);
        $selectedTermIds = $entry->terms()->pluck('terms.id')->map(fn ($id) => (int) $id)->all();

        return view('backend.content-entries.edit', compact('contentType', 'entry', 'groups', 'taxonomies', 'selectedTermIds'));
    }

    public function update(UpdateContentEntryRequest $request, ContentType $contentType, ContentEntry $entry)
    {
        $this->authorizeEntry($contentType, $entry);

        $validated = $request->validated();
        $termIds   = array_map('intval', (array) ($validated['terms'] ?? []));
        $data      = collect($validated)->except('terms')->toArray();

        $entry->update($data);
        $entry->terms()->sync($termIds);

        return redirect()
            ->route('admin.content-types.entries.edit', [$contentType, $entry])
            ->with('success', 'Entry updated.');
    }

    public function destroy(ContentType $contentType, ContentEntry $entry)
    {
        $this->authorizeEntry($contentType, $entry);
        $entry->delete();

        return redirect()
            ->route('admin.content-types.entries.index', $contentType)
            ->with('success', 'Entry moved to trash.');
    }

    public function restore(ContentType $contentType, int $id)
    {
        $entry = $contentType->entries()->onlyTrashed()->findOrFail($id);
        $entry->restore();

        return redirect()
            ->route('admin.content-types.entries.index', $contentType)
            ->with('success', 'Entry restored.');
    }

    public function forceDelete(ContentType $contentType, int $id)
    {
        $entry = $contentType->entries()->onlyTrashed()->findOrFail($id);
        $entry->forceDelete();

        return redirect()
            ->route('admin.content-types.entries.index', $contentType)
            ->with('success', 'Entry permanently deleted.');
    }

    private function authorizeEntry(ContentType $contentType, ContentEntry $entry): void
    {
        if ($entry->content_type_id !== $contentType->id) {
            abort(404);
        }
    }

    /**
     * Taxonomies that apply to the given content type, with terms eager-loaded.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Taxonomy>
     */
    private function taxonomiesForType(ContentType $contentType): \Illuminate\Database\Eloquent\Collection
    {
        return Taxonomy::where(function ($q) use ($contentType) {
            $q->whereNull('content_type_ids')
              ->orWhereJsonContains('content_type_ids', $contentType->id);
        })
        ->with([
            'terms' => fn ($q) => $q->ordered(),
            'terms.children' => fn ($q) => $q->ordered(),
        ])
        ->ordered()
        ->get();
    }
}
