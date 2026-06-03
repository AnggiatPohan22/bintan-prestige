@php
    $inputClass = 'w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100';
@endphp

<div class="rounded-xl bg-white p-6 shadow">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $title }}</h1>
            <p class="mt-1 text-sm text-slate-500">Manage reusable frontend FAQ content.</p>
        </div>
        <a href="{{ route('admin.faqs.index') }}" class="btn-secondary">Back</a>
    </div>

    <form method="POST" action="{{ $action }}" class="space-y-5">
        @csrf
        @if($method !== 'POST') @method($method) @endif
        <div><label class="form-label">Question</label><input type="text" name="question" value="{{ old('question', $faq->question) }}" class="{{ $inputClass }}">@error('question') <p class="form-error">{{ $message }}</p> @enderror</div>
        <div><label class="form-label">Answer</label><textarea name="answer" rows="6" class="{{ $inputClass }}">{{ old('answer', $faq->answer) }}</textarea>@error('answer') <p class="form-error">{{ $message }}</p> @enderror</div>
        <div><label class="form-label">Sort order</label><input type="number" name="sort_order" min="0" value="{{ old('sort_order', $faq->sort_order ?? 0) }}" class="{{ $inputClass }}">@error('sort_order') <p class="form-error">{{ $message }}</p> @enderror</div>
        <label class="flex items-center gap-3"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $faq->is_active ?? true))><span class="text-sm font-semibold text-slate-700">Active</span></label>
        <div class="flex items-center gap-3 border-t pt-5">
            <button type="submit" class="btn-primary">Save FAQ</button>
            <a href="{{ route('admin.faqs.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
