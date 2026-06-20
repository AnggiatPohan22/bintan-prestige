<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    public const CURRENCY_IDR = 'IDR';
    public const CURRENCY_SGD = 'SGD';
    public const SUPPORTED_CURRENCIES = [
        self::CURRENCY_IDR,
        self::CURRENCY_SGD,
    ];
    public const UNIQUE_PRODUCT_CURRENCY_INDEX =
        'product_prices_product_id_currency_unique';

    protected $fillable = [
        'product_id',
        'currency',
        'price',
    ];

    public static function isSupportedCurrency(string $currency): bool
    {
        return in_array(
            strtoupper($currency),
            self::SUPPORTED_CURRENCIES,
            true
        );
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class
        );
    }
}
