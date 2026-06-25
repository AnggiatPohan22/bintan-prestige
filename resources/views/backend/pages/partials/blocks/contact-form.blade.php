@php
    $forms = \App\Models\FormDefinition::orderBy('name')->get(['id', 'name']);
@endphp

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="md:col-span-2">
        <label class="admin-form-label">Contact Form</label>
        <select name="data[form_definition_id]" class="admin-input">
            <option value="">— No form selected —</option>
            @foreach($forms as $formDef)
                <option value="{{ $formDef->id }}" @selected((int)($block->data['form_definition_id'] ?? 0) === $formDef->id)>
                    {{ $formDef->name }}
                </option>
            @endforeach
        </select>
        @if($forms->isEmpty())
            <p class="mt-1 text-xs text-amber-600">
                No forms created yet.
                <a href="{{ route('admin.forms.create') }}" class="underline">Create a form</a> first.
            </p>
        @else
            <p class="mt-1 text-xs text-admin-secondary">
                Select the form to embed on this page.
                <a href="{{ route('admin.forms.index') }}" class="text-indigo-600 hover:underline">Manage forms</a>
            </p>
        @endif
    </div>

    <div>
        <label class="admin-form-label">Section Title</label>
        <input type="text" name="data[title]" value="{{ old('data.title', $block->data['title'] ?? '') }}" class="admin-input" placeholder="e.g. Get In Touch">
    </div>

    <div>
        <label class="admin-form-label">Description</label>
        <input type="text" name="data[description]" value="{{ old('data.description', $block->data['description'] ?? '') }}" class="admin-input" placeholder="Optional subtitle">
    </div>
</div>
