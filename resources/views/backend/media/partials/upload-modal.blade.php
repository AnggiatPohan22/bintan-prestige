{{-- Upload modal — included inside the mediaLibrary Alpine root so it shares scope. --}}
<div x-show="uploadOpen" x-cloak class="fixed inset-0 z-[60]" style="display:none">
    <div class="absolute inset-0 bg-admin-card/40" x-on:click="closeUpload()"
         x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"></div>

    <div class="absolute left-1/2 top-1/2 w-[92%] max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-admin-card p-6 shadow-2xl"
         x-transition:enter="transition duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-bold text-admin-secondary">Upload media</h2>
            <button type="button" x-on:click="closeUpload()" class="grid h-8 w-8 place-items-center rounded-full text-admin-secondary hover:opacity-75">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- Collection (drives the storage folder: media/{collection}/YYYY/MM) --}}
        <div class="mb-4">
            <label for="upload-collection" class="admin-form-label">Collection</label>
            <select id="upload-collection" x-model="uploadCollection" class="admin-select">
                @foreach(config('media.collections', []) as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <p class="admin-form-hint mt-1">Organizes the file by position (Hero, Product, Logo, …).</p>
        </div>

        {{-- Drop zone --}}
        <label
            class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-10 text-center transition"
            :class="dragging ? 'border-violet-400 bg-violet-900/20' : 'border-admin hover:border-admin'"
            x-on:dragover.prevent="dragging = true"
            x-on:dragleave.prevent="dragging = false"
            x-on:drop.prevent="dragging = false; uploadFiles($event.dataTransfer.files)"
        >
            <i class="fa-solid fa-cloud-arrow-up mb-2 text-3xl text-admin-secondary"></i>
            <p class="text-sm font-semibold text-admin-secondary">Drag &amp; drop images here</p>
            <p class="mt-1 text-xs text-admin-secondary">or click to browse · jpg, png, gif, webp · max 5 MB each (up to 10)</p>
            <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" multiple class="hidden"
                   x-on:change="uploadFiles($event.target.files); $event.target.value = ''">
        </label>

        {{-- Progress --}}
        <div x-show="uploadTotal > 0" x-cloak class="mt-4">
            <div class="flex items-center justify-between text-sm">
                <span class="font-semibold text-indigo-700">Uploading <span x-text="uploadDone"></span> / <span x-text="uploadTotal"></span></span>
                <span x-show="uploadDone === uploadTotal" class="text-xs font-bold text-green-600">Done!</span>
            </div>
            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-indigo-100">
                <div class="h-2 rounded-full bg-indigo-500 transition-all duration-300"
                     :style="'width:' + (uploadTotal ? Math.round(uploadDone / uploadTotal * 100) : 0) + '%'"></div>
            </div>
        </div>

        <p x-show="uploadError" x-text="uploadError" class="mt-3 text-sm text-red-500" x-cloak></p>
    </div>
</div>
