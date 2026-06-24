{{--
    Reusable single field control for the Block Settings panel (B3).
    Renders ONE control bound to an Alpine lvalue — works at the top level and
    inside repeater rows. The caller renders the top label (except toggle, which
    carries its own inline label).

    Params:
      $f     — Alpine variable name holding the field definition (e.g. 'field' | 'sub')
      $model — Alpine lvalue expression for the value (e.g. selectedNode().data[field.key])
--}}
@php($f = $f ?? 'field')

{{-- text / url --}}
<template x-if="{{ $f }}.type === 'text' || {{ $f }}.type === 'url'">
    <input
        :type="{{ $f }}.type === 'url' ? 'url' : 'text'"
        x-model="{{ $model }}"
        x-on:input="scheduleRefresh()"
        :maxlength="{{ $f }}.maxlength || null"
        :placeholder="{{ $f }}.placeholder || ''"
        class="builder-input"
    >
</template>

{{-- number --}}
<template x-if="{{ $f }}.type === 'number'">
    <input
        type="number"
        x-model.number="{{ $model }}"
        x-on:input="scheduleRefresh()"
        :min="{{ $f }}.min ?? null"
        :max="{{ $f }}.max ?? null"
        class="builder-input"
    >
</template>

{{-- textarea --}}
<template x-if="{{ $f }}.type === 'textarea'">
    <textarea
        x-model="{{ $model }}"
        x-on:input="scheduleRefresh()"
        :rows="{{ $f }}.rows || 3"
        :placeholder="{{ $f }}.placeholder || ''"
        class="builder-input"
    ></textarea>
</template>

{{-- richtext (HTML; sanitized server-side on save) --}}
<template x-if="{{ $f }}.type === 'richtext'">
    <textarea
        x-model="{{ $model }}"
        x-on:input="scheduleRefresh()"
        :rows="{{ $f }}.rows || 6"
        :placeholder="{{ $f }}.placeholder || ''"
        class="builder-input font-mono text-xs"
    ></textarea>
</template>

{{-- select (static options or dynamic optionsFrom) --}}
<template x-if="{{ $f }}.type === 'select'">
    <select
        x-model="{{ $model }}"
        x-on:change="scheduleRefresh()"
        class="builder-input"
    >
        <template x-if="{{ $f }}.optionsFrom">
            <option value="" x-text="{{ $f }}.emptyLabel || '— Select —'"></option>
        </template>
        <template x-for="opt in selectOptions({{ $f }})" :key="opt.value">
            <option :value="opt.value" x-text="opt.label"></option>
        </template>
    </select>
</template>

{{-- color --}}
<template x-if="{{ $f }}.type === 'color'">
    <div class="flex items-center gap-2">
        <input type="color" x-model="{{ $model }}" x-on:input="scheduleRefresh()"
               class="h-9 w-12 shrink-0 cursor-pointer rounded border border-slate-700 bg-slate-800 p-0.5">
        <input type="text" x-model="{{ $model }}" x-on:input="scheduleRefresh()"
               placeholder="#hex" class="builder-input font-mono">
    </div>
</template>

{{-- range --}}
<template x-if="{{ $f }}.type === 'range'">
    <div class="flex items-center gap-2">
        <input type="range" x-model.number="{{ $model }}" x-on:input="scheduleRefresh()"
               :min="{{ $f }}.min ?? 0" :max="{{ $f }}.max ?? 100" :step="{{ $f }}.step ?? 1"
               class="w-full accent-amber-500">
        <span class="w-10 shrink-0 text-right text-xs text-slate-400" x-text="({{ $model }} ?? 0) + ({{ $f }}.suffix || '')"></span>
    </div>
</template>

{{-- toggle (renders its own inline label) --}}
<template x-if="{{ $f }}.type === 'toggle'">
    <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-300">
        <input type="checkbox" x-model="{{ $model }}" x-on:change="scheduleRefresh()"
               class="h-4 w-4 rounded border-slate-600 bg-slate-800 text-amber-500 focus:ring-amber-500">
        <span x-text="{{ $f }}.label"></span>
    </label>
</template>

{{-- image (path + upload + media library + preview) --}}
<template x-if="{{ $f }}.type === 'image'">
    <div>
        <div x-show="{{ $model }}" x-cloak class="mb-2">
            <img :src="mediaPreview({{ $model }})" alt="preview" class="h-24 w-full rounded-lg object-cover shadow">
        </div>
        <div class="flex gap-1.5">
            <input type="text" x-model="{{ $model }}" x-on:input="scheduleRefresh()"
                   :placeholder="{{ $f }}.placeholder || 'path or URL'" class="builder-input flex-1">
            <label class="flex cursor-pointer items-center rounded-lg border border-slate-700 px-2.5 text-xs text-slate-300 hover:border-slate-500 hover:text-white" title="Upload">
                <i class="fa-solid fa-upload"></i>
                <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden"
                       x-on:change="uploadInto($event, v => { {{ $model }} = v })">
            </label>
            <button type="button" title="Media Library"
                    class="flex items-center rounded-lg border border-slate-700 px-2.5 text-xs text-slate-300 hover:border-slate-500 hover:text-white"
                    x-on:click="pickImage(v => { {{ $model }} = v })">
                <i class="fa-solid fa-photo-film"></i>
            </button>
        </div>
    </div>
</template>

