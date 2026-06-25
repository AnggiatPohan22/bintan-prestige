@extends('layouts.admin')

@section('content')

<div class="admin-page"
     x-data="mediaLibrary({
        storeUrl: '{{ route('admin.media.store') }}',
        updateBase: '{{ route('admin.media.index') }}',
        csrf: '{{ csrf_token() }}',
     })">

    {{-- Header --}}
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-extrabold text-admin-secondary">Media Library</h1>
            <p class="text-sm text-admin-secondary">Upload, search, and reuse images across the site.</p>
        </div>
        <button type="button" x-on:click="openUpload()" class="admin-btn-primary">
            <i class="fa-solid fa-cloud-arrow-up mr-1"></i> Upload
        </button>
    </div>

    @if($errors->has('media'))
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ $errors->first('media') }}
        </div>
    @endif

    @if($orphanCount > 0)
        <div class="mb-5 flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-amber-900">{{ $orphanCount }} unregistered media file(s) detected.</p>
                <p class="text-xs text-amber-700">Only files with no Media record and no known CMS reference will be removed.</p>
            </div>
            <form method="POST" action="{{ route('admin.media.orphans.destroy') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="admin-btn-secondary whitespace-nowrap" onclick="return confirm('Remove all confirmed orphaned media files?')">
                    Clean orphan files
                </button>
            </form>
        </div>
    @endif

    {{-- Toolbar: search + type filter --}}
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('admin.media.index') }}" class="flex w-full max-w-sm gap-2">
            @if($activeType)<input type="hidden" name="type" value="{{ $activeType }}">@endif
            <input type="text" name="search" value="{{ $search }}" class="admin-input" placeholder="Search by file name…">
            <button type="submit" class="admin-btn-secondary shrink-0"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>

        <div class="flex flex-wrap gap-1.5">
            <a href="{{ route('admin.media.index', ['search' => $search]) }}"
               class="rounded-full px-3 py-1.5 text-xs font-semibold {{ ! $activeType ? 'bg-indigo-600 text-white' : 'bg-admin-card text-admin-secondary hover:opacity-75' }}">
                All
            </a>
            @foreach($extensions as $ext)
                <a href="{{ route('admin.media.index', ['type' => $ext, 'search' => $search]) }}"
                   class="rounded-full px-3 py-1.5 text-xs font-semibold uppercase {{ $activeType === $ext ? 'bg-indigo-600 text-white' : 'bg-admin-card text-admin-secondary hover:opacity-75' }}">
                    {{ $ext }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Grid --}}
    @include('backend.media.partials.grid', ['media' => $media])

    {{-- Detail slide-over --}}
    <div x-show="detailOpen" x-cloak class="fixed inset-0 z-[55]" style="display:none">
        <div class="absolute inset-0 bg-admin-card/40" x-on:click="closeDetail()"
             x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"></div>

        <div class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-admin-card shadow-2xl"
             x-transition:enter="transition-transform duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition-transform duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">

            <div class="flex items-center justify-between border-b border-admin/50 px-5 py-4">
                <h2 class="font-bold text-admin-secondary">Media details</h2>
                <button type="button" x-on:click="closeDetail()" class="grid h-8 w-8 place-items-center rounded-full text-admin-secondary hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Close details">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5">
                {{-- Preview --}}
                <div class="overflow-hidden rounded-xl border border-admin bg-admin-card">
                    <img :src="selected.url" :alt="selected.alt || selected.name" class="max-h-64 w-full object-contain">
                </div>

                {{-- Copy URL --}}
                <div>
                    <label class="admin-form-label">URL</label>
                    <div class="flex gap-2">
                        <input type="text" :value="selected.url" readonly class="admin-input font-mono text-xs">
                        <button type="button" x-on:click="copyUrl()" class="admin-btn-secondary shrink-0">
                            <i class="fa-solid" :class="copied ? 'fa-check text-green-600' : 'fa-copy'"></i>
                        </button>
                    </div>
                </div>

                {{-- Edit alt + caption --}}
                <form method="POST" :action="`${updateBase}/${selected.id}`" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="admin-form-label">Alt text</label>
                        <input type="text" name="alt" x-model="selected.alt" class="admin-input" placeholder="Describe the image (accessibility + SEO)">
                    </div>
                    <div>
                        <label class="admin-form-label">Caption</label>
                        <input type="text" name="caption" x-model="selected.caption" class="admin-input" placeholder="Optional caption">
                    </div>
                    <button type="submit" class="admin-btn-primary w-full">Save details</button>
                </form>

                {{-- Meta --}}
                <dl class="space-y-1.5 rounded-xl bg-admin-card p-4 text-xs">
                    <div class="flex justify-between"><dt class="text-admin-secondary">File</dt><dd class="font-medium text-admin-secondary truncate pl-3" x-text="selected.name"></dd></div>
                    <div class="flex justify-between"><dt class="text-admin-secondary">Type</dt><dd class="font-medium text-admin-secondary" x-text="selected.ext"></dd></div>
                    <div class="flex justify-between"><dt class="text-admin-secondary">Size</dt><dd class="font-medium text-admin-secondary" x-text="selected.size"></dd></div>
                    <div class="flex justify-between"><dt class="text-admin-secondary">Dimensions</dt><dd class="font-medium text-admin-secondary" x-text="selected.dimensions"></dd></div>
                    <div class="flex justify-between"><dt class="text-admin-secondary">Uploaded by</dt><dd class="font-medium text-admin-secondary" x-text="selected.uploader"></dd></div>
                    <div class="flex justify-between"><dt class="text-admin-secondary">Date</dt><dd class="font-medium text-admin-secondary" x-text="selected.date"></dd></div>
                </dl>

                <div x-show="selected.usageCount > 0" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <p class="font-semibold">Used in <span x-text="selected.usageCount"></span> content location(s)</p>
                    <ul class="mt-2 list-disc space-y-1 pl-4 text-xs">
                        <template x-for="reference in selected.usageReferences" :key="`${reference.source}-${reference.record_id}-${reference.field}`">
                            <li x-text="`${reference.label} (${reference.field})`"></li>
                        </template>
                    </ul>
                    <p class="mt-2 text-xs">Remove these references before deleting the media.</p>
                </div>

                <div x-show="selected.missingFile" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    The stored file is missing. Deleting this entry will safely remove the stale database record.
                </div>
            </div>

            {{-- Delete --}}
            <div class="border-t border-admin/50 px-5 py-4">
                <form method="POST" :action="`${updateBase}/${selected.id}`" x-on:submit="return confirm('Delete this media file? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="admin-btn-danger w-full disabled:cursor-not-allowed disabled:opacity-50" :disabled="selected.usageCount > 0">
                        <i class="fa-solid fa-trash mr-1"></i> Delete media
                    </button>
                </form>
            </div>
        </div>
    </div>

    @include('backend.media.partials.upload-modal')

