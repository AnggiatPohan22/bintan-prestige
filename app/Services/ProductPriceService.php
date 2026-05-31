<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPrice;

class ProductPriceService
{
    public function sync(
        Product $product,
        ?string $idrPrice,
        ?string $sgdPrice
    ): void {

        /*
        |--------------------------------------------------------------------------
        | IDR
        |--------------------------------------------------------------------------
        */

        ProductPrice::query()
            ->updateOrCreate(
                [
                    'product_id' =>
                        $product->id,

                    'currency' =>
                        'IDR',
                ],
                [
                    'price' =>
                        $idrPrice,
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | SGD
        |--------------------------------------------------------------------------
        */

        ProductPrice::query()
            ->updateOrCreate(
                [
                    'product_id' =>
                        $product->id,

                    'currency' =>
                        'SGD',
                ],
                [
                    'price' =>
                        $sgdPrice,
                ]
            );
    }
}