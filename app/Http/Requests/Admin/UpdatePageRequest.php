<?php

namespace App\Http\Requests\Admin;

use App\Support\PageTemplateRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('title') && ! $this->filled('slug')) {
            $this->merge([
                'slug' => Str::slug($this->title),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            // Slug is unique within the page's own locale.
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('pages', 'slug')->where('locale', $this->page->locale)->ignore($this->page), Rule::notIn(['admin', 'products', 'api', 'login', 'register'])],
            'status' => ['required', Rule::in(['draft', 'published', 'scheduled'])],
            'publish_at' => ['nullable', 'date'],
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('page_templates', 'id')->where(
                    fn ($query) => $query
                        ->where('is_active', true)
                        ->whereIn('blade_file', PageTemplateRegistry::keys())
                ),
            ],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'seo_robots' => ['nullable', 'string', 'in:,index, follow,noindex, follow,noindex, nofollow'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Page title is required.',
            'slug.unique' => 'This slug is already in use.',
            'slug.not_in' => 'This slug is reserved and cannot be used.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be draft, published, or scheduled.',
        ];
    }
}
