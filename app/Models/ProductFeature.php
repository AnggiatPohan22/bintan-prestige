<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFeature extends Model
{
    use Translatable;

    /**
     * @var list<string> Phase 7 (B6.1). Only `value` is translated — `label` is
     * a system key ("included" / "not_included") used for filtering/grouping
     * and must stay identical across locales.
     */
    protected array $translatable = ['value'];

    protected $fillable = [
        'product_id',
        'label',
        'value',
        'sort_order',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class
        );
    }

    public function getValueAttribute(): ?string { return $this->translate('value'); }
}
