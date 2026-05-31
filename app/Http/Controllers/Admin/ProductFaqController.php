<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Services\ProductFaqService;
use Illuminate\Http\Request;

class ProductFaqController extends Controller
{
    public function __construct(
        protected ProductFaqService $productFaqService
    ) {}

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'question' => [
                'required',
                'max:255',
            ],
            'answer' => [
                'required',
            ],
            'sort_order' => [
                'nullable',
                'integer',
            ],
        ]);

        $this->productFaqService
            ->create($product, $validated);

        return redirect()->back()->withFragment('faqs-section')->with(
            'success',
            'FAQ added successfully.'
        );
    }

    public function update(Request $request, ProductFaq $faq)
    {
        $validated = $request->validate([
            'question' => [
                'required',
                'max:255',
            ],
            'answer' => [
                'required',
            ],
            'sort_order' => [
                'nullable',
                'integer',
            ],
        ]);

        $this->productFaqService
            ->update($faq, $validated);

        return redirect()->back()->withFragment('faqs-section')->with(
            'success',
            'FAQ updated successfully.'
        );
    }

    public function destroy(ProductFaq $faq)
    {
        $this->productFaqService
            ->delete($faq);

        return redirect()->back()->withFragment('faqs-section')->with(
            'success',
            'FAQ deleted successfully.'
        );
    }
}
