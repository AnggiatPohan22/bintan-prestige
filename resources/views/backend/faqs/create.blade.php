@extends('layouts.admin')

@section('content')
<x-admin.form-shell
    title="Create FAQ"
    subtitle="Manage reusable frontend FAQ content."
    back-route="admin.faqs.index"
    form-action="{{ route('admin.faqs.store') }}"
>
    <x-slot:content>
        <div class="space-y-6">
            <div>
                <label for="question" class="admin-form-label">
                    Question <span class="text-red-500">*</span>
                </label>
                <input
                    id="question"
                    type="text"
                    name="question"
                    value="{{ old('question') }}"
                    class="admin-input @error('question') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    required
                >
                @error('question')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="answer" class="admin-form-label">
                    Answer <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="answer"
                    name="answer"
                    rows="6"
                    class="admin-textarea @error('answer') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                >{{ old('answer') }}</textarea>
                @error('answer')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </x-slot:content>

    <x-slot:sidebar>
        <x-admin.publish-box
            status-field="is_active"
            :status="old('is_active', '1')"
            :status-options="['1' => 'Active', '0' => 'Inactive']"
            :status-colors="['1' => 'success', '0' => 'warning']"
            submit-label="Save FAQ"
            cancel-route="admin.faqs.index"
        >
            <x-slot:extra>
                <div>
                    <label for="sort_order" class="admin-form-label">Sort Order</label>
                    <input
                        id="sort_order"
                        type="number"
                        name="sort_order"
                        min="0"
                        value="{{ old('sort_order', 0) }}"
                        class="admin-input @error('sort_order') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    >
                    @error('sort_order')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </x-slot:extra>
        </x-admin.publish-box>
    </x-slot:sidebar>
</x-admin.form-shell>
@endsection
