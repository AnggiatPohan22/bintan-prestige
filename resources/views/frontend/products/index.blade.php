@extends('layouts.frontend')

@section('content')

<section class="py-12">
    <div class="max-w-7xl mx-auto px-6">

        <div class="mb-10">
            <h1 class="text-3xl md:text-4xl font-bold text-slate-900">
                Tour, Taxi & Activity
            </h1>

            <p class="mt-3 text-slate-600 max-w-2xl">
                Explore selected travel experiences, transfers, and activities.
            </p>
        </div>

        @if($products->count())

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                @foreach($products as $product)

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition">

                        <div class="aspect-[16/10] bg-slate-100 overflow-hidden">

                            @if($product->thumbnail_url)
                                <img
                                    src="{{ $product->thumbnail_url }}"
                                    alt="{{ $product->name }}"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                >
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-400">
                                    No Image
                                </div>
                            @endif

                        </div>

                        <div class="p-5">

                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-xs px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 font-medium">
                                    {{ $product->category?->name }}
                                </span>

                                <span class="text-xs px-3 py-1 rounded-full bg-slate-100 text-slate-600">
                                    {{ $product->destination?->name }}
                                </span>
                            </div>

                            <h2 class="text-lg font-bold text-slate-900 line-clamp-2">
                                {{ $product->name }}
                            </h2>

                            <p class="mt-2 text-sm text-slate-600 line-clamp-2">
                                {{ $product->short_description }}
                            </p>

                            <div class="mt-4">
                                <p class="text-xs text-slate-400">
                                    Start from
                                </p>

                                <p class="text-xl font-bold text-slate-900">
                                    Rp {{ number_format($product->idr_price ?? 0, 0, ',', '.') }}
                                </p>

                                @if($product->sgd_price)
                                    <p class="text-sm text-slate-500">
                                        SGD {{ number_format($product->sgd_price, 0) }}
                                    </p>
                                @endif
                            </div>

                            <div class="mt-5 flex items-center justify-between">

                                <a
                                    href="#"
                                    class="btn-primary"
                                >
                                    View Details
                                </a>

                                @if($product->highlights->count())
                                    <span class="text-xs text-slate-400">
                                        {{ $product->highlights->count() }} highlights
                                    </span>
                                @endif

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

            <div class="mt-10">
                {{ $products->links() }}
            </div>

        @else

            <div class="bg-white border rounded-2xl p-10 text-center">
                <h3 class="text-lg font-bold">
                    No products available
                </h3>

                <p class="mt-2 text-slate-500">
                    Published products will appear here.
                </p>
            </div>

        @endif

    </div>
</section>

@endsection