<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        return view('backend.dashboard', [
            'productCount' => Product::count(),
            'categoryCount' => Category::count(),
            'destinationCount' => Destination::count(),
            'bookingCount' => Booking::count(),
        ]);
    }
}