@php
    $checked = (bool) old($inputName, $value ?? false);
    $onLabel  = $field->settings['on_label']  ?? 'Yes';
    $offLabel = $field->settings['off_label'] ?? 'No';
@endphp

{{-- Hidden input ensures false is submitted when unchecked. --}}
<input type="hidden" name="{{ $inputName }}" value="0">

<label class="inline-flex cursor-pointer items-center gap-3">
    <input
        id="{{ $inputId }}"
        type="checkbox"
        name="{{ $inputName }}"
        value="1"
        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
        @checked($checked)
    >
    <span class="text-sm text-admin-secondary">
        <span x-show="!$el.previousElementSibling.checked">{{ $offLabel }}</span>
        <span x-show="$el.previousElementSibling.checked" style="display:none">{{ $onLabel }}</span>
    </span>
</label>
