@extends('layouts.admin')

@section('content')

@php
    $statCards = [
        [
            'label'      => 'Total Products',
            'value'      => $productCount ?? 0,
            'hint'       => 'All product records in catalog',
            'icon'       => 'fa-map-location-dot',
            'iconBg'     => 'bg-violet-900/20',
            'iconColor'  => 'text-violet-400',
            'route'      => route('admin.products.index'),
        ],
        [
            'label'      => 'Published',
            'value'      => $publishedProductCount ?? 0,
            'hint'       => 'Visible to public visitors',
            'icon'       => 'fa-circle-check',
            'iconBg'     => 'bg-emerald-900/20',
            'iconColor'  => 'text-emerald-400',
            'route'      => route('admin.products.index'),
        ],
        [
            'label'      => 'Draft',
            'value'      => $draftProductCount ?? 0,
            'hint'       => 'Hidden from frontend',
            'icon'       => 'fa-pencil',
            'iconBg'     => 'bg-amber-900/20',
            'iconColor'  => 'text-amber-400',
            'route'      => route('admin.products.index'),
        ],
        [
            'label'      => 'Categories',
            'value'      => $categoryCount ?? 0,
            'hint'       => 'Package groups for filtering',
            'icon'       => 'fa-tag',
            'iconBg'     => 'bg-cyan-900/20',
            'iconColor'  => 'text-cyan-400',
            'route'      => route('admin.categories.index'),
        ],
        [
            'label'      => 'Destinations',
            'value'      => $destinationCount ?? 0,
            'hint'       => 'Locations linked to products',
            'icon'       => 'fa-location-dot',
            'iconBg'     => 'bg-violet-900/20',
            'iconColor'  => 'text-violet-400',
            'route'      => route('admin.destinations.index'),
        ],
        [
            'label'      => 'FAQs',
            'value'      => $faqCount ?? 0,
            'hint'       => 'Global FAQ items',
            'icon'       => 'fa-circle-question',
            'iconBg'     => 'bg-yellow-900/20',
            'iconColor'  => 'text-yellow-400',
            'route'      => null,
        ],
        [
            'label'      => 'Page Sections',
            'value'      => $pageSectionCount ?? 0,
            'hint'       => 'Editable frontend sections',
            'icon'       => 'fa-layer-group',
            'iconBg'     => 'bg-cyan-900/20',
            'iconColor'  => 'text-cyan-400',
            'route'      => route('admin.page-sections.index'),
        ],
        [
            'label'      => 'Product Images',
            'value'      => $productImageCount ?? 0,
            'hint'       => 'Gallery images for products',
            'icon'       => 'fa-images',
            'iconBg'     => 'bg-rose-900/20',
            'iconColor'  => 'text-rose-400',
            'route'      => route('admin.media.index'),
        ],
    ];

    $quickActions = [
        [
            'label' => 'Create Product',
            'route' => route('admin.products.create'),
            'icon'  => 'fa-plus',
        ],
        [
            'label' => 'Manage Products',
            'route' => route('admin.products.index'),
            'icon'  => 'fa-boxes-stacked',
        ],
        [
            'label' => 'Pages',
            'route' => route('admin.pages.index'),
            'icon'  => 'fa-file-lines',
        ],
        [
            'label' => 'Page Sections',
            'route' => route('admin.page-sections.index'),
            'icon'  => 'fa-layer-group',
        ],
        [
            'label' => 'Media Library',
            'route' => route('admin.media.index'),
            'icon'  => 'fa-images',
        ],
        [
            'label' => 'Global Assets',
            'route' => route('admin.settings.global-assets.edit'),
            'icon'  => 'fa-gear',
        ],
    ];
@endphp

