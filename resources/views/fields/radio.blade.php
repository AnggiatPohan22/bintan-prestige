@php
    $options = $field->settings['options'] ?? [];
    $current = old($inputName, $value ?? '');
    $layout  = $field->settings['layout'] ?? 'vertical'; // vertical | horizontal
@endphp

<div class="{{ $layout === 'horizontal' ? 'flex flex-wrap gap-4' : 'flex flex-col gap-2' }}">
    @forelse($options as $idx => $opt)
        @php
            $val   = is_array($opt) ? ($opt['value'] ?? $opt['label']) : $opt;
            $label = is_array($opt) ? ($opt['label'] ?? $opt['value']) : $opt;
        @endphp
        <label class="inline-flex cursor-pointer items-center gap-2">
            <input
                type="radio"
                id="{{ $inputId }}-{{ $idx }}"
                name="{{ $inputName }}"
                value="{{ $val }}"
                class="border-slate-300 text-indigo-600 focus:ring-indigo-500"
                @checked((string) $current === (string) $val)
                {{ $field->is_required ? 'required' : '' }}
            >
            <span class="text-sm text-admin-secondary">{{ $label }}</span>
        </label>
    @empty
        <p class="text-xs text-amber-600">No options defined yet — add them in Field settings.</p>
    @endforelse
</div>
