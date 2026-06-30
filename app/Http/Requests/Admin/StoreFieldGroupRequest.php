<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreFieldGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('label') && ! $this->filled('key')) {
            $this->merge(['key' => Str::slug($this->string('label'), '_')]);
        }
    }

    public function rules(): array
    {
        $contentTypeId = $this->route('content_type')?->id;

        return [
            'label'       => ['required', 'string', 'max:150'],
            'key'         => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('field_groups')->where('content_type_id', $contentTypeId)],
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
