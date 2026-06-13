<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPrice;
use InvalidArgumentException;

class ProductPriceService
{
    public function sync(
        Product $product,
        ?string $idrPrice,
        ?string $sgdPrice
    ): void {

        $this->syncCurrency(
            $product,
            ProductPrice::CURRENCY_IDR,
            $idrPrice
        );

        $this->syncCurrency(
            $product,
            ProductPrice::CURRENCY_SGD,
            $sgdPrice
        );
    }

    public function syncCurrency(
        Product $product,
        string $currency,
        int|float|string|null $price
    ): void {
        $currency = strtoupper($currency);

        if (! ProductPrice::isSupportedCurrency($currency)) {
            throw new InvalidArgumentException(
                'Unsupported product price currency.'
            );
        }

        if (
            $price === null
            || $price === ''
            || ! is_numeric($price)
            || (float) $price < 0
        ) {
            throw new InvalidArgumentException(
                'Product price must be a non-negative numeric value.'
            );
        }

        ProductPrice::query()
            ->updateOrCreate(
                [
                    'product_id' =>
                        $product->id,

                    'currency' =>
                        $currency,
                ],
                [
                    'price' =>
                        $price,
                ]
            );
    }
}
