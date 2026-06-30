@props([
    'field',            // App\Models\Field instance
    'value'  => null,   // current stored value (any scalar / array)
    'namePrefix' => 'data', // wrapping key — stored as data[field_key]
])

@php
    /** @var App\Models\Field $field */
    // $errors is injected by middleware in real requests; guard for test/render contexts.
    $errorBag  = $errors ?? new \Illuminate\Support\MessageBag();
    $inputName = $namePrefix ? $namePrefix.'['.$field->key.']' : $field->key;
    $inputId   = 'field-'.$field->key;
    $partial   = 'fields.'.$field->type;
    $hasError  = $errorBag->has($inputName);
@endphp

<div
    class="field-input-group"
    data-field-key="{{ $field->key }}"
    data-field-type="{{ $field->type }}"
>
    {{-- Label --}}
    <div class="mb-1 flex items-center gap-1.5">
        <label for="{{ $inputId }}" class="admin-form-label mb-0">
            {{ $field->label }}
        </label>
        @if($field->is_required)
            <span class="text-red-500" aria-hidden="true">*</span>
        @endif
    </div>

    {{-- Instructions --}}
    @if($field->instructions)
        <p class="mb-2 text-sm text-admin-secondary">{{ $field->instructions }}</p>
    @endif

    {{-- Type-specific input partial --}}
    @include($partial, [
        'field'     => $field,
        'value'     => $value,
        'inputName' => $inputName,
        'inputId'   => $inputId,
        'hasError'  => $hasError,
    ])

    {{-- Validation error --}}
    @if($errorBag->has($inputName))
        <p class="mt-1.5 text-sm text-red-600">{{ $errorBag->first($inputName) }}</p>
    @endif
</div>
