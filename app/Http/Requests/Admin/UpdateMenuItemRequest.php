<?php

namespace App\Http\Requests\Admin;

use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $linkType      = $this->input('link_type');
        $needsLinkable = in_array($linkType, ['page', 'product', 'category', 'destination'], true);
        $needsUrl      = in_array($linkType, ['url', 'anchor'], true);

        $table = match ($linkType) {
            'page'        => 'pages',
            'product'     => 'products',
            'category'    => 'categories',
            'destination' => 'destinations',
            default       => null,
        };

        return [
            'label'       => ['required', 'string', 'max:255'],
            'link_type'   => ['required', Rule::in(MenuItem::LINK_TYPES)],
            'linkable_id' => [Rule::requiredIf($needsLinkable), 'nullable', $table ? "exists:{$table},id" : 'integer'],
            'url'         => [Rule::requiredIf($needsUrl), 'nullable', 'string', 'max:2048'],
            'parent_id'   => ['nullable', 'integer', 'exists:menu_items,id'],
            'target'      => ['nullable', Rule::in(['_self', '_blank'])],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required'       => 'A menu label is required.',
            'linkable_id.required' => 'Please choose what this item links to.',
            'url.required'         => 'A URL or anchor is required for this link type.',
        ];
    }
}
