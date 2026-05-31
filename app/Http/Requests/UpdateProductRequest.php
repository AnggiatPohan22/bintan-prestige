<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest
    extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'category_id' => [
                'required'
            ],

            'destination_id' => [
                'required'
            ],

            'name' => [
                'required',
                'max:255'
            ],

            'idr_price' => [
                'required',
                'numeric'
            ],

            'sgd_price' => [
                'required',
                'numeric'
            ],

            'thumbnail' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048'
            ],

            'gallery' => [
                'nullable',
                'array',
            ],

            'gallery.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'pickup_available' => ['nullable'],
            'pickup_type' => ['nullable', 'max:255'],
            'pickup_note' => ['nullable'],

            'cta_title' => ['nullable', 'max:255'],
            'cta_description' => ['nullable'],
            'cta_button_text' => ['nullable', 'max:255'],

            'meta_title' => ['nullable', 'max:255'],
            'meta_description' => ['nullable'],
            'meta_keywords' => ['nullable'],
            'canonical_url' => ['nullable', 'url'],
        ];
    }
}
