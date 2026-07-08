@php
    // datetime-local expects "Y-m-d\TH:i" format.
    $rawVal = old($inputName, $value ?? '');
    $dtVal  = '';
    if ($rawVal) {
        try {
            $dtVal = (new \DateTime((string) $rawVal))->format('Y-m-d\TH:i');
        } catch (\Exception) {
            $dtVal = '';
        }
    }
@endphp

<input
    id="{{ $inputId }}"
    type="datetime-local"
    name="{{ $inputName }}"
    value="{{ $dtVal }}"
    class="admin-input {{ $hasError ? 'border-red-400' : '' }}"
    {{ $field->is_required ? 'required' : '' }}
>
