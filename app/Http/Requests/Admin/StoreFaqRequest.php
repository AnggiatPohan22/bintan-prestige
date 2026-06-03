<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page_key' => ['nullable', 'string', 'max:255'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function faqData(): array
    {
        $validated = $this->validated();
        $validated['page_key'] = $validated['page_key'] ?? 'home';
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $this->boolean('is_active');

        return $validated;
    }
}
