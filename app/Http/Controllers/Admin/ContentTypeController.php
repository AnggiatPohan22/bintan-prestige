<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreContentTypeRequest;
use App\Http\Requests\Admin\UpdateContentTypeRequest;
use App\Models\ContentType;
use Illuminate\Http\Request;

class ContentTypeController extends Controller
{
    public function index(Request $request)
    {
        $active = ContentType::query()
            ->when($request->search, fn ($q) => $q->where('label_plural', 'like', '%'.$request->search.'%'))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        $archived = ContentType::onlyTrashed()
            ->latest('deleted_at')
            ->paginate(20);

        return view('backend.content-types.index', compact('active', 'archived'));
    }

    public function create()
    {
        return view('backend.content-types.create', [
            'supports' => ContentType::SUPPORTS,
        ]);
    }

    public function store(StoreContentTypeRequest $request)
    {
        $contentType = ContentType::create($this->castBooleans($request->validated()));

        return redirect()
            ->route('admin.content-types.edit', $contentType)
            ->with('success', "Content type \"{$contentType->label_plural}\" created.");
    }

    public function edit(ContentType $contentType)
    {
        return view('backend.content-types.edit', [
            'contentType' => $contentType,
            'supports'    => ContentType::SUPPORTS,
        ]);
    }

    public function update(UpdateContentTypeRequest $request, ContentType $contentType)
    {
        $contentType->update($this->castBooleans($request->validated()));

        return redirect()
            ->route('admin.content-types.edit', $contentType)
            ->with('success', 'Content type updated.');
    }

    public function destroy(ContentType $contentType)
    {
        $contentType->delete();

        return redirect()
            ->route('admin.content-types.index')
            ->with('success', "\"{$contentType->label_plural}\" moved to archive.");
    }

    public function restore(int $id)
    {
        $contentType = ContentType::onlyTrashed()->findOrFail($id);
        $contentType->restore();

        return redirect()
            ->route('admin.content-types.index')
            ->with('success', "\"{$contentType->label_plural}\" restored.");
    }

    public function forceDelete(int $id)
    {
        $contentType = ContentType::onlyTrashed()->findOrFail($id);

        // RESTRICT at DB level — enforce at app level for a clear error message.
        if ($contentType->entries()->withTrashed()->exists()) {
            return redirect()
                ->route('admin.content-types.index')
                ->with('error', "Cannot permanently delete \"{$contentType->label_plural}\" — it still has content entries. Delete all entries first.");
        }

        $label = $contentType->label_plural;
        $contentType->forceDelete();

        return redirect()
            ->route('admin.content-types.index')
            ->with('success', "\"{$label}\" permanently deleted.");
    }

    /** Cast checkbox booleans from form submission (unchecked = absent from request). */
    private function castBooleans(array $data): array
    {
        foreach (['is_public', 'has_archive', 'is_active'] as $key) {
            $data[$key] = (bool) ($data[$key] ?? false);
        }

        return $data;
    }
}
