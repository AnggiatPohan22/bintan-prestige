<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'theme_zip' => [
                'required',
                'file',
                'mimes:zip',
                'max:51200', // 50 MB in kilobytes
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'theme_zip.required' => 'Please select a ZIP file to import.',
            'theme_zip.mimes'    => 'The uploaded file must be a ZIP archive.',
            'theme_zip.max'      => 'The theme ZIP must not exceed 50 MB.',
        ];
    }
}
