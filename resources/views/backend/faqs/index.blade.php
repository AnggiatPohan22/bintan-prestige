@extends('layouts.admin')

@section('content')

<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="admin-page-title">
                    FAQs
                </h1>

                <p class="admin-page-subtitle">
                    Manage global frontend FAQ content.
                </p>
            </div>

            <a
                href="{{ route('admin.faqs.create') }}"
                class="admin-btn-primary w-full sm:w-auto"
            >
                Create FAQ
            </a>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-extrabold text-slate-900">
                    FAQ List
                </h2>

                <span class="admin-badge-info">
                    {{ $faqs->total() }} item(s)
                </span>
            </div>
        </div>

        <div class="admin-card-body">
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr class="admin-table-header">
                        <th class="px-4 py-4">Question</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Sort</th>
                        <th class="px-4 py-4 text-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($faqs as $faq)
                        <tr class="admin-table-row">
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-800">
                                    {{ $faq->question }}
                                </div>

                                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                    {{ \Illuminate\Support\Str::limit($faq->answer, 160) }}
                                </p>
                            </td>

                            <td class="px-4 py-4">
                                <span class="{{ $faq->is_active ? 'admin-badge-success' : 'admin-badge-warning' }}">
                                    {{ $faq->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td class="px-4 py-4 text-sm font-medium text-slate-600">
                                {{ $faq->sort_order }}
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex flex-col justify-end gap-2 sm:flex-row">
                                    <a
                                        href="{{ route('admin.faqs.edit', $faq) }}"
                                        class="admin-btn-soft px-4 py-2"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.faqs.destroy', $faq) }}"
                                    >
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
                            <td colspan="4" class="px-4 py-6">
                                <div class="admin-empty-state">
                                    No FAQs found.
                                </div>
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
</div>

@endsection
