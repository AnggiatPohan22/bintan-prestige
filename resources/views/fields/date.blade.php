@php
    // Normalise to Y-m-d string for the HTML date input.
    $dateVal = old($inputName, $value ?? '');
    if ($dateVal && ! is_string($dateVal)) {
        $dateVal = '';
    }
@endphp

<input
    id="{{ $inputId }}"
    type="date"
    name="{{ $inputName }}"
    value="{{ $dateVal }}"
    @if(!empty($field->settings['min'])) min="{{ $field->settings['min'] }}" @endif
    @if(!empty($field->settings['max'])) max="{{ $field->settings['max'] }}" @endif
    class="admin-input {{ $hasError ? 'border-red-400' : '' }}"
    {{ $field->is_required ? 'required' : '' }}
>
