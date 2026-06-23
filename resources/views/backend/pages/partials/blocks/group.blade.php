<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="admin-form-label">Content Width</label>
        <select name="data[width]" class="admin-input">
            @foreach(['contained' => 'Contained', 'wide' => 'Wide', 'full' => 'Full width'] as $value => $label)
                <option value="{{ $value }}" @selected(($block->data['width'] ?? 'contained') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="admin-form-label">Vertical Spacing</label>
        <select name="data[spacing]" class="admin-input">
            @foreach(['none' => 'None', 'sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'] as $value => $label)
                <option value="{{ $value }}" @selected(($block->data['spacing'] ?? 'md') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<p class="mt-3 rounded-lg bg-indigo-50 px-3 py-2 text-sm text-indigo-700">
    Move blocks into this Group using their Parent Container control.
</p>
