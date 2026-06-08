@extends('layouts.admin')

@section('content')

<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="admin-page-title">
                    Products
                </h1>

                <p class="admin-page-subtitle">
                    Manage tour, taxi, and activity products from one dashboard.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <a
                    href="{{ route('admin.products.create') }}"
                    class="admin-btn-primary w-full sm:w-auto"
                >
                    Create Product
                </a>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-lg font-extrabold text-slate-900">
                        Product List
                    </h2>

                    <span class="admin-badge-info">
                        {{ $products->total() }} item(s)
                    </span>
                </div>

                <span class="text-xs font-bold uppercase tracking-wide text-slate-400">
                    Showing products
                </span>
            </div>
        </div>

        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.products.index') }}" class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(0,1.2fr)_minmax(180px,0.8fr)_minmax(180px,0.8fr)_minmax(150px,0.55fr)_auto] xl:items-end">
                    <div>
                        <label for="product-search" class="admin-form-label">
                            Search
                        </label>
                        <input
                            id="product-search"
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search products..."
                            class="admin-input"
                        >
                    </div>

                    <div>
                        <label for="product-category-filter" class="admin-form-label">
                            Category
                        </label>
                        <select
                            id="product-category-filter"
                            name="category_id"
                            class="admin-select"
                        >
                            <option value="">All categories</option>
                            @foreach($categories as $category)
                                <option
                                    value="{{ $category->id }}"
                                    @selected((string) request('category_id') === (string) $category->id)
                                >
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="product-destination-filter" class="admin-form-label">
                            Destination
                        </label>
                        <select
                            id="product-destination-filter"
                            name="destination_id"
                            class="admin-select"
                        >
                            <option value="">All destinations</option>
                            @foreach($destinations as $destination)
                                <option
                                    value="{{ $destination->id }}"
                                    @selected((string) request('destination_id') === (string) $destination->id)
                                >
                                    {{ $destination->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        @php
                            $rawStatusOptions = collect($statuses)
                                ->map(fn ($status) => (string) $status)
                                ->filter(fn ($status) => $status !== '')
                                ->unique()
                                ->values();
                            $publishedStatusValue = $rawStatusOptions->first(fn ($status) => $status === 'published')
                                ?? $rawStatusOptions->first(fn ($status) => $status === '1');
                            $draftStatusValue = $rawStatusOptions->first(fn ($status) => $status === 'draft')
                                ?? $rawStatusOptions->first(fn ($status) => ! in_array($status, ['published', '1'], true));
                        @endphp

                        <label for="product-status-filter" class="admin-form-label">
                            Status
                        </label>
                        <select
                            id="product-status-filter"
                            name="status"
                            class="admin-select"
                        >
                            <option value="">All status</option>
                            @if($publishedStatusValue !== null)
                                <option
                                    value="{{ $publishedStatusValue }}"
                                    @selected((string) request('status') === $publishedStatusValue)
                                >
                                    Published
                                </option>
                            @endif
                            @if($draftStatusValue !== null)
                                <option
                                    value="{{ $draftStatusValue }}"
                                    @selected((string) request('status') === $draftStatusValue)
                                >
                                    Draft
                                </option>
                            @endif
                        </select>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row xl:pb-0">
                        <button
                            type="submit"
                            class="admin-btn-primary px-4 py-3"
                        >
                            Apply
                        </button>

                        <a
                            href="{{ route('admin.products.index') }}"
                            class="admin-btn-secondary px-4 py-3"
                        >
                            Reset
                        </a>
                    </div>
                </div>
            </form>

            <div class="space-y-3">
                @forelse($products as $product)
                    @php
                        $hasCoreFeatures =
                            ($product->included_features_count ?? 0) > 0
                            && ($product->excluded_features_count ?? 0) > 0;
                        $productStatusValue = (string) $product->status;
                        $isPublished = in_array($productStatusValue, ['published', '1'], true);
                        $productStatusLabel = $isPublished ? 'Published' : 'Draft';
                    @endphp

                    <article class="overflow-hidden rounded-2xl border border-slate-100 bg-white p-4 shadow-sm ring-1 ring-slate-100 transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-lg">
                        <div class="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1.18fr)_minmax(225px,0.48fr)_minmax(222px,0.52fr)_minmax(280px,0.72fr)] xl:items-center">
                            <div class="flex min-w-0 gap-4">
                                @if($product->thumbnail)
                                    <img
                                        src="{{ asset('storage/'.$product->thumbnail) }}"
                                        class="aspect-[7/6] h-20 w-24 shrink-0 rounded-xl border border-slate-200 object-cover sm:h-24 sm:w-28"
                                        alt="{{ $product->name }}"
                                    >
                                @else
                                    <div class="flex aspect-[7/6] h-20 w-24 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-100 text-xs font-bold text-slate-400 sm:h-24 sm:w-28">
                                        No Image
                                    </div>
                                @endif

                                <div class="min-w-0 flex-1 self-center">
                                    <h3 class="break-words text-base font-extrabold leading-6 text-slate-950 sm:text-lg">
                                        {{ $product->name }}
                                    </h3>

                                    <div class="mt-1 break-words text-[11px] font-black uppercase tracking-wide text-indigo-500">
                                        {{ $product->category->name }} &bull; {{ $product->destination->name }}
                                    </div>

                                    <div class="mt-2 text-xs font-semibold text-slate-400">
                                        {{ $product->images_count }} gallery image(s)
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-bold text-slate-500 sm:hidden">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1">
                                            {{ $product->category->name }}
                                        </span>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1">
                                            {{ $product->destination->name }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="min-w-[220px] rounded-2xl border border-indigo-100 bg-gradient-to-br from-white to-indigo-50/60 px-5 py-3 text-center shadow-sm">
                                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-300">
                                    Current Rate
                                </span>
                                <div class="mt-1 whitespace-nowrap text-xl font-black leading-7 text-indigo-700">
                                    Rp {{ number_format($product->idr_price ?? 0, 0, ',', '.') }}
                                </div>
                                <div class="mt-1 text-xs font-bold text-slate-400">
                                    SGD {{ number_format($product->sgd_price ?? 0, 0, ',', '.') }}
                                </div>
                            </div>

                            <div class="min-w-0 overflow-x-auto py-1">
                                <div class="flex w-max items-center gap-3 text-center text-xs xl:w-auto xl:justify-start">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white/80 font-black text-slate-600 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-indigo-50" title="{{ $product->features_count }} feature(s)" aria-label="{{ $product->features_count }} feature(s)">
                                        <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                                    </span>
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white/80 font-black text-slate-600 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-indigo-50" title="{{ $product->itineraries_count }} itinerary item(s)" aria-label="{{ $product->itineraries_count }} itinerary item(s)">
                                        <i class="fa-solid fa-route" aria-hidden="true"></i>
                                    </span>
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white/80 font-black text-slate-600 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-indigo-50" title="{{ $product->faqs_count }} FAQ item(s)" aria-label="{{ $product->faqs_count }} FAQ item(s)">
                                        <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                                    </span>
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-amber-200 text-xs font-black shadow-sm transition-all duration-200 hover:-translate-y-0.5 {{ $hasCoreFeatures ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}" title="{{ $hasCoreFeatures ? 'Core features ready' : 'Need included and excluded features' }}" aria-label="{{ $hasCoreFeatures ? 'Core features ready' : 'Need included and excluded features' }}">
                                        @if($hasCoreFeatures)
                                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                                        @else
                                            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <div class="grid min-w-0 gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 p-3 sm:grid-cols-[minmax(0,1fr)_auto] xl:items-center">
                                <div class="min-w-0 self-center">
                                    <button
                                        onclick="openConfirmModal(
                                            '{{ route('admin.products.toggle-status',$product) }}',
                                            'Change publish status?'
                                        )"
                                        class="inline-flex min-h-10 max-w-full items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold transition-all duration-200 {{ $isPublished ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 hover:bg-emerald-100' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-100 hover:bg-amber-100' }}"
                                    >
                                        <span class="h-2 w-2 shrink-0 rounded-full {{ $isPublished ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                        <span class="truncate">
                                            {{ $productStatusLabel }}
                                        </span>
                                    </button>
                                </div>

                                <div class="flex justify-end gap-2 sm:flex-col xl:flex-row">
                                    <a
                                        href="{{ route('admin.products.edit', $product) }}"
                                        class="flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white/80 text-indigo-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-indigo-50"
                                        title="Edit product"
                                        aria-label="Edit product"
                                    >
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
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
                                            class="flex h-11 w-11 items-center justify-center rounded-full border border-red-100 bg-white/80 text-red-600 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-red-50"
                                            title="Delete product"
                                            aria-label="Delete product"
                                        >
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="admin-empty-state">
                        <div class="text-base font-extrabold text-slate-700">
                            No products found
                        </div>
                        <p class="mt-2 text-sm font-medium text-slate-500">
                            Try adjusting your filters or search keyword.
                        </p>
                        <a
                            href="{{ route('admin.products.index') }}"
                            class="admin-btn-secondary mt-4"
                        >
                            Reset Filter
                        </a>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>

@endsection
