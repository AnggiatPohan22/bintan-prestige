<?php

namespace App\Http\Requests\Admin;

use App\Http\Controllers\Admin\PageBlockController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePageBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'block_type' => ['required', Rule::in(PageBlockController::blockTypes())],
            'label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
