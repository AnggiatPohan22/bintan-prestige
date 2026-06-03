@extends('layouts.admin')

@section('content')

@php
    $inputClass = 'w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100';
    $extraData = old('extra_data', json_encode($pageSection->extra_data ?? [], JSON_PRETTY_PRINT));
    $animation = old('animation', $pageSection->animation);
    $remainingSlots = max(0, \App\Models\PageSection::MEDIA_LIMIT - $pageSection->media->count());
@endphp

<div class="rounded-xl bg-white p-6 shadow">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                Edit Page Section
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Upload integration is foundation only. Leave file fields empty to keep current images.
            </p>
        </div>

        <a href="{{ route('admin.page-sections.index') }}" class="btn-secondary">
            Back
        </a>
    </div>

    <form
        method="POST"
        action="{{ route('admin.page-sections.update', $pageSection) }}"
        enctype="multipart/form-data"
        class="space-y-5"
    >
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label class="form-label">Page key</label>
                <input type="text" value="{{ $pageSection->page_key }}" disabled class="{{ $inputClass }} bg-slate-100">
            </div>

            <div>
                <label class="form-label">Section key</label>
                <input type="text" value="{{ $pageSection->section_key }}" disabled class="{{ $inputClass }} bg-slate-100">
            </div>

            <div>
                <label class="form-label">Label</label>
                <input type="text" name="label" value="{{ old('label', $pageSection->label) }}" class="{{ $inputClass }}">
                @error('label') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label">Title</label>
                <input type="text" name="title" value="{{ old('title', $pageSection->title) }}" class="{{ $inputClass }}">
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="form-label">Subtitle</label>
                <input type="text" name="subtitle" value="{{ old('subtitle', $pageSection->subtitle) }}" class="{{ $inputClass }}">
                @error('subtitle') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="form-label">Description</label>
                <textarea name="description" rows="5" class="{{ $inputClass }}">{{ old('description', $pageSection->description) }}</textarea>
                @error('description') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label">Button text</label>
                <input type="text" name="button_text" value="{{ old('button_text', $pageSection->button_text) }}" class="{{ $inputClass }}">
            </div>

            <div>
                <label class="form-label">Button URL</label>
                <input type="text" name="button_url" value="{{ old('button_url', $pageSection->button_url) }}" class="{{ $inputClass }}">
            </div>

            <div>
                <label class="form-label">Image upload</label>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="{{ $inputClass }}">
                <p class="mt-2 text-xs text-slate-400">Recommended format: WebP/JPG, max 2MB. Leave empty to keep current image.</p>
                @error('image') <p class="form-error">{{ $message }}</p> @enderror

                @if($pageSection->image_url)
                    <img src="{{ $pageSection->image_url }}" alt="{{ $pageSection->title }}" class="mt-3 h-32 w-full rounded-lg border object-cover">
                @endif
            </div>

            <div>
                <label class="form-label">Mobile image upload</label>
                <input type="file" name="mobile_image" accept="image/jpeg,image/png,image/webp" class="{{ $inputClass }}">
                <p class="mt-2 text-xs text-slate-400">Recommended format: WebP/JPG, max 2MB. Leave empty to keep current image.</p>
                @error('mobile_image') <p class="form-error">{{ $message }}</p> @enderror

                @if($pageSection->mobile_image_url)
                    <img src="{{ $pageSection->mobile_image_url }}" alt="{{ $pageSection->title }}" class="mt-3 h-32 w-full rounded-lg border object-cover">
                @endif
            </div>

            <div>
                <label class="form-label">Image path</label>
                <input type="text" name="image_path" value="{{ old('image_path', $pageSection->image) }}" class="{{ $inputClass }}">
            </div>

            <div>
                <label class="form-label">Mobile image path</label>
                <input type="text" name="mobile_image_path" value="{{ old('mobile_image_path', $pageSection->mobile_image) }}" class="{{ $inputClass }}">
            </div>

            <div class="md:col-span-2">
                <label class="form-label">Section gallery images</label>
                <input
                    type="file"
                    name="media_uploads[]"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    class="{{ $inputClass }}"
                    @disabled($remainingSlots === 0)
                >
                <p class="mt-2 text-xs text-slate-400">
                    Upload up to {{ \App\Models\PageSection::MEDIA_LIMIT }} images per section. Remaining slots: {{ $remainingSlots }}.
                    Hero and any future multi-image section can reuse this gallery.
                </p>
                @error('media_uploads') <p class="form-error">{{ $message }}</p> @enderror
                @error('media_uploads.*') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label">Animation</label>
                <select name="animation" class="{{ $inputClass }}">
                    @foreach(\App\Models\PageSection::ANIMATION_OPTIONS as $option)
                        <option value="{{ $option }}" @selected($animation === $option)>
                            {{ ucwords(str_replace('-', ' ', $option)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label">Sort order</label>
                <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $pageSection->sort_order) }}" class="{{ $inputClass }}">
            </div>

            <div class="md:col-span-2">
                <label class="form-label">Extra data JSON</label>
                <textarea name="extra_data" rows="6" class="{{ $inputClass }}">{{ $extraData }}</textarea>
                @error('extra_data') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-3">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $pageSection->is_active))>
                <span class="text-sm font-semibold text-slate-700">Active</span>
            </label>
        </div>

        <div class="flex items-center gap-3 border-t pt-5">
            <button type="submit" class="btn-primary">
                Save Section
            </button>
            <a href="{{ route('admin.page-sections.index') }}" class="btn-secondary">
                Cancel
            </a>
        </div>
    </form>

    @if($pageSection->media->count())
        <div class="mt-8 border-t pt-6">
            <h2 class="text-lg font-bold text-slate-800">
                Section Gallery
            </h2>

            <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach($pageSection->media as $media)
                    <div class="rounded-xl border bg-white p-3 shadow-sm">
                        <img src="{{ $media->url }}" alt="{{ $media->alt }}" class="h-32 w-full rounded-lg object-cover">

                        <form method="POST" action="{{ route('admin.page-sections.media.destroy', $media) }}" class="mt-3">
                            @csrf
                            @method('DELETE')

                            <button type="submit" onclick="return confirm('Delete this section image?')" class="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">
                                Delete Image
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

@endsection
