@php
    $colorVal = old($inputName, $value ?? ($field->settings['default'] ?? '#000000'));
@endphp

<div
    x-data="{ color: @js($colorVal) }"
    class="flex items-center gap-3"
>
    <input
        type="color"
        :value="color"
        @input="color = $event.target.value"
        class="h-10 w-16 cursor-pointer rounded border border-slate-200 p-0.5"
    >

    <input
        id="{{ $inputId }}"
        type="text"
        name="{{ $inputName }}"
        x-model="color"
        pattern="^#[0-9A-Fa-f]{6}$"
        maxlength="7"
        placeholder="#000000"
        class="admin-input w-32 font-mono uppercase {{ $hasError ? 'border-red-400' : '' }}"
        {{ $field->is_required ? 'required' : '' }}
    >

    <div
        class="h-10 w-10 rounded-lg border border-slate-200 shadow-sm"
        :style="'background-color:' + color"
        aria-hidden="true"
    ></div>
</div>
