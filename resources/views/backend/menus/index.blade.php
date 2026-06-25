@extends('layouts.admin')

@section('content')

@php
    $locationMeta = [
        'header'         => ['title' => 'Header Menu',   'icon' => 'fa-window-maximize', 'hint' => 'Main navigation bar'],
        'footer_quick'   => ['title' => 'Footer — Quick', 'icon' => 'fa-link',           'hint' => 'Primary footer links'],
        'footer_utility' => ['title' => 'Footer — Utility','icon' => 'fa-ellipsis',       'hint' => 'Secondary footer links'],
    ];
@endphp

<div class="admin-page">
    <div class="mb-6">
        <h1 class="text-lg font-extrabold text-admin-secondary">Menus</h1>
        <p class="mt-0.5 text-sm text-admin-secondary">
            The single place to manage header &amp; footer links and dropdowns. Appearance (colors, CTA,
            footer layout) lives in
            <a href="{{ route('admin.settings.global-assets.edit') }}" class="text-violet-400 hover:underline">Global Assets</a>.
        </p>
    </div>

    @if($menus->isEmpty())
        <div class="admin-empty-state py-10">
            <p class="font-medium text-admin-secondary">No menus found.</p>
            <p class="mt-1 text-sm text-admin-secondary">Run the menu seeder to create the header and footer menus.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($menus as $menu)
                @php
                    $meta = $locationMeta[$menu->location] ?? ['title' => $menu->name, 'icon' => 'fa-bars', 'hint' => ''];
                    $preview = $menu->rootItems->take(4);
                @endphp

                <a href="{{ route('admin.menus.edit', $menu) }}"
                   class="group flex flex-col rounded-2xl border border-admin bg-admin-card p-5 transition hover:-translate-y-0.5 hover:border-violet-500/50">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <span class="grid h-11 w-11 place-items-center rounded-xl bg-violet-900/20 text-violet-400">
                                <i class="fa-solid {{ $meta['icon'] }}"></i>
                            </span>
                            <div>
                                <h2 class="font-bold text-admin-secondary">{{ $meta['title'] }}</h2>
                                <p class="text-xs text-admin-secondary">{{ $meta['hint'] }}</p>
                            </div>
                        </div>
                        <span class="{{ $menu->is_active ? 'admin-badge-success' : 'admin-badge-warning' }}">
                            {{ $menu->is_active ? 'Active' : 'Off' }}
                        </span>
                    </div>

                    {{-- Link chips preview --}}
                    <div class="mt-4 min-h-[2.5rem] flex flex-wrap gap-1.5">
                        @forelse($preview as $item)
                            <span class="rounded-full bg-admin-card px-2.5 py-1 text-xs font-medium text-admin-secondary">{{ $item->label }}</span>
                        @empty
                            <span class="text-xs text-admin-secondary">No items yet.</span>
                        @endforelse
                        @if($menu->items_count > $preview->count())
                            <span class="rounded-full px-2 py-1 text-xs font-medium text-admin-secondary">+{{ $menu->items_count - $preview->count() }} more</span>
                        @endif
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-admin/50 pt-3">
                        <span class="text-xs text-admin-secondary">{{ $menu->items_count }} {{ \Illuminate\Support\Str::plural('item', $menu->items_count) }}</span>
                        <span class="inline-flex items-center gap-1 text-sm font-semibold text-violet-400 group-hover:gap-2 transition-all">
                            Manage <i class="fa-solid fa-arrow-right text-xs"></i>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>

@endsection
