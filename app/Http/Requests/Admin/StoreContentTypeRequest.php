<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesColumnDefaults;
use App\Models\ContentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreContentTypeRequest extends FormRequest
{
    use NormalizesColumnDefaults;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // NOT-NULL columns with a DB default — coerce blank form fields back to
        // that default so create() never inserts an explicit NULL.
        $this->applyColumnDefaults(['icon' => 'file-lines', 'menu_position' => 0]);

        if ($this->filled('label_singular') && ! $this->filled('slug')) {
            $this->merge(['slug' => Str::slug($this->string('label_singular'))]);
        }

        if (
            $this->filled('label_plural')
            && ! $this->filled('route_base')
            && $this->boolean('is_public')
            && $this->boolean('has_archive')
        ) {
            $this->merge(['route_base' => Str::slug($this->string('label_plural'))]);
        }
    }

    public function rules(): array
    {
        return [
            'slug'           => ['required', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9\-]*$/', 'unique:content_types,slug', Rule::notIn(ContentType::RESERVED_PREFIXES)],
            'label_singular' => ['required', 'string', 'max:150'],
            'label_plural'   => ['required', 'string', 'max:150'],
            'icon'           => ['nullable', 'string', 'max:50'],
            'description'    => ['nullable', 'string', 'max:5000'],
            'is_public'      => ['boolean'],
            'has_archive'    => ['boolean'],
            'route_base'     => [
                'nullable', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9\-]*$/',
                'unique:content_types,route_base',
                Rule::notIn(ContentType::RESERVED_PREFIXES),
                'required_if:is_public,1,has_archive,1',
            ],
            'supports'       => ['nullable', 'array'],
            'supports.*'     => [Rule::in(ContentType::SUPPORTS)],
            'menu_position'  => ['nullable', 'integer', 'min:0'],
            'is_active'      => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'           => 'Slug may only contain lowercase letters, numbers, and hyphens.',
            'slug.unique'          => 'This slug is already taken.',
            'slug.not_in'          => 'This slug is reserved and cannot be used.',
            'route_base.regex'     => 'Route base may only contain lowercase letters, numbers, and hyphens.',
            'route_base.unique'    => 'This route base is already in use by another content type.',
            'route_base.not_in'    => 'This route base is reserved and cannot be used.',
            'route_base.required_if' => 'A route base is required for public types with an archive.',
        ];
    }
}
