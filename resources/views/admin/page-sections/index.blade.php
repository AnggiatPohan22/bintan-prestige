@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="rounded-xl bg-white p-6 shadow">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Page Sections</h1>
                <p class="mt-1 text-sm text-slate-500">Edit static homepage marketing content.</p>
            </div>
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $pageSections->count() }} item(s)</span>
        </div>
    </div>

    @unless($tableExists)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">The page_sections table does not exist yet.</div>
    @endunless

    <div class="rounded-xl bg-white p-6 shadow">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px]">
                <thead>
                    <tr class="border-b text-left">
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Page key</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Section key</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Label</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Title</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Sort</th>
                        <th class="px-4 py-4 text-right text-sm font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pageSections as $pageSection)
                        <tr class="border-b hover:bg-slate-50">
                            <td class="px-4 py-4 text-sm text-slate-700">{{ $pageSection->page_key }}</td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ $pageSection->section_key }}</td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ $pageSection->label ?: '-' }}</td>
                            <td class="px-4 py-4 text-sm font-semibold text-slate-800">{{ $pageSection->title ?: '-' }}</td>
                            <td class="px-4 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $pageSection->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $pageSection->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ $pageSection->sort_order }}</td>
                            <td class="px-4 py-4 text-right"><a href="{{ route('admin.page-sections.edit', $pageSection) }}" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-sm text-slate-500">No page sections found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
