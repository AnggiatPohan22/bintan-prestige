<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductNote;

class ProductNoteService
{
    public function create(Product $product, array $data): ProductNote
    {
        return $product->notes()->create([
            'title' => $data['title'] ?? null,
            'description' => $data['description'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function update(ProductNote $note, array $data): ProductNote
    {
        $note->update([
            'title' => $data['title'] ?? null,
            'description' => $data['description'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return $note->fresh();
    }

    public function delete(ProductNote $note): bool
    {
        return $note->delete();
    }
}