<?php

namespace Tests;

use App\Models\Product;
use App\Support\ProductDetailDisplayState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function productDetailDisplayState(Product $product, array $globalViewData = []): array
    {
        $product->loadMissing([
            'category',
            'destination',
            'prices',
            'images' => fn ($query) => $query
                ->orderBy('sort_order')
                ->orderBy('id'),
            'highlights',
            'features',
            'faqs',
            'itineraries',
            'notes',
        ]);

        return ProductDetailDisplayState::make($product, [
            'siteAssets' => collect(),
            'defaultMediaSettings' => [],
            'businessIdentity' => [],
            'contactInformation' => [],
            'bookingCtaSettings' => [],
            ...$globalViewData,
        ]);
    }
}
