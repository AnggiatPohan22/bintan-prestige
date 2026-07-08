@extends('layouts.admin')

@section('content')

<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="mb-1 flex items-center gap-2 text-sm text-admin-secondary">
                    <a href="{{ route('admin.taxonomies.index') }}" class="hover:text-indigo-600">Taxonomies</a>
                    <span>/</span>
                    <span>{{ $taxonomy->label_plural }}</span>
                </div>
                <h1 class="admin-page-title">{{ $taxonomy->label_plural }}</h1>
                <p class="admin-page-subtitle">
                    {{ $taxonomy->is_hierarchical ? 'Hierarchical taxonomy' : 'Flat taxonomy' }}
                    · <span class="font-mono">{{ $taxonomy->slug }}</span>
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.taxonomies.edit', $taxonomy) }}" class="admin-btn-secondary w-full sm:w-auto">
                    Edit Taxonomy
                </a>
                <a href="{{ route('admin.taxonomies.terms.create', $taxonomy) }}" class="admin-btn-primary w-full sm:w-auto">
                    New {{ $taxonomy->label_singular }}
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="admin-alert-success mb-6">{{ session('success') }}</div>
    @endif

    {{-- Terms list --}}
    @if($terms->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 px-6 py-12 text-center">
            <p class="text-sm text-admin-secondary">
                No {{ strtolower($taxonomy->label_plural) }} yet.
                <a href="{{ route('admin.taxonomies.terms.create', $taxonomy) }}" class="text-indigo-600 hover:underline">
                    Add the first one.
                </a>
            </p>
        </div>
    @else
        <div class="admin-card overflow-hidden">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        @if($taxonomy->is_hierarchical)
                            <th>Parent</th>
                        @endif
                        <th>Sort</th>
                        <th class="w-40">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($terms as $term)
                        <tr>
                            <td>
                                <div class="font-medium text-admin-primary">{{ $term->name }}</div>
                                @if($term->description)
                                    <div class="text-xs text-admin-secondary">{{ Str::limit($term->description, 60) }}</div>
                                @endif
                            </td>
                            <td class="font-mono text-sm text-admin-secondary">{{ $term->slug }}</td>
                            @if($taxonomy->is_hierarchical)
                                <td class="text-sm text-admin-secondary">
                                    {{ $term->parent_id ? ($terms->firstWhere('id', $term->parent_id)?->name ?? '—') : '—' }}
                                </td>
                            @endif
                            <td class="text-sm text-admin-secondary">{{ $term->sort_order }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.taxonomies.terms.edit', [$taxonomy, $term]) }}"
                                       class="admin-btn-secondary py-1 text-xs">Edit</a>
                                    <form action="{{ route('admin.taxonomies.terms.destroy', [$taxonomy, $term]) }}"
                                          method="POST" onsubmit="return confirm('Move this term to trash?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="admin-btn-danger py-1 text-xs">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Trash --}}
    @if($trashed->isNotEmpty())
        <div class="mt-10">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-400">Trash</h2>
            <div class="admin-card overflow-hidden">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th class="w-40">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trashed as $term)
                            <tr class="opacity-60">
                                <td>{{ $term->name }}</td>
                                <td class="font-mono text-sm">{{ $term->slug }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <form action="{{ route('admin.taxonomies.terms.restore', [$taxonomy, $term->id]) }}"
                                              method="POST">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="admin-btn-secondary py-1 text-xs">Restore</button>
                                        </form>
                                        <form action="{{ route('admin.taxonomies.terms.force-delete', [$taxonomy, $term->id]) }}"
                                              method="POST"
                                              onsubmit="return confirm('Permanently delete this term?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="admin-btn-danger py-1 text-xs">Delete Forever</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@endsection
