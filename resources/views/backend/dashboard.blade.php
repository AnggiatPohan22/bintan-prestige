@extends('layouts.admin')

@section('content')

@php
    $statCards = [
        [
            'label' => 'Total Products',
            'value' => $productCount ?? 0,
            'hint' => 'All product records in the catalog.',
            'icon' => 'TP',
        ],
        [
            'label' => 'Published Products',
            'value' => $publishedProductCount ?? 0,
            'hint' => 'Products visible to public visitors.',
            'icon' => 'PB',
        ],
        [
            'label' => 'Draft Products',
            'value' => $draftProductCount ?? 0,
            'hint' => 'Products still hidden from frontend.',
            'icon' => 'DR',
        ],
        [
            'label' => 'Total Categories',
            'value' => $categoryCount ?? 0,
            'hint' => 'Package groups used for filtering.',
            'icon' => 'CT',
        ],
        [
            'label' => 'Total Destinations',
            'value' => $destinationCount ?? 0,
            'hint' => 'Destination records connected to products.',
            'icon' => 'DS',
        ],
        [
            'label' => 'Total FAQs',
            'value' => $faqCount ?? 0,
            'hint' => 'Global FAQ items managed by admin.',
            'icon' => 'FQ',
        ],
        [
            'label' => 'Total Page Sections',
            'value' => $pageSectionCount ?? 0,
            'hint' => 'Editable frontend section records.',
            'icon' => 'PS',
        ],
        [
            'label' => 'Total Product Images',
            'value' => $productImageCount ?? 0,
            'hint' => 'Gallery images uploaded for products.',
            'icon' => 'IM',
        ],
    ];

    $quickActions = [
        [
            'label' => 'Create Product',
            'route' => route('admin.products.create'),
            'icon' => 'CP',
        ],
        [
            'label' => 'Manage Products',
            'route' => route('admin.products.index'),
            'icon' => 'MP',
        ],
        [
            'label' => 'Page Sections',
            'route' => route('admin.page-sections.index'),
            'icon' => 'PS',
        ],
        [
            'label' => 'Global Assets',
            'route' => route('admin.settings.global-assets.edit'),
            'icon' => 'GA',
        ],
        [
            'label' => 'Create Category',
            'route' => route('admin.categories.create'),
            'icon' => 'CC',
        ],
        [
            'label' => 'Create Destination',
            'route' => route('admin.destinations.create'),
            'icon' => 'CD',
        ],
    ];
@endphp

<div class="admin-dashboard-shell">
    <section class="admin-dashboard-hero">
        <p class="admin-dashboard-kicker">
            Admin Overview
        </p>

        <h1 class="admin-dashboard-title">
            Dashboard
        </h1>

        <p class="admin-dashboard-description">
            Monitor product content, global page sections, media readiness, and quick admin workflows from one clean overview.
        </p>
    </section>

    <section class="admin-stat-grid" aria-label="Dashboard statistics">
        @foreach($statCards as $card)
            <article class="admin-stat-card">
                <div class="admin-stat-card__top">
                    <div>
                        <h2 class="admin-stat-card__label">
                            {{ $card['label'] }}
                        </h2>

                        <p class="admin-stat-card__value">
                            {{ number_format($card['value']) }}
                        </p>
                    </div>

                    <span class="admin-stat-card__icon" aria-hidden="true">
                        {{ $card['icon'] }}
                    </span>
                </div>

                <p class="admin-stat-card__hint">
                    {{ $card['hint'] }}
                </p>
            </article>
        @endforeach
    </section>

    <section class="admin-content-grid">
        <div class="admin-panel">
            <div class="admin-panel__header">
                <div>
                    <h2 class="admin-panel__title">
                        Quick Actions
                    </h2>

                    <p class="admin-panel__description">
                        Jump into common admin workflows without searching the sidebar.
                    </p>
                </div>
            </div>

            <div class="admin-quick-actions">
                @foreach($quickActions as $action)
                    <a href="{{ $action['route'] }}" class="admin-quick-action">
                        <span class="admin-quick-action__icon" aria-hidden="true">
                            {{ $action['icon'] }}
                        </span>

                        <span>
                            {{ $action['label'] }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="admin-panel admin-panel--wide">
            <div class="admin-panel__header">
                <div>
                    <h2 class="admin-panel__title">
                        Recent Products
                    </h2>

                    <p class="admin-panel__description">
                        Latest product records added or updated in the catalog.
                    </p>
                </div>

                <a href="{{ route('admin.products.index') }}" class="btn-secondary px-4 py-2 text-sm">
                    View All
                </a>
            </div>

            @if(($recentProducts ?? collect())->count())
                <div class="admin-recent-list">
                    @foreach($recentProducts as $product)
                        <article class="admin-recent-item">
                            <div class="min-w-0">
                                <h3 class="admin-recent-item__title">
                                    {{ $product->name }}
                                </h3>

                                <p class="admin-recent-item__meta">
                                    {{ $product->category?->name ?? 'No category' }}
                                    <span aria-hidden="true">/</span>
                                    {{ $product->destination?->name ?? 'No destination' }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-3">
                                <span class="admin-badge {{ $product->status === 'published' ? 'admin-badge--published' : 'admin-badge--draft' }}">
                                    {{ ucfirst($product->status ?? 'draft') }}
                                </span>

                                <a href="{{ route('admin.products.edit', $product) }}" class="btn-secondary px-4 py-2 text-sm">
                                    Edit
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center">
                    <p class="text-sm font-semibold text-slate-500">
                        No products found yet.
                    </p>

                    <a href="{{ route('admin.products.create') }}" class="btn-primary mt-4 px-4 py-2 text-sm">
                        Create Product
                    </a>
                </div>
            @endif
        </div>
    </section>
</div>

@endsection
