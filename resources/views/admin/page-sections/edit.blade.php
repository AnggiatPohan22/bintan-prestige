@extends('layouts.admin')

@section('content')

<div class="rounded-xl bg-white p-6 shadow">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                Edit Page Section
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Update static marketing content. Page and section keys are system identifiers.
            </p>
        </div>

        <a
            href="{{ route('admin.page-sections.index') }}"
            class="btn-secondary w-full sm:w-auto"
        >
            Back
        </a>
    </div>

    <form
        action="{{ route('admin.page-sections.update', $pageSection) }}"
        method="POST"
        class="space-y-6"
    >
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="page_key" class="form-label">Page key</label>
                <input
                    id="page_key"
                    type="text"
                    value="{{ $pageSection->page_key }}"
                    class="form-input bg-slate-100 text-slate-500"
                    readonly
                >
            </div>

            <div>
                <label for="section_key" class="form-label">Section key</label>
                <input
                    id="section_key"
                    type="text"
                    value="{{ $pageSection->section_key }}"
                    class="form-input bg-slate-100 text-slate-500"
                    readonly
                >
            </div>

            <div>
                <label for="label" class="form-label">Label</label>
                <input
                    id="label"
                    type="text"
                    name="label"
                    value="{{ old('label', $pageSection->label) }}"
                    class="form-input @error('label') form-error @enderror"
                >
                @error('label')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="title" class="form-label">Title</label>
                <input
                    id="title"
                    type="text"
                    name="title"
                    value="{{ old('title', $pageSection->title) }}"
                    class="form-input @error('title') form-error @enderror"
                >
                @error('title')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label for="subtitle" class="form-label">Subtitle</label>
                <input
                    id="subtitle"
                    type="text"
                    name="subtitle"
                    value="{{ old('subtitle', $pageSection->subtitle) }}"
                    class="form-input @error('subtitle') form-error @enderror"
                >
                @error('subtitle')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="description" class="form-label">Description</label>
            <textarea
                id="description"
                name="description"
                rows="5"
                class="form-textarea @error('description') form-error @enderror"
            >{{ old('description', $pageSection->description) }}</textarea>
            @error('description')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="button_text" class="form-label">Button text</label>
                <input
                    id="button_text"
                    type="text"
                    name="button_text"
                    value="{{ old('button_text', $pageSection->button_text) }}"
                    class="form-input @error('button_text') form-error @enderror"
                >
                @error('button_text')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="button_url" class="form-label">Button URL</label>
                <input
                    id="button_url"
                    type="text"
                    name="button_url"
                    value="{{ old('button_url', $pageSection->button_url) }}"
                    class="form-input @error('button_url') form-error @enderror"
                >
                @error('button_url')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="image" class="form-label">Image path</label>
                <input
                    id="image"
                    type="text"
                    name="image"
                    value="{{ old('image', $pageSection->image) }}"
                    class="form-input @error('image') form-error @enderror"
                    placeholder="homepage/about.jpg or https://..."
                >
                <p class="mt-2 text-xs text-slate-500">
                    Upload integration later. For now, use a storage path or full URL.
                </p>
                @error('image')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="mobile_image" class="form-label">Mobile image path</label>
                <input
                    id="mobile_image"
                    type="text"
                    name="mobile_image"
                    value="{{ old('mobile_image', $pageSection->mobile_image) }}"
                    class="form-input @error('mobile_image') form-error @enderror"
                    placeholder="homepage/about-mobile.jpg or https://..."
                >
                <p class="mt-2 text-xs text-slate-500">
                    Upload integration later. Leave empty to use the same fallback behavior.
                </p>
                @error('mobile_image')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="extra_data" class="form-label">Extra data JSON</label>
            <textarea
                id="extra_data"
                name="extra_data"
                rows="12"
                class="form-textarea font-mono text-sm @error('extra_data') form-error @enderror"
                placeholder='{"overlay_title": "Example"}'
            >{{ old('extra_data', $extraDataJson) }}</textarea>
            <p class="mt-2 text-xs text-slate-500">
                Must be valid JSON. Used for section-specific fields such as overlay text, testimonial items, or extra image slots.
            </p>
            @error('extra_data')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <label class="inline-flex items-center gap-3">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                    @checked(old('is_active', $pageSection->is_active))
                >
                <span class="text-sm font-medium text-slate-700">
                    Active section
                </span>
            </label>

            <div>
                <label for="sort_order" class="form-label">Sort order</label>
                <input
                    id="sort_order"
                    type="number"
                    min="0"
                    name="sort_order"
                    value="{{ old('sort_order', $pageSection->sort_order) }}"
                    class="form-input @error('sort_order') form-error @enderror"
                >
                @error('sort_order')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t pt-5 sm:flex-row">
            <button type="submit" class="btn-primary">
                Update Page Section
            </button>

            <a href="{{ route('admin.page-sections.index') }}" class="btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

@endsection
