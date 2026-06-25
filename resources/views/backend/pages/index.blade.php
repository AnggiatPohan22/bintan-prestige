@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="admin-page-title">Pages</h1>
                <p class="admin-page-subtitle">
                    Manage website pages. Published pages are visible to visitors.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:gap-2">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search pages..."
                        class="admin-input w-full sm:w-56"
                    >

                    <select
                        name="status"
                        class="admin-input w-full sm:w-36"
                        onchange="this.form.submit()"
                    >
                        <option value="">All Status</option>
                        <option value="published" @selected(request('status') === 'published')>Published</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                        <option value="scheduled" @selected(request('status') === 'scheduled')>Scheduled</option>
                    </select>
                </form>

                <a
                    href="{{ route('admin.pages.create') }}"
                    class="admin-btn-primary w-full sm:w-auto"
                >
                    Create Page
                </a>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-extrabold text-admin-primary">All Pages</h2>
                <span class="admin-badge-info">{{ $pages->total() }} page(s)</span>
            </div>
        </div>

        <div class="admin-card-body">
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr class="admin-table-header">
                            <th class="px-4 py-4">Page</th>
                            <th class="px-4 py-4">Status</th>
                            <th class="px-4 py-4">Sort</th>
                            <th class="px-4 py-4">Updated</th>
                            <th class="px-4 py-4 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($pages as $page)
                            <tr class="admin-table-row">
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-admin-primary">
                                        {{ $page->title }}
                                    </div>
                                    <div class="mt-1 text-xs text-admin-secondary">
                                        /pages/{{ $page->slug }}
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    @if($page->isPublished())
                                        <span class="admin-badge-success">Published</span>
                                        <p class="mt-1 max-w-44 text-xs text-admin-secondary">Live publicly and eligible for managed menus.</p>
                                    @elseif($page->isScheduled())
                                        <span class="admin-badge-warning">Scheduled</span>
                                        <p class="mt-1 max-w-44 text-xs text-admin-secondary">
                                            Publishes {{ $page->publish_at->format('d M Y, H:i') }}
                                        </p>
                                    @else
                                        <span class="admin-badge-warning">Draft</span>
                                        <p class="mt-1 max-w-44 text-xs text-admin-secondary">Admin preview only; hidden from public pages and menus.</p>
                                    @endif
                                </td>

                                <td class="px-4 py-4 text-sm text-admin-secondary">
                                    {{ $page->sort_order }}
                                </td>

                                <td class="px-4 py-4 text-sm text-admin-secondary">
                                    {{ $page->updated_at->format('d M Y') }}
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex flex-col justify-end gap-2 sm:flex-row sm:flex-wrap">
                                        <a
                                            href="{{ route('admin.pages.preview', $page) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="admin-btn-soft px-4 py-2"
                                        >
                                            {{ $page->isPublished() ? 'Preview' : 'Preview Draft' }}
                                        </a>

                                        @if($page->isPublished())
                                            <a
                                                href="{{ route('pages.show', $page->slug) }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="admin-btn-soft px-4 py-2"
                                            >
                                                View Live
                                            </a>
                                        @endif

                                        <a
                                            href="{{ route('admin.pages.edit', $page) }}"
                                            class="admin-btn-soft px-4 py-2"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.pages.duplicate', $page) }}"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                data-confirm="Duplicate \"{{ addslashes($page->title) }}\"?"
                                                class="admin-btn-soft px-4 py-2"
                                            >
                                                Duplicate
                                            </button>
                                        </form>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.pages.destroy', $page) }}"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                data-confirm="Delete this page permanently?"
                                                class="admin-btn-danger px-4 py-2"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10">
                                    <div class="admin-empty-state">
                                        <p class="font-medium text-admin-secondary">No pages found.</p>
                                        <p class="mt-1 text-sm text-admin-secondary">
                                            <a href="{{ route('admin.pages.create') }}" class="text-indigo-600 hover:underline">
                                                Create your first page
                                            </a>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $pages->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
