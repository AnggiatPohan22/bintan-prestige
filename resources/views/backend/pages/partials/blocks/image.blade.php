<div
    class="grid grid-cols-1 gap-4 md:grid-cols-2"
    x-data="imageUploader(
        @js(old('data.src', $block->data['src'] ?? '')),
        'image-block-{{ $block->id }}',
        @js(old('data.alt', $block->data['alt'] ?? '')),
        @js(old('data.caption', $block->data['caption'] ?? ''))
    )"
    x-on:media-picker-selected.window="selectMedia($event.detail)"
>
    <div class="md:col-span-2">
        <label class="admin-form-label">Image</label>

        {{-- Preview --}}
        <div x-show="preview" x-cloak class="mb-2">
            <img :src="preview" alt="Preview" class="h-32 w-full rounded-lg object-cover shadow">
        </div>

        <div class="flex gap-2">
            <input
                type="text"
                name="data[src]"
                x-model="path"
                class="admin-input flex-1"
                placeholder="e.g. pages/about-hero.webp or https://..."
            >
            <label class="admin-btn-soft cursor-pointer whitespace-nowrap px-4 py-2 text-sm">
                <span x-text="uploading ? 'Uploading…' : 'Upload'"></span>
                <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    x-on:change="uploadImage($event)"
                >
            </label>
            <button type="button" class="admin-btn-secondary whitespace-nowrap px-4 py-2 text-sm"
                    x-on:click="$dispatch('open-media-picker', { target: pickerTarget })">
                Media Library
            </button>
        </div>
        <p class="mt-1 text-xs text-slate-400">Upload or paste a storage path / URL. Max 5MB.</p>
        <p x-show="error" x-text="error" class="mt-1 text-xs text-red-500" x-cloak></p>
    </div>

    <div>
        <label class="admin-form-label">Alt Text</label>
        <input type="text" name="data[alt]" x-model="alt" class="admin-input" placeholder="Describe the image for accessibility">
    </div>

    <div>
        <label class="admin-form-label">Caption</label>
        <input type="text" name="data[caption]" x-model="caption" class="admin-input" placeholder="Optional caption below image">
    </div>

    <div>
        <label class="admin-form-label">Width</label>
        <select name="data[width_class]" class="admin-input">
            @foreach(['full' => 'Full width', 'half' => 'Half width', 'third' => 'One third'] as $val => $label)
                <option value="{{ $val }}" @selected(($block->data['width_class'] ?? 'full') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
