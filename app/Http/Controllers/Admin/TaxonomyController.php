<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTaxonomyRequest;
use App\Http\Requests\Admin\UpdateTaxonomyRequest;
use App\Models\ContentType;
use App\Models\Taxonomy;

class TaxonomyController extends Controller
{
    public function index()
    {
        $active  = Taxonomy::ordered()->get();
        $trashed = Taxonomy::onlyTrashed()->ordered()->get();

        return view('backend.taxonomies.index', compact('active', 'trashed'));
    }

    public function create()
    {
        $contentTypes = ContentType::orderBy('label_singular')->get();

        return view('backend.taxonomies.create', compact('contentTypes'));
    }

    public function store(StoreTaxonomyRequest $request)
    {
        $data = $request->validated();

        // An empty content_type_ids array means "all types" — store as null.
        if (isset($data['content_type_ids']) && $data['content_type_ids'] === []) {
            $data['content_type_ids'] = null;
        }

        Taxonomy::create($data);

        return redirect()
            ->route('admin.taxonomies.index')
            ->with('success', 'Taxonomy created.');
    }

    public function edit(Taxonomy $taxonomy)
    {
        $contentTypes = ContentType::orderBy('label_singular')->get();

        return view('backend.taxonomies.edit', compact('taxonomy', 'contentTypes'));
    }

    public function update(UpdateTaxonomyRequest $request, Taxonomy $taxonomy)
    {
        $data = $request->validated();

        if (isset($data['content_type_ids']) && $data['content_type_ids'] === []) {
            $data['content_type_ids'] = null;
        }

        $taxonomy->update($data);

        return redirect()
            ->route('admin.taxonomies.index')
            ->with('success', 'Taxonomy updated.');
    }

    public function destroy(Taxonomy $taxonomy)
    {
        // Soft-delete child terms alongside the taxonomy.
        $taxonomy->terms()->each(fn ($t) => $t->delete());
        $taxonomy->delete();

        return redirect()
            ->route('admin.taxonomies.index')
            ->with('success', 'Taxonomy moved to trash.');
    }

    public function restore(int $id)
    {
        $taxonomy = Taxonomy::onlyTrashed()->findOrFail($id);
        $taxonomy->restore();
        // Restore terms that were trashed at the same time (within 5 seconds).
        $taxonomy->terms()->onlyTrashed()
            ->where('deleted_at', '>=', $taxonomy->deleted_at)
            ->each(fn ($t) => $t->restore());

        return redirect()
            ->route('admin.taxonomies.index')
            ->with('success', 'Taxonomy restored.');
    }

    public function forceDelete(int $id)
    {
        $taxonomy = Taxonomy::onlyTrashed()->findOrFail($id);
        // MySQL CASCADE removes terms and pivot rows on force-delete.
        $taxonomy->forceDelete();

        return redirect()
            ->route('admin.taxonomies.index')
            ->with('success', 'Taxonomy permanently deleted.');
    }
}
