<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:500'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'mobile_image_path' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'mobile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'extra_data' => ['nullable', 'json'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function pageSectionData(): array
    {
        $validated = $this->validated();

        $data = [
            'label' => $validated['label'] ?? null,
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'description' => $validated['description'] ?? null,
            'button_text' => $validated['button_text'] ?? null,
            'button_url' => $validated['button_url'] ?? null,
            'image' => $validated['image_path'] ?? null,
            'mobile_image' => $validated['mobile_image_path'] ?? null,
            'extra_data' => filled($validated['extra_data'] ?? null)
                ? json_decode($validated['extra_data'], true)
                : null,
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ];

        return $data;
    }
}
