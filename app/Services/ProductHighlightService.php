<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductHighlight;

class ProductHighlightService
{
    public function create(
        Product $product,
        array $data
    ): ProductHighlight {

        return $product
            ->highlights()
            ->create([
                'title' => $data['title'],
                'icon' => $data['icon'] ?? null,
                'sort_order' =>
                    $data['sort_order'] ?? 0,
            ]);
    }

    public function update(
        ProductHighlight $highlight,
        array $data
    ): ProductHighlight {

        $highlight->update([
            'title' => $data['title'],
            'icon' => $data['icon'] ?? null,
            'sort_order' =>
                $data['sort_order'] ?? 0,
        ]);

        return $highlight->fresh();
    }

    public function delete(
        ProductHighlight $highlight
    ): bool {

        return $highlight->delete();
    }
}