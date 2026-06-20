@extends('layouts.admin')

@section('content')
<div class="admin-page">

    {{-- Header --}}
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="admin-page-title">Plugins</h1>
                <p class="admin-page-subtitle">
                    Manage CMS plugins. Activate plugins to extend functionality without changing core code.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.plugins.scan') }}">
                @csrf
                <button type="submit" class="admin-btn-secondary">
                    <i class="fa-solid fa-rotate mr-1.5" aria-hidden="true"></i>
                    Scan for Plugins
                </button>
            </form>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="admin-alert-success mb-4">
            <i class="fa-solid fa-circle-check mr-1.5" aria-hidden="true"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('info'))
        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
            <i class="fa-solid fa-circle-info mr-1.5" aria-hidden="true"></i>
            {{ session('info') }}
        </div>
    @endif

    @if(session('error'))
        <div class="admin-alert-danger mb-4">
            <i class="fa-solid fa-triangle-exclamation mr-1.5" aria-hidden="true"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Plugin list --}}
    @if($plugins->isEmpty())
        <div class="admin-card">
            <div class="admin-card-body py-16 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                    <i class="fa-solid fa-puzzle-piece text-2xl text-slate-400" aria-hidden="true"></i>
                </div>
                <h3 class="mb-1 font-semibold text-slate-700">No plugins found</h3>
                <p class="mb-4 text-sm text-slate-400">
                    Place plugin folders inside <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">app/Plugins/</code> and run Scan.
                </p>
                <form method="POST" action="{{ route('admin.plugins.scan') }}">
                    @csrf
                    <button type="submit" class="admin-btn-primary">
                        <i class="fa-solid fa-rotate mr-1.5" aria-hidden="true"></i>
                        Scan Now
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-extrabold text-slate-900">Installed Plugins</h2>
                    <span class="admin-badge-info">{{ $plugins->count() }} plugin(s)</span>
                </div>
            </div>

            <div class="admin-card-body p-0">
                <div class="divide-y divide-slate-100">
                    @foreach($plugins as $plugin)
                        <div
                            class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-start sm:justify-between"
                            x-data="{ deactivateOpen: false, uninstallOpen: false }"
                        >
                            {{-- Plugin info --}}
                            <div class="min-w-0 flex-1">
                                <div class="mb-1 flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-slate-900">{{ $plugin->name }}</span>
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500">v{{ $plugin->version }}</span>

                                    @if($plugin->is_active)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                            <i class="fa-solid fa-circle text-[6px]" aria-hidden="true"></i>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">
                                            <i class="fa-solid fa-circle text-[6px]" aria-hidden="true"></i>
                                            Inactive
                                        </span>
                                    @endif
                                </div>

                                @if($plugin->description)
                                    <p class="mb-1 text-sm text-slate-500">{{ $plugin->description }}</p>
                                @endif

                                @if($plugin->author)
                                    <p class="text-xs text-slate-400">
                                        <i class="fa-solid fa-user mr-1" aria-hidden="true"></i>
                                        {{ $plugin->author }}
                                    </p>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                {{-- View details --}}
                                <a
                                    href="{{ route('admin.plugins.show', $plugin) }}"
                                    class="admin-btn-secondary py-1.5 text-xs"
                                >
                                    <i class="fa-solid fa-eye mr-1" aria-hidden="true"></i>
                                    Details
                                </a>

                                @if($plugin->is_active)
                                    {{-- Deactivate button → confirm modal --}}
                                    <button
                                        type="button"
                                        class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition-colors"
                                        x-on:click="deactivateOpen = true"
                                    >
                                        <i class="fa-solid fa-toggle-on mr-1" aria-hidden="true"></i>
                                        Deactivate
                                    </button>
                                @else
                                    {{-- Activate --}}
                                    <form method="POST" action="{{ route('admin.plugins.activate', $plugin) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition-colors"
                                        >
                                            <i class="fa-solid fa-toggle-off mr-1" aria-hidden="true"></i>
                                            Activate
                                        </button>
                                    </form>
                                @endif

                                {{-- Uninstall --}}
                                <button
                                    type="button"
                                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100 transition-colors"
                                    x-on:click="uninstallOpen = true"
                                >
                                    <i class="fa-solid fa-trash mr-1" aria-hidden="true"></i>
                                    Uninstall
                                </button>

                                {{-- Deactivate confirm modal --}}
                                <div
                                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                                    x-cloak
                                    x-show="deactivateOpen"
                                    x-transition.opacity
                                >
                                    <div
                                        class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl"
                                        x-on:click.outside="deactivateOpen = false"
                                    >
                                        <h3 class="mb-2 text-base font-bold text-slate-900">Deactivate Plugin</h3>
                                        <p class="mb-5 text-sm text-slate-500">
                                            Deactivate <strong>{{ $plugin->name }}</strong>? Its functionality will be disabled immediately.
                                        </p>
                                        <div class="flex justify-end gap-2">
                                            <button
                                                type="button"
                                                class="admin-btn-secondary py-1.5 text-sm"
                                                x-on:click="deactivateOpen = false"
                                            >
                                                Cancel
                                            </button>
                                            <form method="POST" action="{{ route('admin.plugins.deactivate', $plugin) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rounded-lg bg-amber-500 px-4 py-1.5 text-sm font-semibold text-white hover:bg-amber-600 transition-colors">
                                                    Deactivate
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                {{-- Uninstall confirm modal --}}
                                <div
                                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                                    x-cloak
                                    x-show="uninstallOpen"
                                    x-transition.opacity
                                >
                                    <div
                                        class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl"
                                        x-on:click.outside="uninstallOpen = false"
                                    >
                                        <h3 class="mb-2 text-base font-bold text-slate-900">Uninstall Plugin</h3>
                                        <p class="mb-5 text-sm text-slate-500">
                                            Permanently remove <strong>{{ $plugin->name }}</strong> from the registry?
                                            @if($plugin->is_active)
                                                <br><span class="mt-1 block font-semibold text-red-600">You must deactivate it first.</span>
                                            @else
                                                This cannot be undone.
                                            @endif
                                        </p>
                                        <div class="flex justify-end gap-2">
                                            <button
                                                type="button"
                                                class="admin-btn-secondary py-1.5 text-sm"
                                                x-on:click="uninstallOpen = false"
                                            >
                                                Cancel
                                            </button>
                                            <form method="POST" action="{{ route('admin.plugins.destroy', $plugin) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg bg-red-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-red-700 transition-colors">
                                                    Uninstall
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
