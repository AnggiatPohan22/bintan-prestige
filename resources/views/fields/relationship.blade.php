@php
    /*
     * Stores one or multiple content entry IDs (B4+).
     * Settings: content_type (slug), multiple (bool), label_field (key to display).
     */
    $multiple    = (bool) ($field->settings['multiple'] ?? false);
    $ctSlug      = $field->settings['content_type'] ?? null;
    $rawVal      = old($inputName, $value ?? ($multiple ? [] : null));
    $selectedIds = $multiple ? array_filter((array) $rawVal) : [$rawVal];
    $nameAttr    = $multiple ? $inputName.'[]' : $inputName;
@endphp

<div
    x-data="{
        selected: @js(array_values(array_filter($selectedIds))),
        multiple: @js($multiple),
        remove(id) { this.selected = this.selected.filter(i => i != id); }
    }"
    class="field-relationship"
>
    {{-- Hidden inputs carry the IDs --}}
    <template x-if="!multiple">
        <input type="hidden" name="{{ $inputName }}" :value="selected[0] ?? ''">
    </template>
    <template x-if="multiple">
        <template x-for="id in selected" :key="id">
            <input type="hidden" name="{{ $nameAttr }}" :value="id">
        </template>
    </template>

    {{-- Selected tags --}}
    <div x-show="selected.length > 0" class="mb-2 flex flex-wrap gap-1">
        <template x-for="id in selected" :key="id">
            <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-xs text-indigo-700">
                <span x-text="'#' + id"></span>
                <button type="button" @click="remove(id)" class="ml-0.5 hover:text-red-500">&times;</button>
            </span>
        </template>
    </div>

    @if($ctSlug)
        <p class="mb-2 text-xs text-admin-secondary">
            Picking from: <span class="font-mono font-medium">{{ $ctSlug }}</span>
            — Entry picker available once content entries exist (B4+).
        </p>
    @else
        <p class="mb-2 text-xs text-amber-600">No content type configured. Edit this field's settings to select a source.</p>
    @endif

    {{-- Fallback: manual ID input for now --}}
    <div class="flex gap-2">
        <input
            type="number"
            placeholder="Enter Entry ID"
            class="admin-input w-40"
            @keydown.enter.prevent="
                const v = parseInt($event.target.value);
                if (v && (multiple || selected.length === 0)) {
                    if (!selected.includes(v)) selected.push(v);
                } else if (!multiple) {
                    selected = [v];
                }
                $event.target.value = '';
            "
        >
        <span class="mt-2 text-xs text-admin-secondary">Press Enter to add</span>
    </div>
</div>
