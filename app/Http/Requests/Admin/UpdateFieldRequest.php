<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesColumnDefaults;
use App\Support\FieldTypeRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFieldRequest extends FormRequest
{
    use NormalizesColumnDefaults;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->applyColumnDefaults(['sort_order' => 0]);
    }

    public function rules(): array
    {
        $groupId = $this->route('field_group')?->id;
        $fieldId = $this->route('field')?->id;

        return [
            'type'              => ['required', 'string', Rule::in(FieldTypeRegistry::keys())],
            'key'               => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('fields')->where('field_group_id', $groupId)->ignore($fieldId)],
            'label'             => ['required', 'string', 'max:150'],
            'instructions'      => ['nullable', 'string', 'max:5000'],
            'is_required'       => ['boolean'],
            'is_filterable'     => ['boolean'],
            'settings'          => ['nullable', 'array'],
            'default_value'     => ['nullable'],
            'conditional_logic' => ['nullable', 'array'],
            'sort_order'        => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in'    => 'The selected field type is not in the catalog.',
            'key.regex'  => 'Key must start with a letter and contain only lowercase letters, numbers, and underscores.',
            'key.unique' => 'A field with this key already exists in this group.',
        ];
    }
}
