<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductHighlight extends Model
{
    use Translatable;

    /** @var list<string> Phase 7 (B6.1) — sidecar-translated copy. `icon` shared. */
    protected array $translatable = ['title'];

    protected $fillable = [
        'product_id',
        'title',
        'icon',
        'sort_order',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class
        );
    }

    public function getTitleAttribute(): ?string { return $this->translate('title'); }
}
