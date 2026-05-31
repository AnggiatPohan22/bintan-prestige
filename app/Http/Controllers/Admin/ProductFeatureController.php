<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductFeature;
use App\Services\ProductFeatureService;
use Illuminate\Http\Request;

class ProductFeatureController extends Controller
{
    public function __construct(
        protected ProductFeatureService $productFeatureService
    ) {}

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'label' => [
                'required',
                'in:included,excluded,optional,addon,important',
            ],
            'value' => [
                'required',
                'max:255',
            ],
            'sort_order' => [
                'nullable',
                'integer',
            ],
        ]);

        $this->productFeatureService
            ->create($product, $validated);

        return redirect()->back()->withFragment('features-section')->with(
            'success',
            'Feature added successfully.'
        );
    }

    public function update(Request $request, ProductFeature $feature)
    {
        $validated = $request->validate([
            'label' => [
                'required',
                'in:included,excluded,optional,addon,important',
            ],
            'value' => [
                'required',
                'max:255',
            ],
            'sort_order' => [
                'nullable',
                'integer',
            ],
        ]);

        $this->productFeatureService
            ->update($feature, $validated);

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
