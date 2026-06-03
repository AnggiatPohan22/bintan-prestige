<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductImageService
{
    public function __construct(
        protected ImageOptimizationService $imageService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Upload Gallery
    |--------------------------------------------------------------------------
    */

    public function uploadGallery(
        Product $product,
        array $images = []
    ): void {

        $nextSortOrder =
            ((int) $product->images()
                ->max('sort_order')) + 1;

        foreach (
            $images
            as $image
        ) {

            $path =
                $this->imageService
                ->upload($image);

            ProductImage::create([
                'product_id' =>
                    $product->id,

                'image' =>
                    $path,

                'sort_order' =>
                    $nextSortOrder++,
            ]);
        }

        $this->autoThumbnail(
            $product
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Replace Thumbnail
    |--------------------------------------------------------------------------
    */

    public function replaceThumbnail(
        Product $product,
        ?UploadedFile $file
    ): string|null {

        $thumbnail =
            $product->thumbnail;

        if (! $file) {
            return $thumbnail;
        }

        $thumbnailIsGalleryImage =
            $product->exists
            && $product->thumbnail
            && $product->images()
                ->where(
                    'image',
                    $product->thumbnail
                )
                ->exists();

        if (
            $product->thumbnail
            && ! $thumbnailIsGalleryImage
        ) {

            $this->deleteIfLocalProductImage(
                $product->thumbnail
            );
        }

        return $this->imageService
            ->upload($file);
    }

    /*
    |--------------------------------------------------------------------------
    | Auto Thumbnail
    |--------------------------------------------------------------------------
    */

    public function autoThumbnail(
        Product $product
    ): void {

        if (
            $product->thumbnail
        ) {
            return;
        }

        $firstImage =
            $product->images()
                ->first();

        if (! $firstImage) {
            return;
        }

        $product->update([
            'thumbnail' =>
                $firstImage->image
        ]);
    }

    public function deleteIfLocalProductImage(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'products/')) {
            return;
        }

        Storage::disk('public')
            ->delete($path);
    }
}
