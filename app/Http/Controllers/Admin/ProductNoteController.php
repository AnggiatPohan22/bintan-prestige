<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductNote;
use App\Services\ProductNoteService;
use Illuminate\Http\Request;

class ProductNoteController extends Controller
{
    public function __construct(
        protected ProductNoteService $productNoteService
    ) {}

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'title' => ['nullable', 'max:255'],
            'description' => ['required'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $this->productNoteService
            ->create($product, $validated);

        return redirect()->back()->withFragment('notes-section')->with(
            'success',
            'Note added successfully.'
        );
    }

    public function update(Request $request, ProductNote $note)
    {
        $validated = $request->validate([
            'title' => ['nullable', 'max:255'],
            'description' => ['required'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $this->productNoteService
            ->update($note, $validated);

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
