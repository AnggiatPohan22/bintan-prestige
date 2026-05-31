@extends('layouts.admin')

@section('content')

<div class="space-y-6">
    <div class="rounded-xl bg-white p-4 shadow sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    Categories
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Manage category data used by products.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form class="w-full sm:w-72">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search categories..."
                        class="form-input"
                    >
                </form>

                <a
                    href="{{ route('admin.categories.create') }}"
                    class="btn-primary w-full sm:w-auto"
                >
                    Create Category
                </a>
            </div>
        </div>
    </div>

    <div class="rounded-xl bg-white p-6 shadow">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-800">
                Active Categories
            </h2>

            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                {{ $categories->total() }} item(s)
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px]">
                <thead>
                    <tr class="border-b text-left">
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Category</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Products</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-4 text-right text-sm font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($categories as $category)
                        <tr class="border-b hover:bg-slate-50">
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-800">
                                    {{ $category->name }}
                                </div>
                                <div class="mt-1 text-xs text-slate-400">
                                    /{{ $category->slug }}
                                </div>
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
                                <span class="rounded-full px-3 py-1 text-xs font-semibold
                                    {{ $category->is_active
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-slate-100 text-slate-600' }}">
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-3">
                                    <a
                                        href="{{ route('admin.categories.edit', $category) }}"
                                        class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.categories.destroy', $category) }}"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            onclick="return confirm('Move this category to archive?')"
                                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700"
                                        >
                                            Archive
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center text-sm text-slate-500">
                                No active categories found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $categories->links() }}
        </div>
    </div>

    <div class="rounded-xl bg-white p-6 shadow">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-800">
                Archive
            </h2>

            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                {{ $archivedCategories->total() }} item(s)
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px]">
                <thead>
                    <tr class="border-b text-left">
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Category</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Archived At</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Products</th>
                        <th class="px-4 py-4 text-right text-sm font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($archivedCategories as $category)
                        <tr class="border-b hover:bg-slate-50">
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-800">
                                    {{ $category->name }}
                                </div>
                                <div class="mt-1 text-xs text-slate-400">
                                    /{{ $category->slug }}
                                </div>
                            </td>

                            <td class="px-4 py-4 text-sm text-slate-500">
                                {{ $category->deleted_at?->format('d M Y H:i') }}
                            </td>

                            <td class="px-4 py-4 text-sm font-medium text-slate-600">
                                {{ $category->products_count }}
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-3">
                                    <form
                                        method="POST"
                                        action="{{ route('admin.categories.restore', $category->id) }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            type="submit"
                                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                        >
                                            Restore
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.categories.force-delete', $category->id) }}"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            onclick="return confirm('Permanently delete this category?')"
                                            class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-800"
                                        >
                                            Delete Permanent
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center text-sm text-slate-500">
                                Archive is empty.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $archivedCategories->links() }}
        </div>
    </div>
</div>

@endsection
