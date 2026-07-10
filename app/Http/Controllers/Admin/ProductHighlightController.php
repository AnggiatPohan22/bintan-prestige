<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Product;
use App\Models\ProductHighlight;

use Illuminate\Http\Request;

use App\Services\ProductHighlightService;
use App\Support\ChildTranslations;

class ProductHighlightController extends Controller
{
    private const TRANSLATABLE_FIELDS = ['title'];

    public function __construct(
        protected ProductHighlightService
        $productHighlightService
    ) {}

    public function store(
        Request $request,
        Product $product
    ) {

        $validated = $request->validate(array_merge([
            'title' => ['required', 'max:255'],
            'icon' => ['nullable', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ], ChildTranslations::rulesFor(self::TRANSLATABLE_FIELDS, 255)));

        $highlight = $this->productHighlightService
            ->create(
                $product,
                $validated
            );

        ChildTranslations::syncFromRequest($request, $highlight, self::TRANSLATABLE_FIELDS);

        return redirect()->back()->withFragment('highlights-section')->with(
            'success',
            'Highlight added successfully.'
        );
    }

    public function update(
        Request $request,
        ProductHighlight $highlight
    ) {

        $validated = $request->validate(array_merge([
            'title' => ['required', 'max:255'],
            'icon' => ['nullable', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ], ChildTranslations::rulesFor(self::TRANSLATABLE_FIELDS, 255)));

        $this->productHighlightService
            ->update(
                $highlight,
                $validated
            );

        ChildTranslations::syncFromRequest($request, $highlight, self::TRANSLATABLE_FIELDS);

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
