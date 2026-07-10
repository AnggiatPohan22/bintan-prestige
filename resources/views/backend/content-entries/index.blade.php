@extends('layouts.admin')

@section('content')
<div class="admin-page">

    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="mb-1 flex items-center gap-2 text-sm text-admin-secondary">
                    <a href="{{ route('admin.content-types.index') }}" class="hover:text-indigo-600">Content Types</a>
                    <span>/</span>
                    <span>{{ $contentType->label_plural }}</span>
                    <span>/</span>
                    <span>Entries</span>
                </div>
                <h1 class="admin-page-title">{{ $contentType->label_plural }}</h1>
                <p class="admin-page-subtitle">Manage all {{ strtolower($contentType->label_plural) }} entries.</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form class="flex w-full flex-col gap-2 sm:w-96 sm:flex-row" method="GET">
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search entries..."
                        class="admin-input flex-1"
                    >

                    @if(count(\App\Support\Locales::active()) > 1)
                        <select
                            name="locale"
                            class="admin-input sm:w-32"
                            onchange="this.form.submit()"
                            title="Filter by locale"
                        >
                            <option value="">All Locales</option>
                            @foreach(\App\Support\Locales::active() as $__code => $__meta)
                                <option value="{{ $__code }}" @selected(request('locale') === $__code)>{{ strtoupper($__code) }}</option>
                            @endforeach
                        </select>
                    @endif
                </form>

                <a href="{{ route('admin.content-types.entries.create', $contentType) }}"
                   class="admin-btn-primary w-full sm:w-auto">
                    New {{ $contentType->label_singular }}
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="admin-alert-success mb-6">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="admin-alert-danger mb-6">{{ session('error') }}</div>
    @endif

    {{-- Status filter tabs --}}
    <div class="mb-4 flex flex-wrap gap-2">
        @php
            $statuses = ['all' => 'All'] + array_combine(\App\Models\ContentEntry::STATUSES, array_map('ucfirst', \App\Models\ContentEntry::STATUSES));
            $current  = request('status', 'all');
        @endphp

        @foreach($statuses as $val => $label)
            <a
                href="{{ route('admin.content-types.entries.index', [$contentType, 'status' => $val === 'all' ? null : $val]) }}"
                class="{{ $current === $val ? 'admin-badge-info' : 'admin-badge-secondary' }} px-3 py-1 text-sm no-underline"
            >
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Active entries --}}
    <x-admin.data-table title="{{ $contentType->label_plural }}" :count="$active->total()" count-badge="success">
        <x-slot:thead>
            <th class="px-4 py-4">Entry</th>
            <th class="px-4 py-4">Status</th>
            @if(count(\App\Support\Locales::active()) > 1)
                <th class="px-4 py-4">Translations</th>
            @endif
            <th class="px-4 py-4">Author</th>
            <th class="px-4 py-4">Date</th>
            <th class="px-4 py-4 text-right">Action</th>
        </x-slot:thead>

        <x-slot:tbody>
            @forelse($active as $entry)
                <tr class="admin-table-row">
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2 font-semibold text-admin-secondary">
                            {{ $entry->title ?? '(no title)' }}
                            @if(count(\App\Support\Locales::active()) > 1)
                                <span class="rounded bg-violet-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-violet-700">{{ $entry->locale }}</span>
                            @endif
                        </div>
                        @if($entry->slug)
                            <div class="mt-0.5 font-mono text-xs text-admin-secondary">/{{ $entry->slug }}</div>
                        @endif
                        @if($entry->excerpt)
                            <p class="mt-1 max-w-md text-sm text-admin-secondary">
                                {{ \Illuminate\Support\Str::limit($entry->excerpt, 80) }}
                            </p>
                        @endif
                    </td>

                    <td class="px-4 py-4">
                        <span class="{{ $entry->statusBadgeClass() }}">{{ ucfirst($entry->status) }}</span>
                        @if($entry->published_at)
                            <div class="mt-0.5 text-xs text-admin-secondary">{{ $entry->published_at->format('d M Y') }}</div>
                        @endif
                    </td>

                    @if(count(\App\Support\Locales::active()) > 1)
                        <td class="px-4 py-4">
                            @include('backend._partials.translation-badges', [
                                'record'   => $entry,
                                'siblings' => $entry->translationSiblings,
                            ])
                        </td>
                    @endif

                    <td class="px-4 py-4 text-sm text-admin-secondary">
                        {{ $entry->author?->name ?? '—' }}
                    </td>

                    <td class="px-4 py-4 text-xs text-admin-secondary">
                        {{ $entry->updated_at->format('d M Y') }}
                    </td>

                    <td class="px-4 py-4">
                        <div class="flex flex-col justify-end gap-2 sm:flex-row">
                            <a href="{{ route('admin.content-types.entries.edit', [$contentType, $entry]) }}"
                               class="admin-btn-soft px-4 py-2">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('admin.content-types.entries.destroy', [$contentType, $entry]) }}">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    data-confirm="Move &quot;{{ $entry->title ?? 'this entry' }}&quot; to trash?"
                                    class="admin-btn-danger px-4 py-2"
                                >
                                    Trash
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10">
                        <div class="admin-empty-state">
                            No entries yet.
                            <a href="{{ route('admin.content-types.entries.create', $contentType) }}"
                               class="ml-1 text-indigo-600 hover:underline">
                                Create the first {{ strtolower($contentType->label_singular) }}.
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-slot:tbody>
    </x-admin.data-table>

    {{ $active->links() }}

    {{-- Trashed entries --}}
    @if($archived->isNotEmpty())
        <div class="mt-10">
            <x-admin.data-table title="Trash" :count="$archived->count()" count-badge="warning">
                <x-slot:thead>
                    <th class="px-4 py-4">Entry</th>
                    <th class="px-4 py-4">Trashed</th>
                    <th class="px-4 py-4 text-right">Action</th>
                </x-slot:thead>

                <x-slot:tbody>
                    @foreach($archived as $entry)
                        <tr class="admin-table-row opacity-70">
                            <td class="px-4 py-4">
                                <div class="font-semibold text-admin-secondary">{{ $entry->title ?? '(no title)' }}</div>
                                @if($entry->slug)
                                    <div class="font-mono text-xs text-admin-secondary">/{{ $entry->slug }}</div>
                                @endif
                            </td>

                            <td class="px-4 py-4 text-xs text-admin-secondary">
                                {{ $entry->deleted_at?->format('d M Y') }}
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex flex-col justify-end gap-2 sm:flex-row">
                                    <form method="POST" action="{{ route('admin.content-types.entries.restore', [$contentType, $entry->id]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="admin-btn-soft px-4 py-2">Restore</button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.content-types.entries.force-delete', [$contentType, $entry->id]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            data-confirm="Permanently delete this entry? This cannot be undone."
                                            class="admin-btn-danger px-4 py-2"
                                        >
                                            Delete Forever
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-slot:tbody>
            </x-admin.data-table>
        </div>
    @endif

</div>
@endsection
