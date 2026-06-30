<textarea
    id="{{ $inputId }}"
    name="{{ $inputName }}"
    rows="{{ $field->settings['rows'] ?? 4 }}"
    placeholder="{{ $field->settings['placeholder'] ?? '' }}"
    @if(!empty($field->settings['maxlength'])) maxlength="{{ $field->settings['maxlength'] }}" @endif
    class="admin-textarea {{ $hasError ? 'border-red-400' : '' }}"
    {{ $field->is_required ? 'required' : '' }}
>{{ old($inputName, $value ?? '') }}</textarea>
