<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreTaxonomyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('label_singular') && ! $this->filled('slug')) {
            $this->merge(['slug' => Str::slug($this->string('label_singular'))]);
        }

        // content_type_ids arrives as string[] from checkboxes; cast to int[].
        // If no checkboxes are checked the key is absent → null (all types).
        if ($this->has('content_type_ids') && is_array($this->input('content_type_ids'))) {
            $this->merge([
                'content_type_ids' => array_values(
                    array_map('intval', (array) $this->input('content_type_ids'))
                ),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'slug'             => ['required', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9\-_]*$/', 'unique:taxonomies,slug'],
            'label_singular'   => ['required', 'string', 'max:100'],
            'label_plural'     => ['required', 'string', 'max:100'],
            'description'      => ['nullable', 'string', 'max:5000'],
            'is_hierarchical'  => ['boolean'],
            'content_type_ids' => ['nullable', 'array'],
            'content_type_ids.*' => ['integer', 'exists:content_types,id'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Slug may only contain lowercase letters, numbers, hyphens, and underscores.',
            'slug.unique' => 'A taxonomy with this slug already exists.',
        ];
    }
}
