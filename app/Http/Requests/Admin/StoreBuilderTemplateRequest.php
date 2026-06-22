<?php

namespace App\Http\Requests\Admin;

use App\Support\PageTemplateRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBuilderTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:100'],
            'base_template_id' => [
                'nullable',
                'integer',
                Rule::exists('page_templates', 'id')->where(
                    fn ($query) => $query
                        ->where('is_active', true)
                        ->whereIn('blade_file', PageTemplateRegistry::keys())
                ),
            ],
            'template_data' => ['required', 'array', 'max:200'],
        ];
    }
}
