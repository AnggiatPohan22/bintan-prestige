{{-- Upload modal — included inside the mediaLibrary Alpine root so it shares scope. --}}
<div x-show="uploadOpen" x-cloak class="fixed inset-0 z-[60]" style="display:none">
    <div class="absolute inset-0 bg-slate-900/40" x-on:click="closeUpload()"
         x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"></div>

    <div class="absolute left-1/2 top-1/2 w-[92%] max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-slate-800 p-6 shadow-2xl"
         x-transition:enter="transition duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-bold text-slate-100">Upload media</h2>
            <button type="button" x-on:click="closeUpload()" class="grid h-8 w-8 place-items-center rounded-full text-slate-400 hover:bg-slate-700 hover:text-slate-200">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- Drop zone --}}
        <label
            class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-10 text-center transition"
            :class="dragging ? 'border-violet-400 bg-violet-900/20' : 'border-slate-700 hover:border-slate-500'"
            x-on:dragover.prevent="dragging = true"
            x-on:dragleave.prevent="dragging = false"
            x-on:drop.prevent="dragging = false; uploadFiles($event.dataTransfer.files)"
        >
            <i class="fa-solid fa-cloud-arrow-up mb-2 text-3xl text-slate-400"></i>
            <p class="text-sm font-semibold text-slate-300">Drag &amp; drop images here</p>
            <p class="mt-1 text-xs text-slate-400">or click to browse · jpg, png, gif, webp · max 5 MB each (up to 10)</p>
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
