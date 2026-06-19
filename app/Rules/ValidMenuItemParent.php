<?php

namespace App\Rules;

use App\Models\Menu;
use App\Models\MenuItem;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidMenuItemParent implements ValidationRule
{
    public function __construct(
        private readonly Menu $menu,
        private readonly ?MenuItem $item = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if ($this->menu->location !== 'header') {
            $fail('Footer menu items must remain at the top level.');

            return;
        }

        $parent = MenuItem::query()->find($value);

        if (! $parent || $parent->menu_id !== $this->menu->id) {
            $fail('The selected parent must belong to this menu.');

            return;
        }

        if ($parent->parent_id !== null) {
            $fail('Menu hierarchy supports a maximum of one child level.');

            return;
        }

        if ($this->item && $parent->id === $this->item->id) {
            $fail('A menu item cannot be its own parent.');

            return;
        }

        if ($this->item && $this->item->children()->exists()) {
            $fail('An item with children cannot become a child item.');
        }
    }
}
