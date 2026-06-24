<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="admin-form-label">Column Count</label>
        <select name="data[columns]" class="admin-input">
            @foreach([2, 3, 4] as $count)
                <option value="{{ $count }}" @selected((int) ($block->data['columns'] ?? 2) === $count)>{{ $count }} columns</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="admin-form-label">Gap</label>
        <select name="data[gap]" class="admin-input">
            @foreach(['none' => 'None', 'sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'] as $value => $label)
                <option value="{{ $value }}" @selected(($block->data['gap'] ?? 'md') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-2">
        <input type="hidden" name="data[stack_mobile]" value="0">
        <label class="flex items-center gap-2 text-sm font-medium text-slate-300">
            <input type="checkbox" name="data[stack_mobile]" value="1" class="rounded border-slate-300" @checked((bool) ($block->data['stack_mobile'] ?? true))>
            Stack columns vertically on mobile
        </label>
    </div>
</div>

<p class="mt-3 rounded-lg bg-indigo-50 px-3 py-2 text-sm text-indigo-700">
    Create one Group per column, then select this Columns block as each Group's parent. Extra Groups beyond the selected count wrap to a new row.
</p>
