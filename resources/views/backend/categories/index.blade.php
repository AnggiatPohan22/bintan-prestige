@extends('layouts.admin')

@section('content')

<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="admin-page-title">Categories</h1>
                <p class="admin-page-subtitle">Manage category data used by products.</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form class="w-full sm:w-72">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search categories..."
                        class="admin-input"
                    >
                </form>

                <a href="{{ route('admin.categories.create') }}" class="admin-btn-primary w-full sm:w-auto">
                    Create Category
                </a>
            </div>
        </div>
    </div>

    {{-- Active --}}
    <x-admin.data-table title="Active Categories" :count="$categories->total()" count-badge="success">
        <x-slot:thead>
            <th class="px-4 py-4">Category</th>
            <th class="px-4 py-4">Products</th>
            <th class="px-4 py-4">Status</th>
            <th class="px-4 py-4 text-right">Action</th>
        </x-slot:thead>

        <x-slot:tbody>
            @forelse($categories as $category)
                <tr class="admin-table-row">
                    <td class="px-4 py-4">
                        <div class="font-semibold text-slate-800">{{ $category->name }}</div>
                        <div class="mt-1 text-xs text-slate-400">/{{ $category->slug }}</div>
                        @if($category->description)
                            <p class="mt-2 max-w-xl text-sm text-slate-500">
                                {{ \Illuminate\Support\Str::limit($category->description, 120) }}
                            </p>
                        @endif
                    </td>

                    <td class="px-4 py-4 text-sm font-medium text-slate-600">
                        {{ $category->products_count }}
                    </td>

                    <td class="px-4 py-4">
                        <span class="{{ $category->is_active ? 'admin-badge-success' : 'admin-badge-warning' }}">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>

                    <td class="px-4 py-4">
                        <div class="flex flex-col justify-end gap-2 sm:flex-row">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="admin-btn-soft px-4 py-2">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    onclick="return confirm('Move this category to archive?')"
                                    class="admin-btn-danger px-4 py-2"
                                >
                                    Archive
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-10">
                        <div class="admin-empty-state">No active categories found.</div>
                    </td>
                </tr>
            @endforelse
        </x-slot:tbody>

        <x-slot:pagination>
            {{ $categories->links() }}
        </x-slot:pagination>
    </x-admin.data-table>

    {{-- Archive --}}
    <x-admin.data-table title="Archive" :count="$archivedCategories->total()" count-badge="info">
        <x-slot:thead>
            <th class="px-4 py-4">Category</th>
            <th class="px-4 py-4">Archived At</th>
            <th class="px-4 py-4">Products</th>
            <th class="px-4 py-4 text-right">Action</th>
        </x-slot:thead>

        <x-slot:tbody>
            @forelse($archivedCategories as $category)
                <tr class="admin-table-row">
                    <td class="px-4 py-4">
                        <div class="font-semibold text-slate-800">{{ $category->name }}</div>
                        <div class="mt-1 text-xs text-slate-400">/{{ $category->slug }}</div>
                    </td>

                    <td class="px-4 py-4 text-sm text-slate-500">
                        {{ $category->deleted_at?->format('d M Y H:i') }}
                    </td>

                    <td class="px-4 py-4 text-sm font-medium text-slate-600">
                        {{ $category->products_count }}
                    </td>

                    <td class="px-4 py-4">
                        <div class="flex flex-col justify-end gap-2 sm:flex-row">
                            <form method="POST" action="{{ route('admin.categories.restore', $category->id) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="admin-btn-success px-4 py-2">Restore</button>
                            </form>

                            <form method="POST" action="{{ route('admin.categories.force-delete', $category->id) }}">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    onclick="return confirm('Permanently delete this category?')"
                                    class="admin-btn-danger px-4 py-2"
                                >
                                    Delete Permanent
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-6">
                        <div class="admin-empty-state">Archive is empty.</div>
                    </td>
                </tr>
            @endforelse
        </x-slot:tbody>

        <x-slot:pagination>
            {{ $archivedCategories->links() }}
        </x-slot:pagination>
    </x-admin.data-table>
</div>

@endsection
