@extends('layouts.admin')

@section('content')

<div class="space-y-6">
    <div class="rounded-xl bg-white p-6 shadow">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    FAQs
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Manage global frontend FAQ content.
                </p>
            </div>

            <a href="{{ route('admin.faqs.create') }}" class="btn-primary">
                Create FAQ
            </a>
        </div>
    </div>

    <div class="rounded-xl bg-white p-6 shadow">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px]">
                <thead>
                    <tr class="border-b text-left">
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Question</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-4 text-sm font-semibold text-slate-600">Sort</th>
                        <th class="px-4 py-4 text-right text-sm font-semibold text-slate-600">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($faqs as $faq)
                        <tr class="border-b hover:bg-slate-50">
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-800">
                                    {{ $faq->question }}
                                </div>
                                <p class="mt-2 max-w-2xl text-sm text-slate-500">
                                    {{ \Illuminate\Support\Str::limit($faq->answer, 140) }}
                                </p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $faq->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $faq->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600">
                                {{ $faq->sort_order }}
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('admin.faqs.edit', $faq) }}" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600">
                                        Edit
                                    </a>

                                    <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" onclick="return confirm('Delete this FAQ?')" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center text-sm text-slate-500">
                                No FAQs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $faqs->links() }}
        </div>
    </div>
</div>

@endsection
