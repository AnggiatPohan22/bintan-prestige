@php
    /*
     * Repeater — stores an array of sub-row objects.
     * Sub-fields defined in $field->settings['sub_fields'] (array of field configs).
     * Full repeater rendering (nested field-input components) is wired at B4+.
     */
    $rows      = old($inputName, $value ?? []);
    $rows      = is_array($rows) ? array_values($rows) : [];
    $subFields = $field->settings['sub_fields'] ?? [];
@endphp

<div
    x-data="{ rows: @js($rows) }"
    class="rounded-lg border border-slate-200"
>
    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-2">
        <span class="text-sm font-medium text-admin-secondary">
            {{ $field->label }} rows
            <span class="ml-1 text-xs font-normal" x-text="'(' + rows.length + ')'"></span>
        </span>
        <button
            type="button"
            class="admin-btn-soft py-1 text-xs"
            @click="rows.push({})"
        >
            + Add Row
        </button>
    </div>

    @if(empty($subFields))
        <div class="px-4 py-3 text-sm text-amber-600">
            No sub-fields configured. Edit this field's settings to define sub-fields.
        </div>
    @else
        {{-- Row list --}}
        <div class="divide-y divide-slate-100">
            <template x-for="(row, idx) in rows" :key="idx">
                <div class="flex items-start gap-3 px-4 py-3">
                    <span class="mt-1 min-w-[1.5rem] text-center text-xs font-mono text-slate-400" x-text="idx + 1"></span>

                    <div class="flex-1 space-y-3">
                        @foreach($subFields as $sub)
                            @php
                                $subKey  = $sub['key'] ?? 'field';
                                $subName = "{$inputName}[' + idx + '][{$subKey}]";
                            @endphp
                            <div>
                                <label class="admin-form-label text-xs">{{ $sub['label'] ?? $subKey }}</label>
                                <input
                                    type="text"
                                    :name="`{{ $inputName }}[${idx}][{{ $subKey }}]`"
                                    :value="row.{{ $subKey }} ?? ''"
                                    @input="row.{{ $subKey }} = $event.target.value"
                                    class="admin-input text-sm"
                                >
                            </div>
                        @endforeach
                    </div>

                    <button
                        type="button"
                        class="mt-1 text-slate-400 hover:text-red-500"
                        @click="rows.splice(idx, 1)"
                        aria-label="Remove row"
                    >
                        <i class="fa-solid fa-trash-can text-sm"></i>
                    </button>
                </div>
            </template>

            <div x-show="rows.length === 0" class="px-4 py-4 text-center text-sm text-admin-secondary">
                No rows yet. Click "Add Row" to begin.
            </div>
        </div>
    @endif
</div>
