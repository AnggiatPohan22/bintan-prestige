<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesColumnDefaults;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFieldGroupRequest extends FormRequest
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
        $contentTypeId = $this->route('content_type')?->id;
        $groupId       = $this->route('field_group')?->id;

        return [
            'label'       => ['required', 'string', 'max:150'],
            'key'         => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('field_groups')->where('content_type_id', $contentTypeId)->ignore($groupId)],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.regex'  => 'Key must start with a letter and contain only lowercase letters, numbers, and underscores.',
            'key.unique' => 'A field group with this key already exists for this content type.',
        ];
    }
}
