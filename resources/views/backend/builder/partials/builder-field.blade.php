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
