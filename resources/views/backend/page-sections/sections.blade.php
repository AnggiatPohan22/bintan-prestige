@extends('layouts.admin')

@section('content')

<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <a
                    href="{{ route('admin.page-sections.index') }}"
                    class="admin-btn-secondary mb-4 w-fit px-4 py-2"
                >
                    Back to Page Sections
                </a>

                <h1 class="admin-page-title">
                    {{ $selectedPage['label'] }}
                </h1>

                <p class="admin-page-subtitle">
                    {{ $selectedPage['description'] }}
                </p>
            </div>

            <div class="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-slate-800 px-4 py-3">
                <span class="text-[11px] font-black uppercase tracking-widest text-slate-400">
                    Selected page key
                </span>
                <span class="text-sm font-extrabold text-slate-100">
                    {{ $pageKey }}
                </span>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-100">
                        Section List
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-slate-400">
                        Manage editable sections for this selected frontend page.
                    </p>
                </div>

                <span class="admin-badge-info">
                    {{ $pageSections->total() }} item(s)
                </span>
            </div>
        </div>

        <div class="admin-card-body">
        <div class="admin-table-wrapper">
            <table class="admin-table table-fixed">
                <colgroup>
                    <col class="w-[13%]">
                    <col class="w-[29%]">
                    <col class="w-[17%]">
                    <col class="w-[18%]">
                    <col class="w-[9%]">
                    <col class="w-[6%]">
                    <col class="w-[8%]">
                </colgroup>
                <thead>
                    <tr class="admin-table-header">
                        <th class="px-5 py-4">Page key</th>
                        <th class="px-5 py-4">Section key</th>
                        <th class="px-5 py-4">Label</th>
                        <th class="px-5 py-4">Title</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Sort</th>
                        <th class="px-5 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pageSections as $section)
                        @php
                            $supportsImageInput = \App\Support\PageSectionRegistry::supportsImageInput($section->section_key);
                        @endphp
                        <tr class="admin-table-row align-top">
                            <td class="px-5 py-5 text-sm font-medium leading-6 text-slate-300">
                                <span class="block break-words">{{ $section->page_key }}</span>
                            </td>
                            <td class="px-5 py-5 text-sm leading-6 text-slate-400">
                                <span class="block break-words font-medium text-slate-300">{{ $section->section_key }}</span>
                                @if($supportsImageInput)
                                    <span class="mt-2 inline-flex rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold leading-none text-slate-400">
                                        {{ $section->media_count }} / {{ \App\Models\PageSection::MEDIA_LIMIT }} media item(s)
                                    </span>
                                @else
                                    <span class="mt-2 inline-flex rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-[11px] font-bold leading-none text-red-600">
                                        No image input
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-5 text-sm leading-6 text-slate-400">
                                <span class="block break-words">{{ $section->label ?: '-' }}</span>
                            </td>
                            <td class="px-5 py-5 text-sm font-semibold leading-6 text-slate-100">
                                <span class="block break-words">{{ $section->title ?: '-' }}</span>
                            </td>
                            <td class="px-5 py-5">
                                <span class="{{ $section->is_active ? 'admin-badge-success' : 'admin-badge-warning' }}">
                                    {{ $section->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-5 text-sm leading-6 text-slate-400">{{ $section->sort_order }}</td>
                            <td class="px-5 py-5 text-right">
                                <a
                                    href="{{ route('admin.page-sections.edit', $section) }}"
                                    class="admin-btn-soft px-4 py-2"
                                >
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-6">
                                <div class="admin-empty-state">
                                    No page sections found for this page.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $pageSections->links() }}
        </div>
        </div>
    </div>
</div>

@endsection
