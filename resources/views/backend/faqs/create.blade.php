@extends('layouts.admin')

@section('content')
    <div class="admin-page">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Create FAQ</h1>
                <p class="admin-page-subtitle">
                    Manage reusable frontend FAQ content.
                </p>
            </div>

            <a
                href="{{ route('admin.faqs.index') }}"
                class="admin-btn-secondary mt-5 w-full sm:mt-0 sm:w-auto"
            >
                Back
            </a>
        </div>

        <div class="admin-form-card">
            <form
                method="POST"
                action="{{ route('admin.faqs.store') }}"
                class="space-y-6"
            >
                @csrf

                <div>
                    <label for="question" class="admin-form-label">Question</label>
                    <input
                        id="question"
                        type="text"
                        name="question"
                        value="{{ old('question', $faq->question) }}"
                        class="admin-input @error('question') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    >
                    @error('question')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="answer" class="admin-form-label">Answer</label>
                    <textarea
                        id="answer"
                        name="answer"
                        rows="6"
                        class="admin-textarea @error('answer') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    >{{ old('answer', $faq->answer) }}</textarea>
                    @error('answer')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sort_order" class="admin-form-label">Sort order</label>
                    <input
                        id="sort_order"
                        type="number"
                        name="sort_order"
                        min="0"
                        value="{{ old('sort_order', $faq->sort_order ?? 0) }}"
                        class="admin-input @error('sort_order') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    >
                    @error('sort_order')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <label class="inline-flex items-center gap-3">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        @checked(old('is_active', $faq->is_active ?? true))
                    >
                    <span class="text-sm font-semibold text-slate-700">
                        Active
                    </span>
                </label>

                <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center">
                    <button type="submit" class="admin-btn-primary w-full sm:w-auto">
                        Save FAQ
                    </button>

                    <a
                        href="{{ route('admin.faqs.index') }}"
                        class="admin-btn-secondary w-full sm:w-auto"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
