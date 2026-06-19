<?php

namespace App\Rules;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Page;
use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EligibleMenuLinkTarget implements ValidationRule
{
    public function __construct(
        private readonly string $linkType,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $exists = match ($this->linkType) {
            'page' => Page::query()->published()->whereKey($value)->exists(),
            'product' => Product::query()->publiclyVisible()->whereKey($value)->exists(),
            'category' => Category::query()->where('is_active', true)->whereKey($value)->exists(),
            'destination' => Destination::query()->where('is_active', true)->whereKey($value)->exists(),
            default => true,
        };

        if (! $exists) {
            $fail('The selected target is not available on the public site.');
        }
    }
}
