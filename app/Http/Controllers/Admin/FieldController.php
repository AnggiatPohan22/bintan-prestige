<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFieldRequest;
use App\Http\Requests\Admin\UpdateFieldRequest;
use App\Models\ContentType;
use App\Models\Field;
use App\Models\FieldGroup;
use App\Support\FieldTypeRegistry;
use Illuminate\Http\Request;

class FieldController extends Controller
{
    public function create(ContentType $contentType, FieldGroup $fieldGroup)
    {
        $this->authorizeGroup($contentType, $fieldGroup);

        return view('backend.fields.create', [
            'contentType'  => $contentType,
            'fieldGroup'   => $fieldGroup,
            'fieldTypes'   => FieldTypeRegistry::all(),
        ]);
    }

    public function store(StoreFieldRequest $request, ContentType $contentType, FieldGroup $fieldGroup)
    {
        $this->authorizeGroup($contentType, $fieldGroup);

        $data = $this->castBooleans($request->validated());
        $field = $fieldGroup->fields()->create($data);

        return redirect()
            ->route('admin.content-types.field-groups.edit', [$contentType, $fieldGroup])
            ->with('success', "Field \"{$field->label}\" added.");
    }

    public function edit(ContentType $contentType, FieldGroup $fieldGroup, Field $field)
    {
        $this->authorizeGroup($contentType, $fieldGroup);
        $this->authorizeField($fieldGroup, $field);

        return view('backend.fields.edit', [
            'contentType' => $contentType,
            'fieldGroup'  => $fieldGroup,
            'field'       => $field,
            'fieldTypes'  => FieldTypeRegistry::all(),
        ]);
    }

    public function update(UpdateFieldRequest $request, ContentType $contentType, FieldGroup $fieldGroup, Field $field)
    {
        $this->authorizeGroup($contentType, $fieldGroup);
        $this->authorizeField($fieldGroup, $field);

        $field->update($this->castBooleans($request->validated()));

        return redirect()
            ->route('admin.content-types.field-groups.edit', [$contentType, $fieldGroup])
            ->with('success', "Field \"{$field->label}\" updated.");
    }

    public function destroy(ContentType $contentType, FieldGroup $fieldGroup, Field $field)
    {
        $this->authorizeGroup($contentType, $fieldGroup);
        $this->authorizeField($fieldGroup, $field);
        $label = $field->label;
        $field->delete();

        return redirect()
            ->route('admin.content-types.field-groups.edit', [$contentType, $fieldGroup])
            ->with('success', "\"{$label}\" deleted.");
    }

    public function reorder(Request $request, ContentType $contentType, FieldGroup $fieldGroup)
    {
        $this->authorizeGroup($contentType, $fieldGroup);
        $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($request->ids as $pos => $id) {
            $fieldGroup->fields()->where('id', $id)->update(['sort_order' => $pos]);
        }

        return response()->json(['success' => true]);
    }

    private function authorizeGroup(ContentType $contentType, FieldGroup $fieldGroup): void
    {
        if ($fieldGroup->content_type_id !== $contentType->id) {
            abort(404);
        }
    }

    private function authorizeField(FieldGroup $fieldGroup, Field $field): void
    {
        if ($field->field_group_id !== $fieldGroup->id) {
            abort(404);
        }
    }

    private function castBooleans(array $data): array
    {
        foreach (['is_required', 'is_filterable'] as $key) {
            $data[$key] = (bool) ($data[$key] ?? false);
        }

        return $data;
    }
}
