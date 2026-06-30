<?php

namespace App\Http\Requests\Admin;

use App\Models\ContentEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreContentEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $type = $this->route('content_type');

        // Auto-generate slug from title when the content type supports it.
        if ($type?->supports('slug') && $this->filled('title') && ! $this->filled('slug')) {
            $this->merge(['slug' => Str::slug($this->string('title'))]);
        }

        // Clean up checkbox arrays — the hidden-input fallback adds an empty string.
        if ($this->has('data') && is_array($this->input('data'))) {
            $cleaned = array_map(
                fn ($v) => is_array($v)
                    ? array_values(array_filter($v, fn ($s) => $s !== null && $s !== ''))
                    : $v,
                $this->input('data')
            );
            $this->merge(['data' => $cleaned]);
        }
    }

    public function rules(): array
    {
        $contentTypeId = $this->route('content_type')?->id;

        return [
            'title'            => ['nullable', 'string', 'max:500'],
            'slug'             => [
                'nullable', 'string', 'max:200',
                'regex:/^[a-z0-9][a-z0-9\-]*$/',
                Rule::unique('content_entries')->where('content_type_id', $contentTypeId),
            ],
            'excerpt'          => ['nullable', 'string', 'max:5000'],
            'status'           => ['required', Rule::in(ContentEntry::STATUSES)],
            'published_at'     => ['nullable', 'date', 'required_if:status,scheduled'],
            'template'         => ['nullable', 'string', 'max:100'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
            'data'             => ['nullable', 'array'],
            'data.*'           => ['nullable'],  // per-field validation added at B5
            'seo'              => ['nullable', 'array'],
            'seo.title'        => ['nullable', 'string', 'max:200'],
            'seo.description'  => ['nullable', 'string', 'max:500'],
            'seo.og_image'     => ['nullable', 'integer'],
            'seo.canonical'    => ['nullable', 'url', 'max:2083'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'            => 'Slug may only contain lowercase letters, numbers, and hyphens.',
            'slug.unique'           => 'A entry with this slug already exists for this content type.',
            'published_at.required_if' => 'A publish date is required when status is "Scheduled".',
        ];
    }
}
