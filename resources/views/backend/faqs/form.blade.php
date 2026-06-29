<div class="admin-form-card">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-admin-secondary">{{ $title }}</h1>
            <p class="mt-1 text-sm text-admin-secondary">Manage reusable frontend FAQ content.</p>
        </div>
        <a href="{{ route('admin.faqs.index') }}" class="admin-btn-secondary">Back</a>
    </div>

    <form method="POST" action="{{ $action }}" class="space-y-5">
        @csrf
        @if($method !== 'POST') @method($method) @endif

        <div>
            <label class="admin-form-label">Question</label>
            <input type="text" name="question" value="{{ old('question', $faq->question) }}"
                class="admin-input @error('question') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror">
            @error('question') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="admin-form-label">Answer</label>
            <textarea name="answer" rows="6"
                class="admin-textarea @error('answer') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror">{{ old('answer', $faq->answer) }}</textarea>
            @error('answer') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="admin-form-label">Sort order</label>
            <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $faq->sort_order ?? 0) }}"
                class="admin-input @error('sort_order') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror">
            @error('sort_order') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-3">
            <input type="checkbox" name="is_active" value="1"
                class="rounded border-admin text-indigo-600 focus:ring-indigo-500"
                @checked(old('is_active', $faq->is_active ?? true))>
            <span class="text-sm font-semibold text-admin-secondary">Active</span>
        </label>

        <div class="flex items-center gap-3 border-t pt-5">
            <button type="submit" class="admin-btn-primary">Save FAQ</button>
            <a href="{{ route('admin.faqs.index') }}" class="admin-btn-secondary">Cancel</a>
        </div>
    </form>
</div>
