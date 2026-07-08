<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesColumnDefaults;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaxonomyRequest extends FormRequest
{
    use NormalizesColumnDefaults;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->applyColumnDefaults(['sort_order' => 0]);

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
        $taxonomyId = $this->route('taxonomy')?->id;

        return [
            'slug'             => [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9\-_]*$/',
                Rule::unique('taxonomies', 'slug')->ignore($taxonomyId),
            ],
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
            'slug.regex'  => 'Slug may only contain lowercase letters, numbers, hyphens, and underscores.',
            'slug.unique' => 'A taxonomy with this slug already exists.',
        ];
    }
}
