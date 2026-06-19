<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateThemeCustomizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth + admin middleware handles authorization
    }

    public function rules(): array
    {
        return [
            'tokens'   => ['nullable', 'array'],
            'tokens.*' => ['nullable', 'string', 'max:200'],
        ];
    }
}
