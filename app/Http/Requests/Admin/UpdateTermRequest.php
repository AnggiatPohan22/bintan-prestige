<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name') && ! $this->filled('slug')) {
            $this->merge(['slug' => Str::slug($this->string('name'))]);
        }

        if ($this->input('parent_id') === '' || $this->input('parent_id') === '0') {
            $this->merge(['parent_id' => null]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $taxonomyId = $this->route('taxonomy')?->id;
        $termId     = $this->route('term')?->id;

        return [
            'name'        => ['required', 'string', 'max:200'],
            'slug'        => [
                'required', 'string', 'max:200', 'regex:/^[a-z0-9][a-z0-9\-]*$/',
                Rule::unique('terms', 'slug')
                    ->where('taxonomy_id', $taxonomyId)
                    ->ignore($termId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'parent_id'   => ['nullable', 'integer', 'exists:terms,id'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'  => 'Slug may only contain lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'A term with this slug already exists in this taxonomy.',
        ];
    }
}
