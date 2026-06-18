{{--
    Shared background options partial — include at the bottom of every block form.
    Reads/writes: data[background][color|image|position|repeat|size|opacity]
--}}
@php
    $bg = $block->data['background'] ?? [];
    $bgColor    = $bg['color']    ?? '';
    $bgImage    = $bg['image']    ?? '';
    $bgPosition = $bg['position'] ?? 'center';
    $bgRepeat   = $bg['repeat']   ?? 'no-repeat';
    $bgSize     = $bg['size']     ?? 'cover';
    $bgOpacity  = $bg['opacity']  ?? 100;
@endphp

<div
    class="border-t border-slate-100 pt-4"
    x-data="{
        bgOpen: false,
        bgImage: '{{ $bgImage }}',
        bgUploading: false,
        bgError: '',
        get bgPreview() {
            if (!this.bgImage) return '';
            return this.bgImage.startsWith('http') ? this.bgImage : '/storage/' + this.bgImage;
        },
        async uploadBg(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.bgUploading = true;
            this.bgError = '';
            const form = new FormData();
            form.append('image', file);
            form.append('_token', document.querySelector('meta[name=csrf-token]').content);
            try {
                const res = await fetch('{{ route('admin.media.upload-quick') }}', { method: 'POST', body: form });
                const data = await res.json();
                if (data.success) { this.bgImage = data.path; }
                else { this.bgError = data.message || 'Upload failed.'; }
            } catch(e) { this.bgError = 'Upload failed.'; }
            finally { this.bgUploading = false; event.target.value = ''; }
        },
    }"
>
    <button
        type="button"
        x-on:click="bgOpen = !bgOpen"
        class="flex w-full items-center justify-between py-2 text-sm font-semibold text-slate-600"
    >
        <span>
            <i class="fa-solid fa-palette mr-1 text-slate-400"></i>
            Background & Styling
        </span>
        <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition-transform" :class="bgOpen ? 'rotate-180' : ''"></i>
    </button>

    <div x-show="bgOpen" x-cloak class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">

        {{-- Background Color --}}
        <div>
            <label class="admin-form-label">Background Color</label>
            <div class="flex items-center gap-2">
                <input
                    type="color"
                    name="data[background][color]"
                    value="{{ $bgColor ?: '#ffffff' }}"
                    class="h-10 w-14 cursor-pointer rounded border border-slate-300 p-0.5"
                >
                <input
                    type="text"
                    name="data[background][color]"
                    value="{{ $bgColor }}"
                    class="admin-input flex-1 font-mono text-sm"
                    placeholder="transparent or #hex"
                >
            </div>
            {{-- Brand palette presets --}}
            <div class="mt-2 flex gap-1.5">
                @foreach([
                    '#0f0f0f' => 'Black',
                    '#1e293b' => 'Slate 900',
                    '#f8fafc' => 'Off-white',
                    '#ffffff' => 'White',
                    '#D4AF37' => 'Gold',
                    '#f1f5f9' => 'Light gray',
                ] as $hex => $name)
                    <button
                        type="button"
                        title="{{ $name }}"
                        onclick="this.closest('[x-data]').querySelectorAll('input[name=\'data[background][color]\']').forEach(el => el.value = '{{ $hex }}')"
                        class="h-6 w-6 rounded-full border border-slate-300 shadow-sm"
                        style="background-color: {{ $hex }}"
                    ></button>
                @endforeach
            </div>
        </div>

        {{-- Background Image --}}
        <div>
            <label class="admin-form-label">Background Image</label>
            <div x-show="bgPreview" x-cloak class="mb-2">
                <img :src="bgPreview" alt="BG preview" class="h-16 w-full rounded-lg object-cover shadow">
            </div>
            <div class="flex gap-2">
                <input
                    type="text"
                    name="data[background][image]"
                    x-model="bgImage"
                    class="admin-input flex-1 text-sm"
                    placeholder="Path or URL"
                >
                <label class="admin-btn-soft cursor-pointer px-3 py-2 text-xs">
                    <span x-text="bgUploading ? '…' : 'Upload'"></span>
                    <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" x-on:change="uploadBg($event)">
                </label>
            </div>
            <p x-show="bgError" x-text="bgError" class="mt-1 text-xs text-red-500" x-cloak></p>
        </div>

        {{-- Position --}}
        <div>
            <label class="admin-form-label">Position</label>
            <select name="data[background][position]" class="admin-input">
                @foreach([
                    'center'        => 'Center',
                    'top center'    => 'Top center',
                    'bottom center' => 'Bottom center',
                    'top left'      => 'Top left',
                    'top right'     => 'Top right',
                    'bottom left'   => 'Bottom left',
                    'bottom right'  => 'Bottom right',
                ] as $val => $label)
                    <option value="{{ $val }}" @selected($bgPosition === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Size --}}
        <div>
            <label class="admin-form-label">Size</label>
            <select name="data[background][size]" class="admin-input">
                @foreach(['cover' => 'Cover', 'contain' => 'Contain', 'auto' => 'Auto'] as $val => $label)
                    <option value="{{ $val }}" @selected($bgSize === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Repeat --}}
        <div>
            <label class="admin-form-label">Repeat</label>
            <select name="data[background][repeat]" class="admin-input">
                @foreach(['no-repeat' => 'No repeat', 'repeat' => 'Tile', 'repeat-x' => 'Repeat X', 'repeat-y' => 'Repeat Y'] as $val => $label)
                    <option value="{{ $val }}" @selected($bgRepeat === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Opacity --}}
        <div>
            <label class="admin-form-label">
                Background Opacity: <span id="bg-opacity-label-{{ $block->id }}">{{ $bgOpacity }}%</span>
            </label>
            <input
                type="range"
                name="data[background][opacity]"
                value="{{ $bgOpacity }}"
                min="0"
                max="100"
                step="5"
                class="w-full accent-indigo-500"
                oninput="document.getElementById('bg-opacity-label-{{ $block->id }}').textContent = this.value + '%'"
            >
        </div>

    </div>
</div>
