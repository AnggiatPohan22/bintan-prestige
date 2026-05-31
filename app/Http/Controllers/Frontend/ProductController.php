<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::query()
            ->published()
            ->frontendReady()
            ->latest()
            ->paginate(12);

        return view(
            'frontend.products.index',
            compact('products')
        );
    }
}