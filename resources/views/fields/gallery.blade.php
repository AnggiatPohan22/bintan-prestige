@php
    $rawIds = old($inputName, $value ?? []);
    $mediaIds  = array_filter((array) $rawIds);
    $mediaItems = [];
    if (!empty($mediaIds)) {
        $mediaItems = \App\Models\Media::whereIn('id', $mediaIds)
            ->get(['id', 'url', 'name'])
            ->map(fn ($m) => ['id' => $m->id, 'url' => $m->url, 'name' => $m->name])
            ->toArray();
    }
@endphp

<div
    x-data="{
        items: @js($mediaItems),
        remove(id) { this.items = this.items.filter(i => i.id !== id); },
        onMediaSelected(selected) {
            const incoming = Array.isArray(selected) ? selected : [selected];
            incoming.forEach(m => {
                if (!this.items.find(i => i.id === m.id)) {
                    this.items.push({ id: m.id, url: m.url, name: m.name });
                }
            });
        }
    }"
    class="field-media-gallery"
>
    {{-- Hidden inputs, one per selected image --}}
    <template x-for="item in items" :key="item.id">
        <input type="hidden" name="{{ $inputName }}[]" :value="item.id">
    </template>

    {{-- Previews --}}
    <div x-show="items.length > 0" class="mb-3 flex flex-wrap gap-2">
        <template x-for="item in items" :key="item.id">
            <div class="group relative">
                <img :src="item.url" :alt="item.name" class="h-24 w-24 rounded-lg border object-cover shadow-sm">
                <button
                    type="button"
                    class="absolute right-1 top-1 hidden h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white text-xs group-hover:flex"
                    @click="remove(item.id)"
                    aria-label="Remove"
                >&times;</button>
            </div>
        </template>
    </div>

    <button
        type="button"
        class="admin-btn-soft"
        @click="$dispatch('open-media-library', { callback: 'onMediaSelected', multiple: true })"
    >
        Add Images
    </button>
</div>
