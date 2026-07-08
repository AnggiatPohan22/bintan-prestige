<input
    id="{{ $inputId }}"
    type="email"
    name="{{ $inputName }}"
    value="{{ old($inputName, $value ?? '') }}"
    placeholder="{{ $field->settings['placeholder'] ?? 'e.g. contact@example.com' }}"
    class="admin-input {{ $hasError ? 'border-red-400' : '' }}"
    {{ $field->is_required ? 'required' : '' }}
>
