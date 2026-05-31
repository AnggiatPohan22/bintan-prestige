<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductItinerary;

class ProductItineraryService
{
    public function create(Product $product, array $data): ProductItinerary
    {
        return $product->itineraries()->create([
            'time' => $data['time'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_time' => $data['start_time'] ?? 0,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function update(ProductItinerary $itinerary, array $data): ProductItinerary
    {
        $itinerary->update([
            'time' => $data['time'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_time' => $data['start_time'] ?? 0,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return $itinerary->fresh();
    }

    public function delete(ProductItinerary $itinerary): bool
    {
        return $itinerary->delete();
    }
}