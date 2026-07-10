<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductItinerary;
use App\Services\ProductItineraryService;
use App\Support\ChildTranslations;
use Illuminate\Http\Request;

class ProductItineraryController extends Controller
{
    // `start_time` is a real clock value → shared across locales.
    private const TRANSLATABLE_FIELDS = ['time', 'title', 'description'];

    public function __construct(
        protected ProductItineraryService $productItineraryService
    ) {}

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate(array_merge([
            'time' => ['nullable', 'max:50'],
            'title' => ['required', 'max:255'],
            'description' => ['nullable'],
            'start_time' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer'],
        ], ChildTranslations::rulesFor(self::TRANSLATABLE_FIELDS)));

        $itinerary = $this->productItineraryService
            ->create($product, $validated);

        ChildTranslations::syncFromRequest($request, $itinerary, self::TRANSLATABLE_FIELDS);

        return redirect()->back()->withFragment('itineraries-section')->with(
            'success',
            'Itinerary added successfully.'
        );
    }

    public function update(Request $request, ProductItinerary $itinerary)
    {
        $validated = $request->validate(array_merge([
            'time' => ['nullable', 'max:50'],
            'title' => ['required', 'max:255'],
            'description' => ['nullable'],
            'start_time' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer'],
        ], ChildTranslations::rulesFor(self::TRANSLATABLE_FIELDS)));

        $this->productItineraryService
            ->update($itinerary, $validated);

        ChildTranslations::syncFromRequest($request, $itinerary, self::TRANSLATABLE_FIELDS);

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
