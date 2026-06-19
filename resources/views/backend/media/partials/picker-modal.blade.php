<div
    x-data="{
        open: false,
        target: null,
        pickerUrl: @js(route('admin.media.index', ['picker' => 1])),
        init() {
            window.addEventListener('message', (event) => {
                if (event.origin !== window.location.origin || event.data?.type !== 'media-selected' || ! this.open) return;
                window.dispatchEvent(new CustomEvent('media-picker-selected', {
                    detail: { target: this.target, media: event.data.media },
                }));
                this.open = false;
                this.target = null;
            });
        },
    }"
    x-on:open-media-picker.window="target = $event.detail.target; open = true"
    x-on:keydown.escape.window="open = false; target = null"
>
    <div x-show="open" x-cloak class="fixed inset-0 z-[70]" style="display:none">
        <button type="button" class="absolute inset-0 bg-slate-950/50" aria-label="Close media picker"
                x-on:click="open = false; target = null"></button>

        <div class="absolute inset-4 overflow-hidden rounded-2xl bg-white shadow-2xl md:inset-10">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <h2 class="font-bold text-slate-900">Choose from Media Library</h2>
                <button type="button" class="admin-btn-secondary px-3 py-1.5 text-xs"
                        x-on:click="open = false; target = null">Close</button>
            </div>
            <iframe :src="pickerUrl" title="Media Library picker" class="h-[calc(100%-53px)] w-full border-0"></iframe>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
window.imageUploader = function(initialPath, pickerTarget, initialAlt = '', initialCaption = '') {
    return {
        path: initialPath,
        alt: initialAlt,
        caption: initialCaption,
        pickerTarget,
        uploading: false,
        error: '',
        get preview() {
            if (!this.path) return '';
            if (this.path.startsWith('http')) return this.path;
            return '/storage/' + this.path;
        },
        selectMedia(detail) {
            if (detail.target !== this.pickerTarget) return;
            this.path = detail.media.path || '';
            if (detail.media.alt) this.alt = detail.media.alt;
            if (detail.media.caption) this.caption = detail.media.caption;
        },
        async uploadImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.uploading = true;
            this.error = '';
            const form = new FormData();
            form.append('image', file);
            form.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            try {
                const response = await fetch(@js(route('admin.media.upload-quick')), { method: 'POST', body: form });
                const data = await response.json();
                if (response.ok && data.success) this.path = data.path;
                else this.error = data.message || 'Upload failed.';
            } catch (error) {
                this.error = 'Upload failed. Please try again.';
            } finally {
                this.uploading = false;
                event.target.value = '';
            }
        },
    };
};
</script>
@endpush
@endonce
