<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="admin-form-label">Filter by Category</label>
        <select name="data[category_id]" class="admin-input">
            <option value="">All categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(($block->data['category_id'] ?? null) == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="admin-form-label">Filter by Destination</label>
        <select name="data[destination_id]" class="admin-input">
            <option value="">All destinations</option>
            @foreach($destinations as $destination)
                <option value="{{ $destination->id }}" @selected(($block->data['destination_id'] ?? null) == $destination->id)>
                    {{ $destination->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="admin-form-label">Number of Products</label>
        <input
            type="number"
            name="data[limit]"
            value="{{ old('data.limit', $block->data['limit'] ?? 6) }}"
            class="admin-input"
            min="3"
            max="12"
        >
        <p class="mt-1 text-xs text-admin-secondary">Between 3 and 12.</p>
    </div>

    <div class="flex items-center gap-3 pt-6">
        <input type="hidden" name="data[show_price]" value="0">
        <input
            type="checkbox"
            id="show_price_{{ $block->id }}"
            name="data[show_price]"
            value="1"
            class="rounded border-admin text-indigo-600 focus:ring-indigo-500"
            @checked($block->data['show_price'] ?? true)
        >
        <label for="show_price_{{ $block->id }}" class="text-sm font-medium text-admin-secondary">
            Show price on product cards
        </label>
    </div>
</div>
