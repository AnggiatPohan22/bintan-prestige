<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductFaq;

class ProductFaqService
{
    public function create(Product $product, array $data): ProductFaq
    {
        return $product->faqs()->create([
            'question' => $data['question'],
            'answer' => $data['answer'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function update(ProductFaq $faq, array $data): ProductFaq
    {
        $faq->update([
            'question' => $data['question'],
            'answer' => $data['answer'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return $faq->fresh();
    }

    public function delete(ProductFaq $faq): bool
    {
        return $faq->delete();
    }
}