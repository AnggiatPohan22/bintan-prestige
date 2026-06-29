<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDashboardAppearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth + admin + can:manage-users middleware handles authorization
    }

    public function rules(): array
    {
        return [
            'mode'          => ['required', Rule::in(['dark', 'light', 'light_classic'])],
            'sidebar_style' => ['required', Rule::in(['dark', 'light'])],
            'sidebar_bg'    => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{3,6}$/'],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{3,6}$/'],
            'primary_hover' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{3,6}$/'],
            'primary_text'  => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{3,6}$/'],
            'accent_color'  => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{3,6}$/'],
            'gold_color'    => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{3,6}$/'],
            'show_gold'     => ['boolean'],
            'bg_base'       => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{3,6}$/'],
            'bg_card'       => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{3,6}$/'],
            'bg_input'      => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{3,6}$/'],
            'preset_name'   => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.regex' => 'Masukkan warna yang valid (format: #RRGGBB atau #RGB).',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Checkbox "show_gold" tidak terkirim saat unchecked — default false
        $this->merge([
            'show_gold' => $this->boolean('show_gold'),
        ]);
    }
}
