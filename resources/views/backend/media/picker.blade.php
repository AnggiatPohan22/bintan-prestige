<!DOCTYPE html>
<html lang="en" @if(($adminUiMode ?? 'dark') === 'light') data-admin-mode="light" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Media Picker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    {{-- FOUC guard + theme vars: same treatment as layouts/admin so the picker
         iframe follows the dashboard light/dark appearance. --}}
    <style>
        html, body { background: {{ ($adminUiMode ?? 'dark') === 'light' ? '#F8FAFC' : ($adminAppearance->bg_base ?? '#020617') }}; }
        [x-cloak]  { display: none !important; }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @isset($adminAppearanceCss)
    <style id="admin-appearance-vars">
        {!! $adminAppearanceCss !!}
    </style>
    @endisset
</head>
<body class="admin-body p-4">
    {{-- Selection metadata is posted to the parent and routed to the requesting block field. --}}
    <div x-data="mediaPicker()">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-base font-bold text-admin-secondary">Select media</h1>

            <form method="GET" action="{{ route('admin.media.index') }}" class="flex flex-wrap gap-2">
                <input type="hidden" name="picker" value="1">
                @if($pickerHint ?? null)<input type="hidden" name="hint" value="{{ $pickerHint }}">@endif
                <input type="text" name="search" value="{{ $search }}" class="admin-input w-44" placeholder="Search...">
                <select name="collection" class="admin-select w-40" x-on:change="$el.form.submit()" aria-label="Filter by collection">
                    <option value="">All collections</option>
                    @foreach($collections as $key => $label)
                        <option value="{{ $key }}" @selected($activeCollection === $key)>{{ $label }}</option>
                    @endforeach
                    <option value="uncategorized" @selected($activeCollection === 'uncategorized')>Uncategorized</option>
                </select>
                <button type="submit" class="admin-btn-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>

        {{-- Upload straight into the Media Library, then auto-select the result --}}
        <div class="mb-4 flex flex-wrap items-center gap-2 rounded-xl border border-dashed border-admin bg-admin-card px-4 py-3"
             :class="dragging ? 'border-indigo-400' : ''"
             x-on:dragover.prevent="dragging = true"
             x-on:dragleave.prevent="dragging = false"
             x-on:drop.prevent="dragging = false; upload($event.dataTransfer.files)">
            <i class="fa-solid fa-cloud-arrow-up text-admin-secondary"></i>
            <span class="text-xs font-semibold text-admin-secondary">Upload new — saved to Media Library in:</span>
            <select x-model="uploadCollection" class="admin-select w-36 py-1.5 text-xs" aria-label="Upload collection">
                @foreach($collections as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <label class="admin-btn-primary cursor-pointer px-3 py-1.5 text-xs">
                <span x-text="uploading ? 'Uploading…' : 'Choose file'"></span>
                <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" multiple class="hidden"
                       x-on:change="upload($event.target.files); $event.target.value = ''">
            </label>
            <span class="text-[11px] text-admin-secondary">or drag &amp; drop · jpg, png, gif, webp · max 5 MB</span>
            <p x-show="error" x-text="error" class="w-full text-xs text-red-500" x-cloak></p>
        </div>

        @include('backend.media.partials.grid', ['media' => $media])
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('mediaPicker', () => ({
                uploading: false,
                dragging: false,
                error: '',
                uploadCollection: @js($pickerHint ?? config('media.default_collection', 'general')),

                select(payload) {
                    window.parent.postMessage({ type: 'media-selected', media: payload }, window.location.origin);
                },

                async upload(fileList) {
                    const files = Array.from(fileList || []);
                    if (!files.length || this.uploading) return;

                    this.uploading = true;
                    this.error = '';

                    try {
                        const form = new FormData();
                        files.slice(0, 10).forEach((file) => form.append('files[]', file));
                        form.append('collection', this.uploadCollection);

                        const res = await fetch(@js(route('admin.media.store')), {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: form,
                        });
                        const data = await res.json();

                        if (!res.ok || !data.success) {
                            this.error = data.message || 'Upload failed. Check file type and size.';
                            return;
                        }

                        // Single upload: hand the file straight back to the field.
                        // Multiple: reload the grid so the user can pick.
                        if (data.media.length === 1) {
                            this.select(data.media[0]);
                        } else {
                            window.location.reload();
                        }
                    } catch (e) {
                        this.error = 'Upload failed. Please try again.';
                    } finally {
                        this.uploading = false;
                    }
                },
            }));
        });
    </script>
</body>
</html>
