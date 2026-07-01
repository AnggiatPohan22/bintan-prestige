<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTermRequest;
use App\Http\Requests\Admin\UpdateTermRequest;
use App\Models\Taxonomy;
use App\Models\Term;

class TermController extends Controller
{
    public function index(Taxonomy $taxonomy)
    {
        $terms  = $taxonomy->terms()
            ->ordered()
            ->with('children')
            ->get();

        $trashed = $taxonomy->terms()
            ->onlyTrashed()
            ->ordered()
            ->get();

        return view('backend.taxonomies.terms.index', compact('taxonomy', 'terms', 'trashed'));
    }

    public function create(Taxonomy $taxonomy)
    {
        $parents = $taxonomy->is_hierarchical
            ? $taxonomy->terms()->ordered()->get()
            : collect();

        return view('backend.taxonomies.terms.create', compact('taxonomy', 'parents'));
    }

    public function store(StoreTermRequest $request, Taxonomy $taxonomy)
    {
        $taxonomy->terms()->create($request->validated());

        return redirect()
            ->route('admin.taxonomies.terms.index', $taxonomy)
            ->with('success', 'Term created.');
    }

    public function edit(Taxonomy $taxonomy, Term $term)
    {
        $this->authorizeTerm($taxonomy, $term);

        $parents = $taxonomy->is_hierarchical
            ? $taxonomy->terms()->where('id', '!=', $term->id)->ordered()->get()
            : collect();

        return view('backend.taxonomies.terms.edit', compact('taxonomy', 'term', 'parents'));
    }

    public function update(UpdateTermRequest $request, Taxonomy $taxonomy, Term $term)
    {
        $this->authorizeTerm($taxonomy, $term);
        $term->update($request->validated());

        return redirect()
            ->route('admin.taxonomies.terms.index', $taxonomy)
            ->with('success', 'Term updated.');
    }

    public function destroy(Taxonomy $taxonomy, Term $term)
    {
        $this->authorizeTerm($taxonomy, $term);
        // Soft-delete child terms first.
        $term->children()->each(fn ($c) => $c->delete());
        $term->delete();

        return redirect()
            ->route('admin.taxonomies.terms.index', $taxonomy)
            ->with('success', 'Term moved to trash.');
    }

    public function restore(Taxonomy $taxonomy, int $id)
    {
        $term = $taxonomy->terms()->onlyTrashed()->findOrFail($id);
        $term->restore();

        return redirect()
            ->route('admin.taxonomies.terms.index', $taxonomy)
            ->with('success', 'Term restored.');
    }

    public function forceDelete(Taxonomy $taxonomy, int $id)
    {
        $term = $taxonomy->terms()->onlyTrashed()->findOrFail($id);
        // MySQL CASCADE removes pivot rows on force-delete.
        $term->forceDelete();

        return redirect()
            ->route('admin.taxonomies.terms.index', $taxonomy)
            ->with('success', 'Term permanently deleted.');
    }

    private function authorizeTerm(Taxonomy $taxonomy, Term $term): void
    {
        if ($term->taxonomy_id !== $taxonomy->id) {
            abort(404);
        }
    }
}
