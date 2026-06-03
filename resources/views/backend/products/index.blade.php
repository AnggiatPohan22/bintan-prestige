@extends('layouts.admin')

@section('content')

<div class="space-y-6">

    {{-- Header --}}
    <div class="rounded-xl bg-white p-4 shadow sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    Products
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Manage tour, taxi, and activity products from one dashboard.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form class="w-full sm:w-72">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search products..."
                        class="form-input"
                    >
                </form>

                <a
                    href="{{ route('admin.products.create') }}"
                    class="btn-primary w-full sm:w-auto"
                >
                    Create Product
                </a>
            </div>

        </div>
    </div>

    {{-- Mobile Cards --}}
    <div class="grid gap-4 lg:hidden">
        @forelse($products as $product)

            @php
                $hasCoreFeatures =
                    ($product->included_features_count ?? 0) > 0
                    && ($product->excluded_features_count ?? 0) > 0;
            @endphp

            <div class="rounded-xl bg-white p-4 shadow">
                <div class="flex gap-4">
                    @if($product->main_image_url)
                        <img
                            src="{{ $product->main_image_url }}"
                            class="h-20 w-24 shrink-0 rounded-lg border object-cover"
                            alt="{{ $product->name }}"
                        >
                    @else
                        <div class="flex h-20 w-24 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-400">
                            No Image
                        </div>
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="truncate font-semibold text-slate-800">
                                    {{ $product->name }}
                                </h2>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $product->category->name }} / {{ $product->destination->name }}
                                </p>
                            </div>

                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold
                                {{ $product->status === 'published'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-slate-100 text-slate-600' }}">
                                {{ ucfirst($product->status) }}
                            </span>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div class="rounded-lg bg-slate-50 p-2">
                                <span class="block text-slate-400">IDR</span>
                                <span class="font-semibold text-slate-700">
                                    Rp {{ number_format($product->idr_price ?? 0, 0, ',', '.') }}
                                </span>
                            </div>

                            <div class="rounded-lg bg-slate-50 p-2">
                                <span class="block text-slate-400">SGD</span>
                                <span class="font-semibold text-slate-700">
                                    SGD {{ number_format($product->sgd_price ?? 0, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2 text-xs text-slate-600">
                    <div class="rounded-lg border border-slate-200 p-2">
                        Detail: {{ $product->features_count }} features,
                        {{ $product->itineraries_count }} itinerary
                    </div>

                    <div class="rounded-lg border p-2
                        {{ $hasCoreFeatures
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                            : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                        {{ $hasCoreFeatures
                            ? 'Included & excluded ready'
                            : 'Need included/excluded' }}
                    </div>
                </div>

                <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <a
                        href="{{ route('admin.products.edit', $product) }}"
                        class="btn-secondary flex-1"
                    >
                        Edit
                    </a>

                    <form
                        method="POST"
                        action="{{ route('admin.products.destroy', $product) }}"
                        class="flex-1"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            onclick="return confirm('Delete this product?')"
                            class="w-full rounded-xl bg-red-600 px-5 py-3 text-sm font-medium text-white transition hover:bg-red-700"
                        >
                            Delete
                        </button>
                    </form>
                </div>
            </div>

        @empty

            <div class="rounded-xl bg-white p-8 text-center text-sm text-slate-500 shadow">
                No products found.
            </div>

        @endforelse
    </div>

    {{-- Desktop Table --}}
    <div class="hidden rounded-xl bg-white p-6 shadow lg:block">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1120px]">
                <thead>
                    <tr class="border-b text-left">
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Product</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Category</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Destination</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Price</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Content</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Featured</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-4 text-right text-sm font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($products as $product)

                        @php
                            $hasCoreFeatures =
                                ($product->included_features_count ?? 0) > 0
                                && ($product->excluded_features_count ?? 0) > 0;
                        @endphp

                        <tr class="border-b hover:bg-slate-50">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    @if($product->main_image_url)
                                        <img
                                            src="{{ $product->main_image_url }}"
                                            class="h-16 w-20 rounded-lg border object-cover"
                                            alt="{{ $product->name }}"
                                        >
                                    @else
                                        <div class="flex h-16 w-20 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-400">
                                            No Image
                                        </div>
                                    @endif

                                    <div class="min-w-0">
                                        <div class="max-w-[260px] truncate font-semibold text-slate-800">
                                            {{ $product->name }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $product->images_count }} gallery image(s)
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4 text-sm font-medium text-slate-600">
                                {{ $product->category->name }}
                            </td>

                            <td class="px-4 py-4 text-sm font-medium text-slate-600">
                                {{ $product->destination->name }}
                            </td>

                            <td class="px-4 py-4 text-sm text-slate-600">
                                <div class="font-semibold">
                                    Rp {{ number_format($product->idr_price ?? 0, 0, ',', '.') }}
                                </div>

                                <div class="text-xs text-slate-400">
                                    SGD {{ number_format($product->sgd_price ?? 0, 0, ',', '.') }}
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2 text-xs">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">
                                        {{ $product->features_count }} features
                                    </span>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">
                                        {{ $product->itineraries_count }} itinerary
                                    </span>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">
                                        {{ $product->faqs_count }} FAQ
                                    </span>
                                    <span class="rounded-full px-2.5 py-1
                                        {{ $hasCoreFeatures
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-amber-100 text-amber-700' }}">
                                        {{ $hasCoreFeatures ? 'Ready' : 'Need core features' }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <button
                                    onclick="openConfirmModal(
                                        '{{ route('admin.products.toggle-featured',$product) }}',
                                        'Change featured status?'
                                    )"
                                    class="flex items-center gap-3"
                                >
                                    <div class="relative inline-flex h-5 w-14 items-center rounded-full border shadow-sm transition-all duration-300
                                        {{ $product->is_featured
                                            ? 'border-emerald-500 bg-emerald-500'
                                            : 'border-slate-300 bg-slate-200' }}">
                                        <span class="inline-block h-3 w-3 rounded-full bg-white shadow transition
                                            {{ $product->is_featured
                                                ? 'translate-x-8'
                                                : 'translate-x-1' }}">
                                        </span>
                                    </div>
                                </button>
                            </td>

                            <td class="px-4 py-4">
                                <button
                                    onclick="openConfirmModal(
                                        '{{ route('admin.products.toggle-status',$product) }}',
                                        'Change publish status?'
                                    )"
                                    class="flex items-center gap-3"
                                >
                                    <div class="relative inline-flex h-5 w-14 items-center rounded-full border shadow-sm transition-all duration-300
                                        {{ $product->status === 'published'
                                            ? 'border-emerald-500 bg-emerald-500'
                                            : 'border-slate-300 bg-slate-200' }}">
                                        <span class="inline-block h-3 w-3 rounded-full bg-white shadow transition
                                            {{ $product->status === 'published'
                                                ? 'translate-x-8'
                                                : 'translate-x-1' }}">
                                        </span>
                                    </div>
                                </button>
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-3">
                                    <a
                                        href="{{ route('admin.products.edit', $product) }}"
                                        class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.products.destroy', $product) }}"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            onclick="return confirm('Delete this product?')"
                                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="8"
                                class="py-10 text-center text-sm text-slate-500"
                            >
                                No products found.
                            </td>
                        </tr>

                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $products->links() }}
    </div>

</div>

@endsection
