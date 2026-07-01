<?php

namespace App\Http\Requests\Admin;

use App\Models\ContentEntry;
use App\Support\FieldValidationResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContentEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
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
        $contentType   = $this->route('content_type');
        $contentTypeId = $contentType?->id;
        $entryId       = $this->route('entry')?->id;
        $fieldRules    = $contentType !== null
            ? (new FieldValidationResolver())->resolveForContentType($contentType)
            : [];

        return array_merge([
            'title'           => ['nullable', 'string', 'max:500'],
            'slug'            => [
                'nullable', 'string', 'max:200',
                'regex:/^[a-z0-9][a-z0-9\-]*$/',
                Rule::unique('content_entries')
                    ->where('content_type_id', $contentTypeId)
                    ->ignore($entryId),
            ],
            'excerpt'         => ['nullable', 'string', 'max:5000'],
            'status'          => ['required', Rule::in(ContentEntry::STATUSES)],
            'published_at'    => ['nullable', 'date', 'required_if:status,scheduled'],
            'template'        => ['nullable', 'string', 'max:100'],
            'sort_order'      => ['nullable', 'integer', 'min:0'],
            'data'            => ['nullable', 'array'],
            'terms'           => ['nullable', 'array'],
            'terms.*'         => ['integer', 'exists:terms,id'],
            'seo'             => ['nullable', 'array'],
            'seo.title'       => ['nullable', 'string', 'max:200'],
            'seo.description' => ['nullable', 'string', 'max:500'],
            'seo.og_image'    => ['nullable', 'integer'],
            'seo.canonical'   => ['nullable', 'url', 'max:2083'],
        ], $fieldRules);
    }

    public function messages(): array
    {
        return [
            'slug.regex'               => 'Slug may only contain lowercase letters, numbers, and hyphens.',
            'slug.unique'              => 'A entry with this slug already exists for this content type.',
            'published_at.required_if' => 'A publish date is required when status is "Scheduled".',
        ];
    }
}
