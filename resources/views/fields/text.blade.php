<input
    id="{{ $inputId }}"
    type="text"
    name="{{ $inputName }}"
    value="{{ old($inputName, $value ?? '') }}"
    placeholder="{{ $field->settings['placeholder'] ?? '' }}"
    @if(!empty($field->settings['maxlength'])) maxlength="{{ $field->settings['maxlength'] }}" @endif
    class="admin-input {{ $hasError ? 'border-red-400' : '' }}"
    {{ $field->is_required ? 'required' : '' }}
>
