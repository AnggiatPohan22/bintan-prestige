<?php

namespace App\Http\Requests\Admin;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Rules\EligibleMenuLinkTarget;
use App\Rules\ValidMenuItemParent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $linkType = $this->input('link_type');
        $needsLinkable = in_array($linkType, ['page', 'product', 'category', 'destination'], true);
        $needsUrl = in_array($linkType, ['url', 'anchor'], true);

        $menu = $this->route('menu');
        $parentRules = ['nullable', 'integer'];

        if ($menu instanceof Menu) {
            $parentRules[] = new ValidMenuItemParent($menu);
        }

        return [
            'label' => ['required', 'string', 'max:255'],
            'link_type' => ['required', Rule::in(MenuItem::LINK_TYPES)],
            'linkable_id' => [Rule::requiredIf($needsLinkable), 'nullable', 'integer', new EligibleMenuLinkTarget((string) $linkType)],
            'url' => [Rule::requiredIf($needsUrl), 'nullable', 'string', 'max:2048'],
            'parent_id' => $parentRules,
            'target' => ['nullable', Rule::in(['_self', '_blank'])],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'A menu label is required.',
            'linkable_id.required' => 'Please choose what this item links to.',
            'url.required' => 'A URL or anchor is required for this link type.',
        ];
    }
}
