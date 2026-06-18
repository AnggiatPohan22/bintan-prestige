<div class="space-y-4" x-data="galleryBlock({{ json_encode($block->data ?? []) }})">

    {{-- Hidden inputs — always submitted, one set per image (kept in sync via x-model) --}}
    <template x-for="(img, i) in images" :key="'h' + i">
        <span class="hidden">
            <input type="hidden" :name="`data[images][${i}][src]`"     x-model="img.src">
            <input type="hidden" :name="`data[images][${i}][alt]`"     x-model="img.alt">
            <input type="hidden" :name="`data[images][${i}][caption]`" x-model="img.caption">
        </span>
    </template>

    {{-- ============ IMAGES ============ --}}
    <section class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
        <header class="mb-3 flex items-center justify-between gap-2">
            <h4 class="text-sm font-semibold text-slate-700">
                <i class="fa-solid fa-images mr-1 text-slate-400"></i>
                Images
                <span class="ml-1 font-normal text-slate-400" x-text="'(' + images.length + ')'"></span>
            </h4>
            <div class="flex gap-2">
                <label class="admin-btn-primary cursor-pointer px-3 py-1.5 text-xs">
                    <i class="fa-solid fa-upload mr-1"></i><span>Upload</span>
                    <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" multiple
                           class="hidden" x-on:change="uploadBatch($event)">
                </label>
                <button type="button" x-on:click="addImage()" class="admin-btn-soft px-3 py-1.5 text-xs">
                    <i class="fa-solid fa-link mr-1"></i>Add URL
                </button>
            </div>
        </header>

        {{-- Upload progress --}}
        <div x-show="uploadTotal > 0" x-cloak class="mb-3 rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-2 text-xs">
            <div class="flex items-center justify-between">
                <span class="font-semibold text-indigo-700">
                    Uploading <span x-text="uploadDone"></span> / <span x-text="uploadTotal"></span>
                </span>
                <span x-show="uploadDone === uploadTotal" class="font-bold text-green-600">Done!</span>
            </div>
            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-indigo-200">
                <div class="h-1.5 rounded-full bg-indigo-500 transition-all duration-300"
                     :style="'width:' + (uploadTotal > 0 ? Math.round(uploadDone / uploadTotal * 100) : 0) + '%'"></div>
            </div>
        </div>
        <p x-show="uploadError" x-text="uploadError" class="mb-2 text-xs text-red-500" x-cloak></p>

        {{-- Thumbnail grid --}}
        <div x-show="images.length" x-cloak class="grid grid-cols-3 gap-2 sm:grid-cols-5 md:grid-cols-6">
            <template x-for="(img, i) in images" :key="i">
                <div
                    class="group relative aspect-square cursor-pointer overflow-hidden rounded-lg border bg-white transition"
                    :class="selected === i ? 'border-indigo-500 ring-2 ring-indigo-300' : 'border-slate-200 hover:border-slate-300'"
                    x-on:click="select(i)"
                >
                    <img x-show="img.src" :src="imgPreview(img.src)" class="h-full w-full object-cover" x-cloak>
                    <div x-show="!img.src" class="flex h-full w-full items-center justify-center" x-cloak>
                        <i class="fa-solid fa-image text-slate-300"></i>
                    </div>
                    <span class="absolute left-1 top-1 rounded bg-black/60 px-1.5 text-[10px] font-semibold text-white"
                          x-text="i + 1"></span>
                    <button type="button" x-on:click.stop="removeImage(i)"
                            class="absolute right-1 top-1 grid h-5 w-5 place-items-center rounded-full bg-red-500/90
                                   text-white opacity-0 transition group-hover:opacity-100"
                            title="Remove">
                        <i class="fa-solid fa-xmark text-[10px]"></i>
                    </button>
                </div>
            </template>
        </div>

        <p x-show="images.length === 0" x-cloak class="py-4 text-center text-sm text-slate-400">
            No images yet — click <span class="font-medium">Upload</span> to add several at once,
            or <span class="font-medium">Add URL</span> to paste a link.
        </p>

        {{-- Detail editor for the selected image --}}
        <template x-if="selected !== null && images[selected]">
            <div class="mt-3 rounded-lg border border-indigo-200 bg-white p-3">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-600">
                        Image <span x-text="selected + 1"></span> of <span x-text="images.length"></span>
                    </span>
                    <div class="flex items-center gap-0.5">
                        <button type="button" x-on:click="move(selected, -1)" :disabled="selected === 0"
                                class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 disabled:opacity-30" title="Move left">
                            <i class="fa-solid fa-arrow-left text-xs"></i>
                        </button>
                        <button type="button" x-on:click="move(selected, 1)" :disabled="selected === images.length - 1"
                                class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 disabled:opacity-30" title="Move right">
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </button>
                        <button type="button" x-on:click="removeImage(selected)"
                                class="rounded p-1.5 text-red-400 hover:bg-red-50 hover:text-red-600" title="Remove">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                        <button type="button" x-on:click="selected = null"
                                class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Close">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-md bg-slate-100">
                        <img x-show="images[selected].src" :src="imgPreview(images[selected].src)"
                             class="h-full w-full object-cover" x-cloak>
                    </div>
                    <div class="flex-1 space-y-1.5">
                        <input type="text" x-model="images[selected].src"     class="admin-input text-xs" placeholder="Image path or URL">
                        <input type="text" x-model="images[selected].alt"     class="admin-input text-xs" placeholder="Alt text (accessibility)">
                        <input type="text" x-model="images[selected].caption" class="admin-input text-xs" placeholder="Caption (optional)">
                    </div>
                </div>
            </div>
        </template>
    </section>

    {{-- ============ DISPLAY OPTIONS ============ --}}
    <section class="rounded-xl border border-slate-200 p-4">
        <h4 class="mb-3 text-sm font-semibold text-slate-700">
            <i class="fa-solid fa-sliders mr-1 text-slate-400"></i>Display Options
        </h4>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="admin-form-label">Images per row</label>
                <select name="data[columns]" class="admin-input">
                    @foreach([1, 2, 3, 4] as $col)
                        <option value="{{ $col }}" @selected(($block->data['columns'] ?? 3) == $col)>{{ $col }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Extra rows become a carousel.</p>
            </div>
            <div>
                <label class="admin-form-label">Spacing</label>
                <select name="data[gap]" class="admin-input">
                    @foreach(['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'] as $val => $label)
                        <option value="{{ $val }}" @selected(($block->data['gap'] ?? 'md') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="admin-form-label">Aspect ratio</label>
                <select name="data[aspect_ratio]" class="admin-input">
                    @foreach(['auto' => 'Auto', 'square' => 'Square (1:1)', '16-9' => 'Wide (16:9)', '4-3' => 'Standard (4:3)'] as $val => $label)
                        <option value="{{ $val }}" @selected(($block->data['aspect_ratio'] ?? 'auto') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4">
            <label class="admin-form-label">Gallery caption</label>
            <input type="text" name="data[caption]" value="{{ old('data.caption', $block->data['caption'] ?? '') }}"
                   class="admin-input" placeholder="Overall caption shown below the gallery (optional)">
        </div>

        <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2">
            <label class="flex items-center gap-2">
                <input type="hidden" name="data[show_captions]" value="0">
                <input type="checkbox" name="data[show_captions]" value="1" class="rounded border-slate-300"
                       @checked($block->data['show_captions'] ?? false)>
                <span class="text-sm text-slate-700">Per-image captions</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="hidden" name="data[lightbox_enabled]" value="0">
                <input type="checkbox" name="data[lightbox_enabled]" value="1" class="rounded border-slate-300"
                       @checked($block->data['lightbox_enabled'] ?? true)>
                <span class="text-sm text-slate-700">Lightbox on click</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="hidden" name="data[autoplay]" value="0">
                <input type="checkbox" name="data[autoplay]" value="1" class="rounded border-slate-300"
                       @checked($block->data['autoplay'] ?? false)>
                <span class="text-sm text-slate-700">Autoplay carousel</span>
            </label>
        </div>
    </section>

</div>

@once
@push('scripts')
<script>
function galleryBlock(blockData) {
    return {
        images: (blockData.images && blockData.images.length)
            ? blockData.images.map(img => ({ src: img.src || '', alt: img.alt || '', caption: img.caption || '' }))
            : [],
        selected: null,
        uploadTotal: 0,
        uploadDone: 0,
        uploadError: '',

        imgPreview(src) {
            if (!src) return '';
            return src.startsWith('http') ? src : '/storage/' + src;
        },

        select(i) { this.selected = (this.selected === i) ? null : i; },

        addImage() {
            this.images.push({ src: '', alt: '', caption: '' });
            this.selected = this.images.length - 1;
        },

        removeImage(i) {
            this.images.splice(i, 1);
            if (this.selected === i) this.selected = null;
            else if (this.selected !== null && this.selected > i) this.selected--;
        },

        move(i, dir) {
            const j = i + dir;
            if (j < 0 || j >= this.images.length) return;
            [this.images[i], this.images[j]] = [this.images[j], this.images[i]];
            this.images = [...this.images];
            this.selected = j;
        },

        async uploadBatch(event) {
            const files = Array.from(event.target.files);
            if (!files.length) return;

            this.uploadError = '';
            this.uploadTotal = files.length;
            this.uploadDone  = 0;

            const token = document.querySelector('meta[name="csrf-token"]').content;

            for (const file of files) {
                try {
                    const form = new FormData();
                    form.append('files[]', file);
                    form.append('_token', token);

                    const res  = await fetch('{{ route('admin.media.upload-batch') }}', { method: 'POST', body: form });
                    const data = await res.json();

                    if (data.success && data.files && data.files.length) {
                        this.images.push({ src: data.files[0].path, alt: '', caption: '' });
                    } else {
                        this.uploadError = data.message || 'One or more uploads failed.';
                    }
                } catch (e) {
                    this.uploadError = 'Upload failed. Check your connection.';
                }
                this.uploadDone++;
            }

            event.target.value = '';
            setTimeout(() => { this.uploadTotal = 0; this.uploadDone = 0; }, 3000);
        },
    };
}
</script>
@endpush
@endonce
