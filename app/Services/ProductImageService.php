<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

class ProductImageService
{
    /**
     * Append gallery images from Media Library paths (Phase 6.1 Stage 2).
     * The files already live in the library, so no re-upload happens — we just
     * record ProductImage rows pointing at them, continuing the sort order.
     *
     * @param  array<int, mixed>  $paths  raw request input; non-strings are ignored
     */
    public function attachGalleryPaths(Product $product, array $paths): void
    {
        $paths = array_values(array_filter(
            array_map(fn ($path) => is_string($path) ? trim($path) : '', $paths),
            fn (string $path) => $path !== '',
        ));

        if ($paths === []) {
            $this->autoThumbnail($product);

            return;
        }

        $sortOrder = (int) $product->images()->max('sort_order');

        foreach ($paths as $path) {
            $sortOrder++;

            ProductImage::create([
                'product_id' => $product->id,
                'image' => $path,
                'sort_order' => $sortOrder,
            ]);
        }

        $this->autoThumbnail($product);
    }

    /**
     * Resolve the thumbnail path from a picker selection. Keeps the current
     * thumbnail when nothing new is picked. When the thumbnail changes, a
     * previous *module-owned* upload (products/…) is cleaned up; Media Library
     * files (media/…) and gallery images are left intact.
     */
    public function resolveThumbnail(Product $product, ?string $pickedPath): ?string
    {
        $current = $product->thumbnail;
        $picked = trim((string) $pickedPath);

        if ($picked === '' || $picked === $current) {
            return $current;
        }

        if ($current !== null && ! $this->isGalleryImage($product, $current)) {
            $this->deleteIfModuleOwned($current);
        }

        return $picked;
    }

    /**
     * If the product has no thumbnail, adopt the first gallery image.
     */
    public function autoThumbnail(Product $product): void
    {
        if ($product->thumbnail) {
            return;
        }

        $firstImage = $product->images()
            ->orderBy('sort_order')
            ->first();

        if (! $firstImage) {
            return;
        }

        $product->update([
            'thumbnail' => $firstImage->image,
        ]);
    }

    /**
     * Delete a file only if it is a legacy module-owned upload (products/…).
     * Media Library assets (media/…) are never deleted here — they belong to
     * the library and are usage-tracked + delete-guarded there.
     */
    public function deleteIfModuleOwned(?string $path): void
    {
        if ($path === null || ! str_starts_with($path, 'products/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function isGalleryImage(Product $product, string $path): bool
    {
        return $product->exists
            && $product->images()->where('image', $path)->exists();
    }
}
