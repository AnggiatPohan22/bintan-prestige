@php
    $mediaId  = (int) old($inputName, $value ?? 0) ?: null;
    $previewUrl = null;
    if ($mediaId) {
        $media = \App\Models\Media::find($mediaId);
        $previewUrl = $media?->url ?? null;
    }
@endphp

<div
    x-data="{
        mediaId: @js($mediaId),
        previewUrl: @js($previewUrl),
        clear() { this.mediaId = null; this.previewUrl = null; },
        onMediaSelected(media) { this.mediaId = media.id; this.previewUrl = media.url; }
    }"
    class="field-media-image"
>
    <input type="hidden" name="{{ $inputName }}" :value="mediaId ?? ''">

    {{-- Preview --}}
    <div x-show="previewUrl" class="mb-3">
        <img :src="previewUrl" alt="Selected image" class="h-40 w-auto rounded-lg border object-cover shadow-sm">
    </div>

    <div class="flex flex-wrap gap-2">
        <button
            type="button"
            class="admin-btn-soft"
            @click="$dispatch('open-media-library', { callback: 'onMediaSelected', multiple: false })"
        >
            <span x-text="mediaId ? 'Change Image' : 'Choose Image'"></span>
        </button>

        <button
            type="button"
            x-show="mediaId"
            class="admin-btn-danger"
            @click="clear()"
        >
            Remove
        </button>
    </div>
</div>
