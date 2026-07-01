@extends('layouts.admin')

@section('content')

<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="admin-page-title">Taxonomies</h1>
                <p class="admin-page-subtitle">Define classification systems for your content (categories, tags, etc.).</p>
            </div>
            <a href="{{ route('admin.taxonomies.create') }}" class="admin-btn-primary w-full sm:w-auto">
                New Taxonomy
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="admin-alert-success mb-6">{{ session('success') }}</div>
    @endif

    {{-- Active taxonomies --}}
    @if($active->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 px-6 py-12 text-center">
            <p class="text-sm text-admin-secondary">No taxonomies yet. Create one to start classifying your content.</p>
        </div>
    @else
        <div class="admin-card overflow-hidden">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Slug</th>
                        <th>Label</th>
                        <th>Type</th>
                        <th>Applies To</th>
                        <th class="w-48">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($active as $taxonomy)
                        <tr>
                            <td class="font-mono text-sm">{{ $taxonomy->slug }}</td>
                            <td>
                                <div class="font-medium text-admin-primary">{{ $taxonomy->label_singular }}</div>
                                <div class="text-xs text-admin-secondary">Plural: {{ $taxonomy->label_plural }}</div>
                            </td>
                            <td>
                                @if($taxonomy->is_hierarchical)
                                    <span class="admin-badge-info">Hierarchical</span>
                                @else
                                    <span class="admin-badge-secondary">Flat</span>
                                @endif
                            </td>
                            <td class="text-sm text-admin-secondary">
                                {{ $taxonomy->content_type_ids === null ? 'All content types' : count($taxonomy->content_type_ids).' type(s)' }}
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.taxonomies.terms.index', $taxonomy) }}"
                                       class="admin-btn-secondary py-1 text-xs">Terms</a>
                                    <a href="{{ route('admin.taxonomies.edit', $taxonomy) }}"
                                       class="admin-btn-secondary py-1 text-xs">Edit</a>
                                    <form action="{{ route('admin.taxonomies.destroy', $taxonomy) }}" method="POST"
                                          onsubmit="return confirm('Move this taxonomy to trash?')">
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
                            <th>Slug</th>
                            <th>Label</th>
                            <th class="w-48">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trashed as $taxonomy)
                            <tr class="opacity-60">
                                <td class="font-mono text-sm">{{ $taxonomy->slug }}</td>
                                <td>{{ $taxonomy->label_singular }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <form action="{{ route('admin.taxonomies.restore', $taxonomy->id) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="admin-btn-secondary py-1 text-xs">Restore</button>
                                        </form>
                                        <form action="{{ route('admin.taxonomies.force-delete', $taxonomy->id) }}" method="POST"
                                              onsubmit="return confirm('Permanently delete this taxonomy and all its terms?')">
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