{{-- code (Custom CSS etc.) — monospace textarea, plain text --}}
<template x-if="{{ $f }}.type === 'code'">
    <textarea
        x-model="{{ $model }}"
        x-on:input="scheduleRefresh()"
        :rows="{{ $f }}.rows || 6"
        :placeholder="{{ $f }}.placeholder || ''"
        spellcheck="false"
        class="builder-input font-mono text-xs"
    ></textarea>
</template>

{{-- box (4-side margin / padding, px) --}}
<template x-if="{{ $f }}.type === 'box'">
    <div class="grid grid-cols-4 gap-1.5">
        <div>
            <input type="number" x-model.number="{{ $model }}.top" x-on:input="scheduleRefresh()" placeholder="0" class="builder-input px-1.5 text-center" title="Top">
            <p class="mt-0.5 text-center text-[9px] uppercase tracking-wide text-slate-400">Top</p>
        </div>
        <div>
            <input type="number" x-model.number="{{ $model }}.right" x-on:input="scheduleRefresh()" placeholder="0" class="builder-input px-1.5 text-center" title="Right">
            <p class="mt-0.5 text-center text-[9px] uppercase tracking-wide text-slate-400">Right</p>
        </div>
        <div>
            <input type="number" x-model.number="{{ $model }}.bottom" x-on:input="scheduleRefresh()" placeholder="0" class="builder-input px-1.5 text-center" title="Bottom">
            <p class="mt-0.5 text-center text-[9px] uppercase tracking-wide text-slate-400">Bottom</p>
        </div>
        <div>
            <input type="number" x-model.number="{{ $model }}.left" x-on:input="scheduleRefresh()" placeholder="0" class="builder-input px-1.5 text-center" title="Left">
            <p class="mt-0.5 text-center text-[9px] uppercase tracking-wide text-slate-400">Left</p>
        </div>
    </div>
</template>

{{-- background (color + image + position/size/repeat/opacity) → data.background.* --}}
<template x-if="{{ $f }}.type === 'background'">
    <div class="space-y-3">
        {{-- Color --}}
        <div>
            <label class="builder-label">Background Color</label>
            <div class="flex items-center gap-2">
                <input type="color" x-model="{{ $model }}.color" x-on:input="scheduleRefresh()"
                       class="h-9 w-12 shrink-0 cursor-pointer rounded border border-slate-700 bg-slate-800 p-0.5">
                <input type="text" x-model="{{ $model }}.color" x-on:input="scheduleRefresh()"
                       placeholder="transparent or #hex" class="builder-input font-mono">
            </div>
        </div>

        {{-- Image --}}
        <div>
            <label class="builder-label">Background Image</label>
            <div x-show="{{ $model }}.image" x-cloak class="mb-2">
                <img :src="mediaPreview({{ $model }}.image)" alt="preview" class="h-20 w-full rounded-lg object-cover shadow">
            </div>
            <div class="flex gap-1.5">
                <input type="text" x-model="{{ $model }}.image" x-on:input="scheduleRefresh()"
                       placeholder="path or URL" class="builder-input flex-1">
                <label class="flex cursor-pointer items-center rounded-lg border border-slate-700 px-2.5 text-xs text-slate-300 hover:border-slate-500 hover:text-white" title="Upload">
                    <i class="fa-solid fa-upload"></i>
                    <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden"
                           x-on:change="uploadInto($event, v => { {{ $model }}.image = v })">
                </label>
                <button type="button" title="Media Library"
                        class="flex items-center rounded-lg border border-slate-700 px-2.5 text-xs text-slate-300 hover:border-slate-500 hover:text-white"
                        x-on:click="pickImage(v => { {{ $model }}.image = v })">
                    <i class="fa-solid fa-photo-film"></i>
                </button>
            </div>
        </div>

        {{-- Position --}}
        <div>
            <label class="builder-label">Position</label>
            <select x-model="{{ $model }}.position" x-on:change="scheduleRefresh()" class="builder-input">
                <option value="center">Center center</option>
                <option value="top center">Top center</option>
                <option value="bottom center">Bottom center</option>
                <option value="top left">Top left</option>
                <option value="top right">Top right</option>
                <option value="bottom left">Bottom left</option>
                <option value="bottom right">Bottom right</option>
            </select>
        </div>

        {{-- Size + Repeat --}}
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="builder-label">Display Size</label>
                <select x-model="{{ $model }}.size" x-on:change="scheduleRefresh()" class="builder-input">
                    <option value="cover">Cover</option>
                    <option value="contain">Contain</option>
                    <option value="auto">Auto</option>
                </select>
            </div>
            <div>
                <label class="builder-label">Repeat</label>
                <select x-model="{{ $model }}.repeat" x-on:change="scheduleRefresh()" class="builder-input">
                    <option value="no-repeat">No repeat</option>
                    <option value="repeat">Tile</option>
                    <option value="repeat-x">Repeat X</option>
                    <option value="repeat-y">Repeat Y</option>
                </select>
            </div>
        </div>

        {{-- Opacity --}}
        <div>
            <label class="builder-label">Opacity</label>
            <div class="flex items-center gap-2">
                <input type="range" min="0" max="100" step="5" x-model.number="{{ $model }}.opacity" x-on:input="scheduleRefresh()" class="w-full accent-amber-500">
                <span class="w-10 shrink-0 text-right text-xs text-slate-400" x-text="({{ $model }}.opacity ?? 100) + '%'"></span>
            </div>
        </div>
    </div>
</template>
