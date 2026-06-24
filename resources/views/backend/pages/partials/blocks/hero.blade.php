<div
    class="grid grid-cols-1 gap-4 md:grid-cols-2"
    x-data="imageUploader(@js(old('data.image', $block->data['image'] ?? '')), 'hero-image-{{ $block->id }}')"
    x-on:media-picker-selected.window="selectMedia($event.detail)"
>
    <div class="md:col-span-2">
        <label class="admin-form-label">Title</label>
        <input type="text" name="data[title]" value="{{ old('data.title', $block->data['title'] ?? '') }}" class="admin-input" placeholder="Main headline">
    </div>

    <div class="md:col-span-2">
        <label class="admin-form-label">Subtitle</label>
        <input type="text" name="data[subtitle]" value="{{ old('data.subtitle', $block->data['subtitle'] ?? '') }}" class="admin-input" placeholder="Supporting text below headline">
    </div>

    <div class="md:col-span-2">
        <label class="admin-form-label">Hero Image</label>

        {{-- Preview --}}
        <div x-show="preview" x-cloak class="mb-2">
            <img :src="preview" alt="Preview" class="h-32 w-full rounded-lg object-cover shadow">
        </div>

        <div class="flex gap-2">
            <input
                type="text"
                name="data[image]"
                x-model="path"
                class="admin-input flex-1"
                placeholder="e.g. pages/hero.webp or https://..."
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
        <p class="mt-1 text-xs text-slate-400">Upload or paste a storage path / URL. Max 5MB. JPG, PNG, WebP.</p>
        <p x-show="error" x-text="error" class="mt-1 text-xs text-red-500" x-cloak></p>
    </div>

    <div>
        <label class="admin-form-label">CTA Button Text</label>
        <input type="text" name="data[cta_text]" value="{{ old('data.cta_text', $block->data['cta_text'] ?? '') }}" class="admin-input" placeholder="e.g. Explore Tours">
    </div>

    <div>
        <label class="admin-form-label">CTA Button URL</label>
        <input type="text" name="data[cta_url]" value="{{ old('data.cta_url', $block->data['cta_url'] ?? '') }}" class="admin-input" placeholder="/products">
    </div>

    {{-- Styling Section --}}
    <div class="md:col-span-2" x-data="{ heroStyleOpen: false }">
        <button
            type="button"
            x-on:click="heroStyleOpen = !heroStyleOpen"
            class="flex w-full items-center justify-between py-2 text-sm font-semibold text-slate-400"
        >
            <span><i class="fa-solid fa-sliders mr-1 text-slate-400"></i> Hero Styling</span>
            <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition-transform" :class="heroStyleOpen ? 'rotate-180' : ''"></i>
        </button>

        <div x-show="heroStyleOpen" x-cloak class="mt-3 grid grid-cols-1 gap-4 rounded-xl border border-slate-100 bg-slate-800 p-4 md:grid-cols-2">

            {{-- Background Color --}}
            <div>
                <label class="admin-form-label">Background Color</label>
                <div class="flex items-center gap-2">
                    <input
                        type="color"
                        name="data[background_color]"
                        value="{{ $block->data['background_color'] ?? '#0f0f0f' }}"
                        class="h-10 w-14 cursor-pointer rounded border border-slate-300 p-0.5"
                    >
                    <input
                        type="text"
                        name="data[background_color]"
                        value="{{ $block->data['background_color'] ?? '#0f0f0f' }}"
                        class="admin-input flex-1 font-mono text-sm"
                        placeholder="#0f0f0f"
                    >
                </div>
                <div class="mt-2 flex gap-1.5">
                    @foreach(['#0f0f0f' => 'Black', '#1e293b' => 'Slate 900', '#14100a' => 'Dark brown', '#D4AF37' => 'Gold'] as $hex => $name)
                        <button
                            type="button"
                            title="{{ $name }}"
                            onclick="this.closest('[x-data]').querySelectorAll('input[name=\'data[background_color]\']').forEach(el => el.value = '{{ $hex }}')"
                            class="h-6 w-6 rounded-full border border-slate-300 shadow-sm"
                            style="background-color: {{ $hex }}"
                        ></button>
                    @endforeach
                </div>
            </div>

            {{-- Min Height --}}
            <div>
                <label class="admin-form-label">Min Height</label>
                <select name="data[min_height]" class="admin-input">
                    @foreach(['small' => 'Small (400px)', 'medium' => 'Medium (600px)', 'large' => 'Large (80vh)'] as $val => $label)
                        <option value="{{ $val }}" @selected(($block->data['min_height'] ?? 'large') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Overlay Opacity --}}
            <div>
                <label class="admin-form-label">
                    Overlay Opacity: <span id="hero-overlay-label-{{ $block->id }}">{{ $block->data['overlay_opacity'] ?? 40 }}%</span>
                </label>
                <input
                    type="range"
                    name="data[overlay_opacity]"
                    value="{{ $block->data['overlay_opacity'] ?? 40 }}"
                    min="0"
                    max="90"
                    step="5"
                    class="w-full accent-indigo-500"
                    oninput="document.getElementById('hero-overlay-label-{{ $block->id }}').textContent = this.value + '%'"
                >
                <p class="mt-1 text-xs text-slate-400">Controls how dark the gradient overlay is over the image.</p>
            </div>

            {{-- Overlay Gradient Toggle --}}
            <div class="flex items-center gap-3 self-end pb-2">
                <input type="hidden" name="data[has_overlay]" value="0">
                <label class="flex cursor-pointer items-center gap-2">
                    <input
                        type="checkbox"
                        name="data[has_overlay]"
                        value="1"
                        class="rounded border-slate-300"
                        @checked(filter_var($block->data['has_overlay'] ?? true, FILTER_VALIDATE_BOOLEAN))
                    >
                    <span class="text-sm font-medium text-slate-300">Enable gradient overlay</span>
                </label>
                <p class="text-xs text-slate-400">Adds a dark gradient so text stays readable over bright images.</p>
            </div>

        </div>
    </div>

</div>
