@extends('layouts.admin')

@section('content')

<div class="space-y-6">
    <div class="rounded-xl bg-white p-6 shadow">
        <h1 class="text-2xl font-bold text-slate-800">Page Sections</h1>
        <p class="mt-1 text-sm text-slate-500">Manage editable static section content and images by page.</p>
    </div>

    <div class="rounded-xl bg-white p-6 shadow">
        @if($pageKeys->count())
            <div class="mb-5 border-b border-slate-200 pb-4">
                <div class="flex gap-3 overflow-x-auto pb-2">
                    @foreach($pageOptions as $page)
                        @php
                            $pageKey = $page['key'];
                            $isActivePage = $activePageKey === $pageKey;
                        @endphp

                        <a
                            href="{{ route('admin.page-sections.index', ['page' => $pageKey]) }}"
                            class="flex min-w-56 shrink-0 flex-col rounded-xl border p-4 text-left transition {{ $isActivePage ? 'border-emerald-300 bg-emerald-50 text-emerald-800 shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50' }}"
                            aria-current="{{ $isActivePage ? 'page' : 'false' }}"
                        >
                            <span class="text-[11px] font-bold uppercase tracking-wide {{ $isActivePage ? 'text-emerald-600' : 'text-slate-400' }}">
                                {{ $page['group'] }}
                            </span>
                            <span class="mt-1 text-sm font-extrabold">
                                {{ $page['label'] }}
                            </span>
                            <span class="mt-1 line-clamp-2 text-xs leading-5 {{ $isActivePage ? 'text-emerald-700' : 'text-slate-500' }}">
                                {{ $page['description'] }}
                            </span>
                            <span class="mt-3 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $page['exists'] ? 'bg-white/80 text-slate-600 ring-1 ring-slate-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-100' }}">
                                {{ $pageKey }}
                            </span>
                        </a>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-slate-500">Registered frontend pages are synced automatically. Section order follows the frontend mapping when available.</p>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1180px] table-fixed">
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
                    <tr class="border-b text-left">
                        <th class="px-5 py-4 text-sm font-semibold text-slate-600">Page key</th>
                        <th class="px-5 py-4 text-sm font-semibold text-slate-600">Section key</th>
                        <th class="px-5 py-4 text-sm font-semibold text-slate-600">Label</th>
                        <th class="px-5 py-4 text-sm font-semibold text-slate-600">Title</th>
                        <th class="px-5 py-4 text-sm font-semibold text-slate-600">Status</th>
                        <th class="px-5 py-4 text-sm font-semibold text-slate-600">Sort</th>
                        <th class="px-5 py-4 text-right text-sm font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pageSections as $section)
                        @php
                            $supportsImageInput = \App\Support\PageSectionRegistry::supportsImageInput($section->section_key);
                        @endphp
                        <tr class="border-b align-top hover:bg-slate-50">
                            <td class="px-5 py-5 text-sm font-medium leading-6 text-slate-700">
                                <span class="block break-words">{{ $section->page_key }}</span>
                            </td>
                            <td class="px-5 py-5 text-sm leading-6 text-slate-600">
                                <span class="block break-words font-medium text-slate-700">{{ $section->section_key }}</span>
                                @if($supportsImageInput)
                                    <span class="mt-2 inline-flex rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold leading-none text-slate-500">
                                        {{ $section->media_count }} / {{ \App\Models\PageSection::MEDIA_LIMIT }} media item(s)
                                    </span>
                                @else
                                    <span class="mt-2 inline-flex rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-[11px] font-bold leading-none text-red-600">
                                        No image input
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-5 text-sm leading-6 text-slate-600">
                                <span class="block break-words">{{ $section->label ?: '-' }}</span>
                            </td>
                            <td class="px-5 py-5 text-sm font-semibold leading-6 text-slate-800">
                                <span class="block break-words">{{ $section->title ?: '-' }}</span>
                            </td>
                            <td class="px-5 py-5">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $section->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $section->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-5 py-5 text-sm leading-6 text-slate-600">{{ $section->sort_order }}</td>
                            <td class="px-5 py-5 text-right">
                                <a href="{{ route('admin.page-sections.edit', $section) }}" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-sm text-slate-500">No page sections found for this page.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $pageSections->links() }}</div>
    </div>
</div>

@endsection
