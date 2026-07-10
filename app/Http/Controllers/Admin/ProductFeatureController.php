<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductFeature;
use App\Services\ProductFeatureService;
use App\Support\ChildTranslations;
use Illuminate\Http\Request;

class ProductFeatureController extends Controller
{
    private const TRANSLATABLE_FIELDS = ['value'];

    public function __construct(
        protected ProductFeatureService $productFeatureService
    ) {}

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate(array_merge([
            'label' => ['required', 'in:included,excluded,optional,addon,important'],
            'value' => ['required', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ], ChildTranslations::rulesFor(self::TRANSLATABLE_FIELDS, 255)));

        $feature = $this->productFeatureService
            ->create($product, $validated);

        ChildTranslations::syncFromRequest($request, $feature, self::TRANSLATABLE_FIELDS);

        return redirect()->back()->withFragment('features-section')->with(
            'success',
            'Feature added successfully.'
        );
    }

    public function update(Request $request, ProductFeature $feature)
    {
        $validated = $request->validate(array_merge([
            'label' => ['required', 'in:included,excluded,optional,addon,important'],
            'value' => ['required', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ], ChildTranslations::rulesFor(self::TRANSLATABLE_FIELDS, 255)));

        $this->productFeatureService
            ->update($feature, $validated);

        ChildTranslations::syncFromRequest($request, $feature, self::TRANSLATABLE_FIELDS);

        return redirect()->back()->withFragment('features-section')->with(
            'success',
            'Feature updated successfully.'
        );
    }

    public function destroy(ProductFeature $feature)
    {
        $this->productFeatureService
            ->delete($feature);

        return redirect()->back()->withFragment('features-section')->with(
            'success',
            'Feature deleted successfully.'
        );
    }
}
