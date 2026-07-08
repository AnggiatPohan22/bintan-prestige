<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
            'data' => ['nullable', 'array'],
            'parent_block_id' => ['nullable', 'integer'],
        ];
    }
}
