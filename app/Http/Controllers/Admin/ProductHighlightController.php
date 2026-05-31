<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Product;
use App\Models\ProductHighlight;

use Illuminate\Http\Request;

use App\Services\ProductHighlightService;

class ProductHighlightController extends Controller
{
    public function __construct(
        protected ProductHighlightService
        $productHighlightService
    ) {}

    public function store(
        Request $request,
        Product $product
    ) {

        $validated = $request->validate([

            'title' => [
                'required',
                'max:255'
            ],

            'icon' => [
                'nullable',
                'max:255'
            ],

            'sort_order' => [
                'nullable',
                'integer'
            ],
        ]);

        $this->productHighlightService
            ->create(
                $product,
                $validated
            );

        return redirect()->back()->withFragment('highlights-section')->with(
            'success',
            'Highlight added successfully.'
        );
    }

    public function update(
        Request $request,
        ProductHighlight $highlight
    ) {

        $validated = $request->validate([

            'title' => [
                'required',
                'max:255'
            ],

            'icon' => [
                'nullable',
                'max:255'
            ],

            'sort_order' => [
                'nullable',
                'integer'
            ],
        ]);

        $this->productHighlightService
            ->update(
                $highlight,
                $validated
            );

        return redirect()->back()->withFragment('highlights-section')->with(
            'success',
            'Highlight updated successfully.'
        );
    }

    public function destroy(
        ProductHighlight $highlight
    ) {

        $this->productHighlightService
            ->delete($highlight);

        return redirect()->back()->withFragment('highlights-section')->with(
            'success',
            'Highlight deleted successfully.'
        );
    }
}
