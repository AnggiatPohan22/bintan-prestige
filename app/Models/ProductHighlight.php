<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductHighlight extends Model
{
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
}
