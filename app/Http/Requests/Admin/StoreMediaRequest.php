<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'extensions:jpg,jpeg,png,gif,webp', 'max:5120'],
            'collection' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Choose at least one image to upload.',
            'files.*.image' => 'Only image files are allowed.',
            'files.*.mimes' => 'Allowed types: jpg, jpeg, png, gif, webp.',
            'files.*.extensions' => 'The file extension must be jpg, jpeg, png, gif, or webp.',
            'files.*.max' => 'Each image must be 5 MB or smaller.',
        ];
    }
}
