<div class="rounded-xl bg-white p-6 shadow">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ isset($faq) ? 'Edit FAQ' : 'Create FAQ' }}</h1>
            <p class="mt-1 text-sm text-slate-500">FAQ items are dynamic content and are not stored in page_sections.</p>
        </div>
        <a href="{{ route('admin.faqs.index') }}" class="btn-secondary">Back</a>
    </div>

    <form action="{{ isset($faq) ? route('admin.faqs.update', $faq) : route('admin.faqs.store') }}" method="POST" class="space-y-6">
        @csrf
        @isset($faq)
            @method('PUT')
        @endisset

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div><label for="page_key" class="form-label">Page key</label><input id="page_key" name="page_key" value="{{ old('page_key', $faq->page_key ?? 'home') }}" class="form-input @error('page_key') form-error @enderror">@error('page_key')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="sort_order" class="form-label">Sort order</label><input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $faq->sort_order ?? 0) }}" class="form-input @error('sort_order') form-error @enderror">@error('sort_order')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        </div>

        <div><label for="question" class="form-label">Question</label><input id="question" name="question" value="{{ old('question', $faq->question ?? '') }}" class="form-input @error('question') form-error @enderror" required>@error('question')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div><label for="answer" class="form-label">Answer</label><textarea id="answer" name="answer" rows="6" class="form-textarea @error('answer') form-error @enderror" required>{{ old('answer', $faq->answer ?? '') }}</textarea>@error('answer')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>

        <label class="inline-flex items-center gap-3"><input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked(old('is_active', $faq->is_active ?? true))><span class="text-sm font-medium text-slate-700">Active FAQ</span></label>

        <div class="flex gap-3 border-t pt-5"><button type="submit" class="btn-primary">{{ isset($faq) ? 'Update FAQ' : 'Create FAQ' }}</button><a href="{{ route('admin.faqs.index') }}" class="btn-secondary">Cancel</a></div>
    </form>
</div>
