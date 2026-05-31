<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductFeature;

class ProductFeatureService
{
    public function create(Product $product, array $data): ProductFeature
    {
        return $product->features()->create([
            'label' => $data['label'],
            'value' => $data['value'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function update(ProductFeature $feature, array $data): ProductFeature
    {
        $feature->update([
            'label' => $data['label'],
            'value' => $data['value'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return $feature->fresh();
    }

    public function delete(ProductFeature $feature): bool
    {
        return $feature->delete();
    }
}