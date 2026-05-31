@extends('layouts.admin')

@section('content')

<div class="space-y-6">
    <div class="rounded-xl bg-white p-4 shadow sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    Destinations
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Manage destination data used by products.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form class="w-full sm:w-72">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search destinations..."
                        class="form-input"
                    >
                </form>

                <a
                    href="{{ route('admin.destinations.create') }}"
                    class="btn-primary w-full sm:w-auto"
                >
                    Create Destination
                </a>
            </div>
        </div>
    </div>

    <div class="rounded-xl bg-white p-6 shadow">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-800">
                Active Destinations
            </h2>

            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                {{ $destinations->total() }} item(s)
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px]">
                <thead>
                    <tr class="border-b text-left">
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Destination</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Products</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-4 text-right text-sm font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($destinations as $destination)
                        <tr class="border-b hover:bg-slate-50">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    @if($destination->image)
                                        <img
                                            src="{{ asset('storage/'.$destination->image) }}"
                                            class="h-16 w-20 rounded-lg border object-cover"
                                            alt="{{ $destination->name }}"
                                        >
                                    @else
                                        <div class="flex h-16 w-20 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-400">
                                            No Image
                                        </div>
                                    @endif

                                    <div>
                                        <div class="font-semibold text-slate-800">
                                            {{ $destination->name }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-400">
                                            /{{ $destination->slug }}
                                        </div>
                                        @if($destination->description)
                                            <p class="mt-2 max-w-xl text-sm text-slate-500">
                                                {{ \Illuminate\Support\Str::limit($destination->description, 120) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4 text-sm font-medium text-slate-600">
                                {{ $destination->products_count }}
                            </td>

                            <td class="px-4 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold
                                    {{ $destination->is_active
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-slate-100 text-slate-600' }}">
                                    {{ $destination->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-3">
                                    <a
                                        href="{{ route('admin.destinations.edit', $destination) }}"
                                        class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.destinations.destroy', $destination) }}"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            onclick="return confirm('Move this destination to archive?')"
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
                                No active destinations found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $destinations->links() }}
        </div>
    </div>

    <div class="rounded-xl bg-white p-6 shadow">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-800">
                Archive
            </h2>

            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                {{ $archivedDestinations->total() }} item(s)
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px]">
                <thead>
                    <tr class="border-b text-left">
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Destination</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Archived At</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Products</th>
                        <th class="px-4 py-4 text-right text-sm font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($archivedDestinations as $destination)
                        <tr class="border-b hover:bg-slate-50">
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-800">
                                    {{ $destination->name }}
                                </div>
                                <div class="mt-1 text-xs text-slate-400">
                                    /{{ $destination->slug }}
                                </div>
                            </td>

                            <td class="px-4 py-4 text-sm text-slate-500">
                                {{ $destination->deleted_at?->format('d M Y H:i') }}
                            </td>

                            <td class="px-4 py-4 text-sm font-medium text-slate-600">
                                {{ $destination->products_count }}
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-3">
                                    <form
                                        method="POST"
                                        action="{{ route('admin.destinations.restore', $destination->id) }}"
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
                                        action="{{ route('admin.destinations.force-delete', $destination->id) }}"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            onclick="return confirm('Permanently delete this destination?')"
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
            {{ $archivedDestinations->links() }}
        </div>
    </div>
</div>

@endsection
