<div>
    <label class="admin-form-label">Divider Style</label>
    <select name="data[style]" class="admin-input">
        @foreach(['line' => 'Line (thin horizontal rule)', 'space' => 'Space (blank vertical gap)', 'gold-line' => 'Gold line (brand accent)'] as $val => $label)
            <option value="{{ $val }}" @selected(($block->data['style'] ?? 'line') === $val)>{{ $label }}</option>
        @endforeach
    </select>
</div>
