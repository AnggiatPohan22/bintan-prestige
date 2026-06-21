<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="md:col-span-2">
        <label class="admin-form-label">Heading Text</label>
        <input
            type="text"
            name="data[text]"
            value="{{ old('data.text', $block->data['text'] ?? '') }}"
            class="admin-input"
            maxlength="500"
            placeholder="Section heading"
        >
    </div>

    <div>
        <label class="admin-form-label">Semantic Level</label>
        <select name="data[level]" class="admin-input">
            @foreach(['h2' => 'H2 — Main section', 'h3' => 'H3 — Subsection', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6'] as $value => $label)
                <option value="{{ $value }}" @selected(($block->data['level'] ?? 'h2') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="admin-form-label">Alignment</label>
        <select name="data[alignment]" class="admin-input">
            @foreach(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'] as $value => $label)
                <option value="{{ $value }}" @selected(($block->data['alignment'] ?? 'left') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