</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('mediaLibrary', (config) => ({
            updateBase: config.updateBase,
            detailOpen: false,
            uploadOpen: false,
            dragging: false,
            copied: false,
            selected: { id: null, path: '', url: '', name: '', alt: '', caption: '', size: '', dimensions: '', ext: '', date: '', uploader: '', usageCount: 0, usageReferences: [], missingFile: false },
            uploadTotal: 0,
            uploadDone: 0,
            uploadError: '',

            select(payload) {
                this.selected = { alt: '', caption: '', ...payload };
                this.copied = false;
                this.detailOpen = true;
            },
            closeDetail() { this.detailOpen = false; },

            openUpload() { this.uploadError = ''; this.uploadTotal = 0; this.uploadDone = 0; this.uploadOpen = true; },
            closeUpload() { this.uploadOpen = false; },

            async copyUrl() {
                try { await navigator.clipboard.writeText(this.selected.url); this.copied = true; setTimeout(() => this.copied = false, 1500); }
                catch (e) { /* clipboard unavailable */ }
            },

            async uploadFiles(fileList) {
                const files = Array.from(fileList || []);
                if (!files.length) return;

                this.uploadError = '';
                this.uploadTotal = files.length;
                this.uploadDone = 0;

                for (const file of files) {
                    try {
                        const form = new FormData();
                        form.append('files[]', file);
                        const res = await fetch(config.storeUrl, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': config.csrf, 'Accept': 'application/json' },
                            body: form,
                        });
                        if (!res.ok) throw new Error('upload failed');
                    } catch (e) {
                        this.uploadError = 'One or more uploads failed. Check file type and size.';
                    }
                    this.uploadDone++;
                }

                // Reload to show the newly registered media.
                setTimeout(() => window.location.reload(), 600);
            },
        }));
    });
</script>
@endpush

@endsection
