<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Services\ProductFaqService;
use App\Support\ChildTranslations;
use Illuminate\Http\Request;

class ProductFaqController extends Controller
{
    private const TRANSLATABLE_FIELDS = ['question', 'answer'];

    public function __construct(
        protected ProductFaqService $productFaqService
    ) {}

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate(array_merge([
            'question' => ['required', 'max:255'],
            'answer' => ['required'],
            'sort_order' => ['nullable', 'integer'],
        ], ChildTranslations::rulesFor(self::TRANSLATABLE_FIELDS)));

        $faq = $this->productFaqService
            ->create($product, $validated);

        ChildTranslations::syncFromRequest($request, $faq, self::TRANSLATABLE_FIELDS);

        return redirect()->back()->withFragment('faqs-section')->with(
            'success',
            'FAQ added successfully.'
        );
    }

    public function update(Request $request, ProductFaq $faq)
    {
        $validated = $request->validate(array_merge([
            'question' => ['required', 'max:255'],
            'answer' => ['required'],
            'sort_order' => ['nullable', 'integer'],
        ], ChildTranslations::rulesFor(self::TRANSLATABLE_FIELDS)));

        $this->productFaqService
            ->update($faq, $validated);

        ChildTranslations::syncFromRequest($request, $faq, self::TRANSLATABLE_FIELDS);

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
