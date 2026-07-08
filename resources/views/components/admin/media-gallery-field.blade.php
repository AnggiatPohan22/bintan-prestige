{{--
    Media-Library-first multi-image field (Phase 6.1 Stage 2).
    Lets the admin add several images by picking/uploading them through the
    Media Library, one at a time. Each addition appends a hidden `{name}[]`
    input carrying the storage-relative PATH, plus a removable thumbnail.
    Order = pick order. Upload inside the picker registers a Media record and
    auto-selects it back here.

    Usage:
    <x-admin.media-gallery-field
        name="gallery" collection="product" label="Gallery Images" :max="10" />
--}}
@props([
    'name',
    'collection' => 'gallery',
    'label' => 'Images',
    'hint' => null,
    'max' => 10,
])

@php
    $pickerTarget = 'media-gallery-'.md5($name.$collection);
@endphp

<div
    x-data="mediaGalleryField(@js($pickerTarget), @js($collection), @js((int) $max))"
    x-on:media-picker-selected.window="onSelected($event.detail)"
>
    <label class="admin-form-label">{{ $label }}</label>

    <div class="flex flex-wrap items-center gap-3">
        <template x-for="(item, index) in items" :key="item.path">
            <div class="relative h-24 w-24 overflow-hidden rounded-xl border border-admin bg-admin-card">
                <img :src="item.url" alt="" class="h-full w-full object-cover">
                <button type="button"
                        class="absolute right-1 top-1 grid h-6 w-6 place-items-center rounded-full bg-black/60 text-xs text-white hover:bg-black/80 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        x-on:click="removeAt(index)" aria-label="Remove image">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <input type="hidden" name="{{ $name }}[]" :value="item.path">
            </div>
        </template>

        <button type="button"
                x-show="items.length < max"
                class="grid h-24 w-24 place-items-center rounded-xl border-2 border-dashed border-admin text-admin-secondary transition hover:border-indigo-400 hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                x-on:click="$dispatch('open-media-picker', { target: pickerTarget, collection: collection })">
            <span class="text-center text-xs font-semibold leading-tight"><i class="fa-solid fa-photo-film mb-1 block text-lg"></i>Media Library</span>
        </button>
    </div>

    <p x-show="items.length >= max" x-cloak class="mt-2 text-xs font-semibold text-amber-600">
        Maximum {{ $max }} images reached.
    </p>
    @if($hint)
        <p class="admin-form-hint mt-2">{{ $hint }}</p>
    @endif
</div>

{{-- Shared picker modal + a single Alpine definition per page. --}}
@once
    @include('backend.media.partials.picker-modal')
@endonce

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('mediaGalleryField', (pickerTarget, collection, max) => ({
        pickerTarget,
        collection,
        max,
        items: [],

        onSelected(detail) {
            if (detail.target !== this.pickerTarget) return;
            const path = detail.media?.path;
            if (! path) return;
            if (this.items.some((item) => item.path === path)) return; // dedupe
            if (this.items.length >= this.max) return;
            this.items.push({
                path,
                url: detail.media.url || ('/storage/' + path),
            });
        },

        removeAt(index) {
            this.items.splice(index, 1);
        },
    }));
});
</script>
@endpush
@endonce
