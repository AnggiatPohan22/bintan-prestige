<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductItinerary;
use App\Services\ProductItineraryService;
use Illuminate\Http\Request;

class ProductItineraryController extends Controller
{
    public function __construct(
        protected ProductItineraryService $productItineraryService
    ) {}

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'time' => ['nullable', 'max:50'],
            'title' => ['required', 'max:255'],
            'description' => ['nullable'],
            'start_time' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $this->productItineraryService
            ->create($product, $validated);

        return redirect()->back()->withFragment('itineraries-section')->with(
            'success',
            'Itinerary added successfully.'
        );
    }

    public function update(Request $request, ProductItinerary $itinerary)
    {
        $validated = $request->validate([
            'time' => ['nullable', 'max:50'],
            'title' => ['required', 'max:255'],
            'description' => ['nullable'],
            'start_time' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $this->productItineraryService
            ->update($itinerary, $validated);

        return redirect()->back()->withFragment('itineraries-section')->with(
            'success',
            'Itinerary updated successfully.'
        );
    }

    public function destroy(ProductItinerary $itinerary)
    {
        $this->productItineraryService
            ->delete($itinerary);

        return redirect()->back()->withFragment('itineraries-section')->with(
            'success',
            'Itinerary deleted successfully.'
        );
    }
}
