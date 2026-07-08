{{--
    Media-Library-first image field (Phase 6.1 S4).
    Renders preview + path input + Upload (registers a Media record via
    uploadQuick) + Media Library picker button. The collection prop routes
    the upload into media/{collection}/YYYY/MM and pre-selects the picker's
    upload destination.

    Usage:
    <x-admin.media-image-field name="image" :value="old('image', $model->image ?? '')"
        label="Image" collection="category" hint="Shown on the frontend card." />
--}}
@props([
    'name',
    'value' => '',
    'label' => 'Image',
    'collection' => 'general',
    'hint' => null,
])

@php
    $pickerTarget = 'media-field-'.md5($name.$collection);
@endphp

<div
    x-data="imageUploader(@js($value), @js($pickerTarget), '', '', @js($collection))"
    x-on:media-picker-selected.window="selectMedia($event.detail)"
>
    <label class="admin-form-label">{{ $label }}</label>

    {{-- Preview --}}
    <div x-show="preview" x-cloak class="mb-2">
        <img :src="preview" alt="Preview" class="h-32 w-full max-w-sm rounded-lg object-cover shadow">
    </div>

    <div class="flex gap-2">
        <input
            type="text"
            name="{{ $name }}"
            x-model="path"
            class="admin-input flex-1"
            placeholder="media/… or https://…"
        >
        <label class="admin-btn-soft cursor-pointer whitespace-nowrap px-4 py-2 text-sm">
            <span x-text="uploading ? 'Uploading…' : 'Upload'"></span>
            <input
                type="file"
                accept="image/jpeg,image/png,image/gif,image/webp"
                class="hidden"
                x-on:change="uploadImage($event)"
            >
        </label>
        <button type="button" class="admin-btn-secondary whitespace-nowrap px-4 py-2 text-sm"
                x-on:click="$dispatch('open-media-picker', { target: pickerTarget, collection: collection })">
            Media Library
        </button>
    </div>

    @if($hint)
        <p class="mt-1 text-xs text-admin-secondary">{{ $hint }}</p>
    @endif
    <p x-show="error" x-text="error" class="mt-1 text-xs text-red-500" x-cloak></p>
</div>

{{-- The picker modal + imageUploader() script register once per page. --}}
@once
    @include('backend.media.partials.picker-modal')
@endonce
