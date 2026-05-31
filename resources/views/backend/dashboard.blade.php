@extends('layouts.admin')

@section('content')

<div>

    <h1 class="text-3xl font-bold mb-4">
        Dashboard
    </h1>

    <div class="grid grid-cols-4 gap-6">

        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="text-gray-500">
                Products
            </h2>

            <p class="text-3xl font-bold">
                {{ \App\Models\Product::count() }}
            </p>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="text-gray-500">
                Categories
            </h2>

            <p class="text-3xl font-bold">
                {{ \App\Models\Category::count() }}
            </p>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="text-gray-500">
                Destinations
            </h2>

            <p class="text-3xl font-bold">
                {{ \App\Models\Destination::count() }}
            </p>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="text-gray-500">
                Bookings
            </h2>

            <p class="text-3xl font-bold">
                {{ \App\Models\Booking::count() }}
            </p>
        </div>

    </div>

</div>

@endsection