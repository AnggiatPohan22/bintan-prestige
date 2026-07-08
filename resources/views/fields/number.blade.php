<input
    id="{{ $inputId }}"
    type="number"
    name="{{ $inputName }}"
    value="{{ old($inputName, $value ?? '') }}"
    @if(isset($field->settings['min'])) min="{{ $field->settings['min'] }}" @endif
    @if(isset($field->settings['max'])) max="{{ $field->settings['max'] }}" @endif
    @if(isset($field->settings['step'])) step="{{ $field->settings['step'] }}" @endif
    placeholder="{{ $field->settings['placeholder'] ?? '' }}"
    class="admin-input {{ $hasError ? 'border-red-400' : '' }}"
    {{ $field->is_required ? 'required' : '' }}
>
