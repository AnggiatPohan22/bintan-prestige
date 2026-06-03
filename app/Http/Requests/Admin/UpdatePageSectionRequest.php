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
            'image' => ['nullable', 'string', 'max:500'],
            'mobile_image' => ['nullable', 'string', 'max:500'],
            'image_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'mobile_image_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'media_uploads' => ['nullable', 'array', 'max:10'],
            'media_uploads.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'section_animation' => ['nullable', 'string', 'in:ken-burns,zoom-in,zoom-out,fade,pan-left,pan-right,none'],
            'extra_data' => ['nullable', 'json'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function pageSectionData(): array
    {
        $validated = $this->validated();
        unset($validated['image_upload'], $validated['mobile_image_upload'], $validated['media_uploads']);

        $extraData = filled($validated['extra_data'] ?? null) ? json_decode($validated['extra_data'], true) : [];

        if (! empty($validated['section_animation'])) {
            $extraData['animation'] = $validated['section_animation'];
        }

        unset($validated['section_animation']);

        $validated['extra_data'] = $extraData ?: null;
        $validated['is_active'] = $this->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
