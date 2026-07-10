<?php

namespace App\Services;

use App\Models\Product;
use App\Support\Locales;
use Illuminate\Http\Request;

class ProductService
{
    /**
     * Product copy fields translated per locale via the translations sidecar
     * (Phase 7 — B6). Base columns keep the default locale; prices, images,
     * slugs, relations, and status stay shared.
     *
     * @var list<string>
     */
    private const TRANSLATABLE_FIELDS = [
        'name',
        'short_description',
        'description',
        'meeting_point',
        'duration',
        'pickup_note',
        'cta_title',
        'cta_description',
        'cta_button_text',
        'meta_title',
        'meta_description',
    ];
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
            $this
            ->productImageService
            ->resolveThumbnail(
                new Product(),
                $request->input('thumbnail')
            );

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
            ->attachGalleryPaths(
                $product,
                $request->input(
                    'gallery',
                    []
                )
            );

        $this->syncTranslations($request, $product);

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
            ->resolveThumbnail(
                $product,
                $request->input('thumbnail')
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
            ->attachGalleryPaths(
                $product,
                $request->input(
                    'gallery',
                    []
                )
            );

        $this->syncTranslations($request, $product);

        return $product
            ->refresh();
    }

    /**
     * Persist per-locale copy translations for the product. Only non-default
     * active locales; empty clears the sidecar row (fallback resumes).
     */
    private function syncTranslations(Request $request, Product $product): void
    {
        $translations = (array) $request->input('translations', []);

        foreach (Locales::nonDefaultActive() as $locale) {
            foreach (self::TRANSLATABLE_FIELDS as $field) {
                $value = $translations[$locale][$field] ?? null;
                $product->setTranslation($field, $locale, is_string($value) ? trim($value) : null);
            }
        }
    }
}
