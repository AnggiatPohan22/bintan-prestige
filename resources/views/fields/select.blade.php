@php
    $options  = $field->settings['options'] ?? [];
    $current  = old($inputName, $value ?? '');
    $multiple = ($field->settings['multiple'] ?? false);
    $nameAttr = $multiple ? $inputName.'[]' : $inputName;
@endphp

<select
    id="{{ $inputId }}"
    name="{{ $nameAttr }}"
    class="admin-input {{ $hasError ? 'border-red-400' : '' }}"
    {{ $multiple ? 'multiple' : '' }}
    {{ $field->is_required ? 'required' : '' }}
>
    @unless($multiple)
        <option value="">— Select —</option>
    @endunless

    @foreach($options as $opt)
        @php
            $val   = is_array($opt) ? ($opt['value'] ?? $opt['label']) : $opt;
            $label = is_array($opt) ? ($opt['label'] ?? $opt['value']) : $opt;
            $sel   = $multiple
                ? in_array($val, (array) $current, true)
                : ((string) $current === (string) $val);
        @endphp
        <option value="{{ $val }}" @selected($sel)>{{ $label }}</option>
    @endforeach
</select>

@if(empty($options))
    <p class="mt-1 text-xs text-amber-600">No options defined yet — add them in Field settings.</p>
@endif
