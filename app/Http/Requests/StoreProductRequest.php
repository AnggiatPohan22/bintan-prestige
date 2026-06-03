<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest
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
                'required',
                'exists:categories,id'
            ],

            'destination_id' => [
                'required',
                'exists:destinations,id'
            ],

            'name' => [
                'required',
                'max:255'
            ],

            'slug' => [
                'nullable',
                'max:255',
                'unique:products,slug'
            ],

            'short_description' => [
                'required',
                'string',
                'max:500'
            ],

            'description' => [
                'required',
                'string'
            ],

            'meeting_point' => [
                'required',
                'max:255'
            ],

            'duration' => [
                'required',
                'max:100'
            ],

            'whatsapp_number' => [
                'required',
                'max:30'
            ],

            'idr_price' => [
                'required',
                'numeric'
            ],

            'sgd_price' => [
                'required',
                'numeric'
            ],

            'status' => [
                'required',
                'in:draft,published'
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

    public function messages(): array
    {
        return [

            'category_id.required' =>
                'Category wajib dipilih.',

            'destination_id.required' =>
                'Destination wajib dipilih.',

            'name.required' =>
                'Product name wajib diisi.',

            'short_description.required' =>
                'Short description wajib diisi.',

            'description.required' =>
                'Description wajib diisi.',

            'meeting_point.required' =>
                'Meeting point wajib diisi.',

            'duration.required' =>
                'Duration wajib diisi.',

            'whatsapp_number.required' =>
                'WhatsApp number wajib diisi.',

            'idr_price.required' =>
                'IDR price wajib diisi.',

            'sgd_price.required' =>
                'SGD price wajib diisi.',

            'status.required' =>
                'Status wajib dipilih.',
        ];
    }
}
