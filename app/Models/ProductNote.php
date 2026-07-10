<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductNote extends Model
{
    use Translatable;

    /** @var list<string> Phase 7 (B6.1) — sidecar-translated note copy. */
    protected array $translatable = ['title', 'description'];

    protected $fillable = [
        'product_id',
        'title',
        'description',
        'sort_order',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getTitleAttribute(): ?string       { return $this->translate('title'); }
    public function getDescriptionAttribute(): ?string { return $this->translate('description'); }
}
