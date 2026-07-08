@php
    $mediaId = (int) old($inputName, $value ?? 0) ?: null;
    $media   = $mediaId ? \App\Models\Media::find($mediaId) : null;
@endphp

<div
    x-data="{
        mediaId: @js($mediaId),
        fileName: @js($media?->name),
        fileUrl: @js($media?->url),
        clear() { this.mediaId = null; this.fileName = null; this.fileUrl = null; },
        onMediaSelected(m) { this.mediaId = m.id; this.fileName = m.name; this.fileUrl = m.url; }
    }"
    class="field-media-file"
>
    <input type="hidden" name="{{ $inputName }}" :value="mediaId ?? ''">

    <div x-show="fileName" class="mb-2 flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
        <i class="fa-solid fa-file text-slate-400" aria-hidden="true"></i>
        <a :href="fileUrl" :title="fileName" target="_blank" x-text="fileName" class="truncate text-indigo-600 hover:underline max-w-xs"></a>
    </div>

    <div class="flex flex-wrap gap-2">
        <button
            type="button"
            class="admin-btn-soft"
            @click="$dispatch('open-media-library', { callback: 'onMediaSelected', multiple: false })"
        >
            <span x-text="mediaId ? 'Change File' : 'Choose File'"></span>
        </button>
        <button type="button" x-show="mediaId" class="admin-btn-danger" @click="clear()">Remove</button>
    </div>
</div>
