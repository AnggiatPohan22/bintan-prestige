<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductItinerary extends Model
{
    protected $fillable = [
        'product_id',
        'time',
        'title',
        'description',
        'start_time',
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
