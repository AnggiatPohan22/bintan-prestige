<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesColumnDefaults;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreTermRequest extends FormRequest
{
    use NormalizesColumnDefaults;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->applyColumnDefaults(['sort_order' => 0]);

        if ($this->filled('name') && ! $this->filled('slug')) {
            $this->merge(['slug' => Str::slug($this->string('name'))]);
        }

        // Empty string parent_id (unselected) → null
        if ($this->input('parent_id') === '' || $this->input('parent_id') === '0') {
            $this->merge(['parent_id' => null]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $taxonomyId = $this->route('taxonomy')?->id;

        return [
            'name'        => ['required', 'string', 'max:200'],
            'slug'        => [
                'required', 'string', 'max:200', 'regex:/^[a-z0-9][a-z0-9\-]*$/',
                Rule::unique('terms', 'slug')->where('taxonomy_id', $taxonomyId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'parent_id'   => ['nullable', 'integer', 'exists:terms,id'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            // Phase 7 (B7) — per-locale translations.
            'translations' => ['nullable', 'array'],
            'translations.*.name' => ['nullable', 'string', 'max:200'],
            'translations.*.description' => ['nullable', 'string', 'max:5000'],
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
