@extends('layouts.admin')

@section('content')

<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="admin-page-title">FAQs</h1>
                <p class="admin-page-subtitle">Manage global frontend FAQ content.</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form class="w-full sm:w-64">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search FAQs..."
                        class="admin-input"
                    >
                </form>

                <a href="{{ route('admin.faqs.create') }}" class="admin-btn-primary w-full sm:w-auto">
                    Create FAQ
                </a>
            </div>
        </div>
    </div>

    <x-admin.data-table title="FAQ List" :count="$faqs->total()">
        <x-slot:thead>
            <th class="px-4 py-4">Question</th>
            <th class="px-4 py-4">Status</th>
            <th class="px-4 py-4">Sort</th>
            <th class="px-4 py-4 text-right">Action</th>
        </x-slot:thead>

        <x-slot:tbody>
            @forelse($faqs as $faq)
                <tr class="admin-table-row">
                    <td class="px-4 py-4">
                        <div class="font-semibold text-slate-100">{{ $faq->question }}</div>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400">
                            {{ \Illuminate\Support\Str::limit($faq->answer, 160) }}
                        </p>
                    </td>

                    <td class="px-4 py-4">
                        <span class="{{ $faq->is_active ? 'admin-badge-success' : 'admin-badge-warning' }}">
                            {{ $faq->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>

                    <td class="px-4 py-4 text-sm font-medium text-slate-400">
                        {{ $faq->sort_order }}
                    </td>

                    <td class="px-4 py-4">
                        <div class="flex flex-col justify-end gap-2 sm:flex-row">
                            <a href="{{ route('admin.faqs.edit', $faq) }}" class="admin-btn-soft px-4 py-2">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    onclick="return confirm('Delete this FAQ?')"
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
                    <td colspan="4" class="px-4 py-10">
                        <div class="admin-empty-state">No FAQs found.</div>
                    </td>
                </tr>
            @endforelse
        </x-slot:tbody>

        <x-slot:pagination>
            {{ $faqs->links() }}
        </x-slot:pagination>
    </x-admin.data-table>
</div>

@endsection
