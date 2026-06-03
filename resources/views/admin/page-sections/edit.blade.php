@extends('layouts.admin')

@section('content')
<div class="rounded-xl bg-white p-6 shadow">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Edit Page Section</h1>
            <p class="mt-1 text-sm text-slate-500">Page and section keys are system identifiers.</p>
        </div>
        <a href="{{ route('admin.page-sections.index') }}" class="btn-secondary">Back</a>
    </div>

    <form action="{{ route('admin.page-sections.update', $pageSection) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div><label class="form-label">Page key</label><input type="text" value="{{ $pageSection->page_key }}" class="form-input bg-slate-100 text-slate-500" readonly></div>
            <div><label class="form-label">Section key</label><input type="text" value="{{ $pageSection->section_key }}" class="form-input bg-slate-100 text-slate-500" readonly></div>
            <div><label for="label" class="form-label">Label</label><input id="label" name="label" value="{{ old('label', $pageSection->label) }}" class="form-input @error('label') form-error @enderror">@error('label')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="title" class="form-label">Title</label><input id="title" name="title" value="{{ old('title', $pageSection->title) }}" class="form-input @error('title') form-error @enderror">@error('title')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div class="md:col-span-2"><label for="subtitle" class="form-label">Subtitle</label><input id="subtitle" name="subtitle" value="{{ old('subtitle', $pageSection->subtitle) }}" class="form-input @error('subtitle') form-error @enderror">@error('subtitle')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        </div>

        <div><label for="description" class="form-label">Description</label><textarea id="description" name="description" rows="5" class="form-textarea @error('description') form-error @enderror">{{ old('description', $pageSection->description) }}</textarea>@error('description')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div><label for="button_text" class="form-label">Button text</label><input id="button_text" name="button_text" value="{{ old('button_text', $pageSection->button_text) }}" class="form-input @error('button_text') form-error @enderror">@error('button_text')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="button_url" class="form-label">Button URL</label><input id="button_url" name="button_url" value="{{ old('button_url', $pageSection->button_url) }}" class="form-input @error('button_url') form-error @enderror">@error('button_url')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div class="space-y-4 rounded-xl border border-slate-200 p-4">
                <div>
                    <label for="image_upload" class="form-label">Upload image</label>
                    <input
                        id="image_upload"
                        type="file"
                        name="image_upload"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        class="form-input @error('image_upload') form-error @enderror"
                    >
                    <p class="mt-2 text-xs text-slate-500">
                        Recommended format: WebP/JPG, max 2MB. Leave empty to keep current image.
                    </p>
                    @error('image_upload')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="image" class="form-label">Image path</label>
                    <input id="image" name="image" value="{{ old('image', $pageSection->image) }}" class="form-input @error('image') form-error @enderror" placeholder="page-sections/example.webp or https://...">
                    <p class="mt-2 text-xs text-slate-500">
                        Uploading a new file will replace this value automatically. Manual path is kept for compatibility.
                    </p>
                    @error('image')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                @if($pageSection->image_url)
                    <img src="{{ $pageSection->image_url }}" class="h-40 w-full rounded-lg border object-cover" alt="{{ $pageSection->title ?? 'Page section image' }}">
                @else
                    <div class="flex h-40 w-full items-center justify-center rounded-lg bg-slate-100 text-sm text-slate-400">
                        No Image
                    </div>
                @endif
            </div>

            <div class="space-y-4 rounded-xl border border-slate-200 p-4">
                <div>
                    <label for="mobile_image_upload" class="form-label">Upload mobile image</label>
                    <input
                        id="mobile_image_upload"
                        type="file"
                        name="mobile_image_upload"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        class="form-input @error('mobile_image_upload') form-error @enderror"
                    >
                    <p class="mt-2 text-xs text-slate-500">
                        Recommended format: WebP/JPG, max 2MB. Leave empty to keep current mobile image.
                    </p>
                    @error('mobile_image_upload')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="mobile_image" class="form-label">Mobile image path</label>
                    <input id="mobile_image" name="mobile_image" value="{{ old('mobile_image', $pageSection->mobile_image) }}" class="form-input @error('mobile_image') form-error @enderror" placeholder="page-sections/example-mobile.webp or https://...">
                    <p class="mt-2 text-xs text-slate-500">
                        Uploading a new file will replace this value automatically. Manual path is kept for compatibility.
                    </p>
                    @error('mobile_image')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                @if($pageSection->mobile_image_url)
                    <img src="{{ $pageSection->mobile_image_url }}" class="h-40 w-full rounded-lg border object-cover" alt="{{ $pageSection->title ?? 'Page section mobile image' }}">
                @else
                    <div class="flex h-40 w-full items-center justify-center rounded-lg bg-slate-100 text-sm text-slate-400">
                        No Mobile Image
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="section_animation" class="form-label">Image animation</label>
                @php
                    $selectedAnimation = old('section_animation', $pageSection->extra_data['animation'] ?? 'ken-burns');
                @endphp
                <select id="section_animation" name="section_animation" class="form-input @error('section_animation') form-error @enderror">
                    <option value="ken-burns" @selected($selectedAnimation === 'ken-burns')>Ken Burns</option>
                    <option value="zoom-in" @selected($selectedAnimation === 'zoom-in')>Zoom In</option>
                    <option value="zoom-out" @selected($selectedAnimation === 'zoom-out')>Zoom Out</option>
                    <option value="fade" @selected($selectedAnimation === 'fade')>Fade</option>
                    <option value="pan-left" @selected($selectedAnimation === 'pan-left')>Pan Left</option>
                    <option value="pan-right" @selected($selectedAnimation === 'pan-right')>Pan Right</option>
                    <option value="none" @selected($selectedAnimation === 'none')>None</option>
                </select>
                <p class="mt-2 text-xs text-slate-500">
                    Used by hero slider and reusable for future page sections.
                </p>
                @error('section_animation')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="media_uploads" class="form-label">Upload section images</label>
                <input
                    id="media_uploads"
                    type="file"
                    name="media_uploads[]"
                    multiple
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    class="form-input @error('media_uploads') form-error @enderror @error('media_uploads.*') form-error @enderror"
                >
                <p class="mt-2 text-xs text-slate-500">
                    Max 10 images per section. Hero uses these images as a slider. Files are stored in this section folder automatically.
                </p>
                @error('media_uploads')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('media_uploads.*')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div><label for="extra_data" class="form-label">Extra data JSON</label><textarea id="extra_data" name="extra_data" rows="10" class="form-textarea font-mono text-sm @error('extra_data') form-error @enderror">{{ old('extra_data', $extraDataJson) }}</textarea>@error('extra_data')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <label class="inline-flex items-center gap-3"><input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked(old('is_active', $pageSection->is_active))><span class="text-sm font-medium text-slate-700">Active section</span></label>
            <div><label for="sort_order" class="form-label">Sort order</label><input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $pageSection->sort_order) }}" class="form-input @error('sort_order') form-error @enderror">@error('sort_order')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        </div>

        <div class="flex gap-3 border-t pt-5"><button type="submit" class="btn-primary">Update Page Section</button><a href="{{ route('admin.page-sections.index') }}" class="btn-secondary">Cancel</a></div>
    </form>

    <div class="mt-8 border-t pt-6">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Section images</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $pageSection->media->count() }} / 10 uploaded for this section.
                </p>
            </div>
        </div>

        @if($pageSection->media->count())
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($pageSection->media as $media)
                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <img src="{{ $media->url }}" class="h-32 w-full rounded-lg object-cover" alt="{{ $media->alt ?? $pageSection->title ?? 'Section image' }}">
                        <p class="mt-2 break-all text-xs text-slate-500">{{ $media->path }}</p>
                        <form method="POST" action="{{ route('admin.page-sections.media.destroy', $media) }}" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Delete this section image?')" class="w-full rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                                Delete Image
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <div class="flex min-h-32 items-center justify-center rounded-xl bg-slate-100 text-sm text-slate-400">
                No section images uploaded yet.
            </div>
        @endif
    </div>
</div>
@endsection
