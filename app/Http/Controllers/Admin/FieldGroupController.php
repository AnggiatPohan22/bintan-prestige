<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFieldGroupRequest;
use App\Http\Requests\Admin\UpdateFieldGroupRequest;
use App\Models\ContentType;
use App\Models\FieldGroup;
use Illuminate\Http\Request;

class FieldGroupController extends Controller
{
    public function index(ContentType $contentType)
    {
        $groups = $contentType->fieldGroups()->with('fields')->ordered()->get();

        return view('backend.field-groups.index', compact('contentType', 'groups'));
    }

    public function create(ContentType $contentType)
    {
        return view('backend.field-groups.create', compact('contentType'));
    }

    public function store(StoreFieldGroupRequest $request, ContentType $contentType)
    {
        $group = $contentType->fieldGroups()->create($request->validated());

        return redirect()
            ->route('admin.content-types.field-groups.edit', [$contentType, $group])
            ->with('success', "Field group \"{$group->label}\" created.");
    }

    public function edit(ContentType $contentType, FieldGroup $fieldGroup)
    {
        $this->authorizeGroup($contentType, $fieldGroup);
        $fieldGroup->load(['fields' => fn ($q) => $q->ordered()]);

        return view('backend.field-groups.edit', compact('contentType', 'fieldGroup'));
    }

    public function update(UpdateFieldGroupRequest $request, ContentType $contentType, FieldGroup $fieldGroup)
    {
        $this->authorizeGroup($contentType, $fieldGroup);
        $fieldGroup->update($request->validated());

        return redirect()
            ->route('admin.content-types.field-groups.edit', [$contentType, $fieldGroup])
            ->with('success', 'Field group updated.');
    }

    public function destroy(ContentType $contentType, FieldGroup $fieldGroup)
    {
        $this->authorizeGroup($contentType, $fieldGroup);
        $label = $fieldGroup->label;
        $fieldGroup->delete();

        return redirect()
            ->route('admin.content-types.field-groups.index', $contentType)
            ->with('success', "\"{$label}\" deleted.");
    }

    public function reorder(Request $request, ContentType $contentType)
    {
        $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($request->ids as $pos => $id) {
            $contentType->fieldGroups()->where('id', $id)->update(['sort_order' => $pos]);
        }

        return response()->json(['success' => true]);
    }

    private function authorizeGroup(ContentType $contentType, FieldGroup $fieldGroup): void
    {
        if ($fieldGroup->content_type_id !== $contentType->id) {
            abort(404);
        }
    }
}