<div class="admin-page">

    {{-- Page Header --}}
    <section class="admin-page-header">
        <p class="admin-dashboard-kicker">Admin Overview</p>
        <h1 class="admin-page-title">Dashboard</h1>
        <p class="admin-page-subtitle">
            Monitor product content, page sections, media readiness, and quick admin workflows from one clean overview.
        </p>
    </section>

    {{-- KPI Stat Cards --}}
    <section class="admin-stat-grid" aria-label="Dashboard statistics">
        @foreach($statCards as $card)
            <article class="admin-stat-card">
                <div class="admin-stat-card__top">
                    <div class="min-w-0">
                        <h2 class="admin-stat-card__label">{{ $card['label'] }}</h2>
                        <p class="admin-stat-card__value">{{ number_format($card['value']) }}</p>
                    </div>
                    <span class="admin-stat-card__icon {{ $card['iconBg'] }} {{ $card['iconColor'] }}" aria-hidden="true">
                        <i class="fa-solid {{ $card['icon'] }}"></i>
                    </span>
                </div>
                <p class="admin-stat-card__hint">{{ $card['hint'] }}</p>
                @if($card['route'])
                    <a href="{{ $card['route'] }}"
                       class="mt-3 inline-flex items-center gap-1 text-xs font-semibold transition"
                       style="color: var(--admin-text-muted)"
                       onmouseover="this.style.color='var(--admin-primary)'"
                       onmouseout="this.style.color='var(--admin-text-muted)'">
                        Lihat semua <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                @endif
            </article>
        @endforeach
    </section>

    {{-- Content Grid: Quick Actions + Recent Products --}}
    <section class="admin-content-grid">

        {{-- Quick Actions --}}
        <div class="admin-card">
            <div class="admin-card-body">
                <div class="admin-panel__header">
                    <div>
                        <h2 class="admin-panel__title">Quick Access</h2>
                        <p class="admin-panel__description">Jump into common workflows without searching the sidebar.</p>
                    </div>
                </div>
                <div class="admin-quick-actions">
                    @foreach($quickActions as $action)
                        <a href="{{ $action['route'] }}" class="admin-quick-action">
                            <span class="admin-quick-action__icon" aria-hidden="true">
                                <i class="fa-solid {{ $action['icon'] }}"></i>
                            </span>
                            <span>{{ $action['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Recent Products --}}
        <div class="admin-card admin-panel--wide">
            <div class="admin-card-body">
                <div class="admin-panel__header">
                    <div>
                        <h2 class="admin-panel__title">Recent Products</h2>
                        <p class="admin-panel__description">Latest product records added or updated in the catalog.</p>
                    </div>
                    <a href="{{ route('admin.products.index') }}" class="admin-btn-secondary shrink-0 px-4 py-2 text-sm">
                        View All
                    </a>
                </div>

                @if(($recentProducts ?? collect())->count())
                    <div class="admin-recent-list">
                        @foreach($recentProducts as $product)
                            <article class="admin-recent-item">
                                <div class="min-w-0">
                                    <h3 class="admin-recent-item__title">{{ $product->name }}</h3>
                                    <p class="admin-recent-item__meta">
                                        {{ $product->category?->name ?? 'No category' }}
                                        <span aria-hidden="true"> · </span>
                                        {{ $product->destination?->name ?? 'No destination' }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <span class="{{ $product->status === 'published' ? 'admin-badge-success' : 'admin-badge-warning' }}">
                                        {{ ucfirst($product->status ?? 'draft') }}
                                    </span>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="admin-btn-secondary px-3 py-1.5 text-xs">
                                        Edit
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="admin-empty-state">
                        <div class="admin-empty-state__icon">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                        <h3 class="admin-empty-state__title">No products yet</h3>
                        <p class="admin-empty-state__description">Create your first product to get started.</p>
                        <a href="{{ route('admin.products.create') }}" class="admin-btn-primary">
                            <i class="fa-solid fa-plus"></i>
                            Create Product
                        </a>
                    </div>
                @endif
            </div>
        </div>

    </section>

</div>

@endsection
