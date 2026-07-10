<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductNote;
use App\Services\ProductNoteService;
use App\Support\ChildTranslations;
use Illuminate\Http\Request;

class ProductNoteController extends Controller
{
    private const TRANSLATABLE_FIELDS = ['title', 'description'];

    public function __construct(
        protected ProductNoteService $productNoteService
    ) {}

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate(array_merge([
            'title' => ['nullable', 'max:255'],
            'description' => ['required'],
            'sort_order' => ['nullable', 'integer'],
        ], ChildTranslations::rulesFor(self::TRANSLATABLE_FIELDS)));

        $note = $this->productNoteService
            ->create($product, $validated);

        ChildTranslations::syncFromRequest($request, $note, self::TRANSLATABLE_FIELDS);

        return redirect()->back()->withFragment('notes-section')->with(
            'success',
            'Note added successfully.'
        );
    }

    public function update(Request $request, ProductNote $note)
    {
        $validated = $request->validate(array_merge([
            'title' => ['nullable', 'max:255'],
            'description' => ['required'],
            'sort_order' => ['nullable', 'integer'],
        ], ChildTranslations::rulesFor(self::TRANSLATABLE_FIELDS)));

        $this->productNoteService
            ->update($note, $validated);

        ChildTranslations::syncFromRequest($request, $note, self::TRANSLATABLE_FIELDS);

        return redirect()->back()->withFragment('notes-section')->with(
            'success',
            'Note updated successfully.'
        );
    }

    public function destroy(ProductNote $note)
    {
        $this->productNoteService
            ->delete($note);

        return redirect()->back()->withFragment('notes-section')->with(
            'success',
            'Note deleted successfully.'
        );
    }
}
