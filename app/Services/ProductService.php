<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductService
{
    public function __construct(
        protected ProductPriceService $productPriceService,
        protected ProductImageService $productImageService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Store Product
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ): Product {

        $thumbnail =
            null;

        if (
            $request->hasFile(
                'thumbnail'
            )
        ) {

            $thumbnail =
                $this
                ->productImageService
                ->replaceThumbnail(
                    new Product(),
                    $request->file(
                        'thumbnail'
                    )
                );
        }

        $product =
            Product::create([

                'category_id' =>
                    $request->category_id,

                'destination_id' =>
                    $request->destination_id,

                'name' =>
                    $request->name,

                'slug' =>
                    $request->slug,

                'short_description' =>
                    $request
                    ->short_description,

                'description' =>
                    $request
                    ->description,

                'meeting_point' =>
                    $request
                    ->meeting_point,

                'duration' =>
                    $request
                    ->duration,

                'whatsapp_number' =>
                    $request
                    ->whatsapp_number,

                'thumbnail' =>
                    $thumbnail,

                'is_featured' =>
                    $request->boolean(
                        'is_featured'
                    ),

                'status' =>
                    $request->status,

                    
                'pickup_available' => $request->boolean('pickup_available'),
                'pickup_type' => $request->pickup_type,
                'pickup_note' => $request->pickup_note,

                'cta_title' => $request->cta_title,
                'cta_description' => $request->cta_description,
                'cta_button_text' => $request->cta_button_text,

                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'meta_keywords' => $request->meta_keywords,
                'canonical_url' => $request->canonical_url,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Sync Prices
        |--------------------------------------------------------------------------
        */

        $this->productPriceService
            ->sync(
                $product,
                $request->idr_price,
                $request->sgd_price
            );

        /*
        |--------------------------------------------------------------------------
        | Upload Gallery
        |--------------------------------------------------------------------------
        */

        $this->productImageService
            ->uploadGallery(
                $product,
                $request->file(
                    'gallery',
                    []
                )
            );

        return $product;
    }

    /*
    |--------------------------------------------------------------------------
    | Update Product
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Product $product
    ): Product {

        $thumbnail =
            $this
            ->productImageService
            ->replaceThumbnail(
                $product,
                $request->file(
                    'thumbnail'
                )
            );

        $product->update([

            'category_id' =>
                $request->category_id,

            'destination_id' =>
                $request->destination_id,

            'name' =>
                $request->name,

            'slug' =>
                $request->slug,

            'short_description' =>
                $request
                ->short_description,

            'description' =>
                $request
                ->description,

            'meeting_point' =>
                $request
                ->meeting_point,

            'duration' =>
                $request
                ->duration,

            'whatsapp_number' =>
                $request
                ->whatsapp_number,

            'thumbnail' =>
                $thumbnail,

            'is_featured' =>
                $request->boolean(
                    'is_featured'
                ),

            'status' =>
                $request->status,

            'pickup_available' => $request->boolean('pickup_available'),
            'pickup_type' => $request->pickup_type,
            'pickup_note' => $request->pickup_note,

            'cta_title' => $request->cta_title,
            'cta_description' => $request->cta_description,
            'cta_button_text' => $request->cta_button_text,

            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
            'canonical_url' => $request->canonical_url,
        ]);

        $this->productPriceService
            ->sync(
                $product,
                $request->idr_price,
                $request->sgd_price
            );

        $this->productImageService
            ->uploadGallery(
                $product,
                $request->file(
                    'gallery',
                    []
                )
            );

        return $product
            ->refresh();
    }
}
