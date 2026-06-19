<?php

namespace App\Http\Requests\Admin;

use App\Models\Widget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $areas = array_keys($this->route('theme')?->widgetAreas() ?? []);

        return [
            'area'        => ['required', 'string', 'max:100', ...(filled($areas) ? [Rule::in($areas)] : [])],
            'widget_type' => ['required', Rule::in(Widget::TYPES)],
            'title'       => ['nullable', 'string', 'max:255'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_visible'  => ['nullable', 'boolean'],

            // Type-specific data fields
            'data'                => ['nullable', 'array'],
            'data.heading'        => ['nullable', 'string', 'max:255'],
            'data.content'        => ['nullable', 'string', 'max:50000'],
            'data.code'           => ['nullable', 'string', 'max:50000'],
            'data.src'            => ['nullable', 'string', 'max:2048'],
            'data.alt'            => ['nullable', 'string', 'max:255'],
            'data.link_url'       => ['nullable', 'string', 'max:2048'],
            'data.caption'        => ['nullable', 'string', 'max:500'],
            'data.links'          => ['nullable', 'array', 'max:50'],
            'data.links.*.label'  => ['nullable', 'string', 'max:150'],
            'data.links.*.url'    => ['nullable', 'string', 'max:2048'],
        ];
    }
}
