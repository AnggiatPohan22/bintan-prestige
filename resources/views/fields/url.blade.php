<input
    id="{{ $inputId }}"
    type="url"
    name="{{ $inputName }}"
    value="{{ old($inputName, $value ?? '') }}"
    placeholder="{{ $field->settings['placeholder'] ?? 'https://' }}"
    class="admin-input font-mono {{ $hasError ? 'border-red-400' : '' }}"
    {{ $field->is_required ? 'required' : '' }}
>
