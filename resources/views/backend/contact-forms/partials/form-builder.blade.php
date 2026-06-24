@php
    $action = $form
        ? route('admin.forms.update', $form)
        : route('admin.forms.store');
    $method = $form ? 'PUT' : 'POST';
    $initialFields = $form
        ? json_encode($form->fields)
        : '[]';
    $settings = $form?->settings ?? [];
@endphp

<form
    method="POST"
    action="{{ $action }}"
    x-data="contactFormBuilder({{ $initialFields }})"
>
    @csrf
    @if($form) @method('PUT') @endif

    @if($errors->any())
        <div class="admin-alert-danger mb-4">
            <ul class="list-disc pl-5 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── Basic Info ──────────────────────────────────────────────────────── --}}
    <div class="admin-card mb-6">
        <div class="admin-card-header">
            <h2 class="text-base font-extrabold text-slate-100">Form Details</h2>
        </div>
        <div class="admin-card-body space-y-5">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="admin-form-label">Form Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $form?->name) }}" class="admin-input @error('name') border-red-300 @enderror" required>
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-form-label">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $form?->slug) }}" class="admin-input @error('slug') border-red-300 @enderror" placeholder="Auto-generated from name">
                    @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- ── Field Builder ───────────────────────────────────────────────────── --}}
    <div class="admin-card mb-6">
        <div class="admin-card-header flex items-center justify-between">
            <h2 class="text-base font-extrabold text-slate-100">Form Fields</h2>
            <button type="button" x-on:click="addField()" class="admin-btn-secondary text-sm">
                + Add Field
            </button>
        </div>
        <div class="admin-card-body">

            <template x-if="fields.length === 0">
                <p class="py-6 text-center text-sm text-slate-400">No fields yet. Click "+ Add Field" to start building.</p>
            </template>

            <div class="space-y-3">
                <template x-for="(field, index) in fields" :key="index">
                    <div class="rounded-xl border border-slate-200 bg-slate-800 p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400" x-text="'Field ' + (index + 1)"></span>
                            <button type="button" x-on:click="removeField(index)" class="text-xs text-red-500 hover:text-red-700">
                                <i class="fa-solid fa-trash mr-1"></i> Remove
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
                            <div>
                                <label class="admin-form-label text-xs">Type</label>
                                <select x-model="field.type" class="admin-input text-sm">
                                    <option value="text">Text</option>
                                    <option value="email">Email</option>
                                    <option value="phone">Phone</option>
                                    <option value="textarea">Textarea</option>
                                    <option value="select">Select</option>
                                    <option value="checkbox">Checkbox</option>
                                    <option value="file">File</option>
                                </select>
                            </div>
                            <div>
                                <label class="admin-form-label text-xs">Label <span class="text-red-500">*</span></label>
                                <input type="text" x-model="field.label" x-on:input="updateName(field)" class="admin-input text-sm" placeholder="e.g. Full Name">
                            </div>
                            <div>
                                <label class="admin-form-label text-xs">Field Name (ID)</label>
                                <input type="text" x-model="field.name" class="admin-input font-mono text-sm" placeholder="auto">
                            </div>
                            <div class="flex items-end gap-2">
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-300">
                                    <input type="checkbox" x-model="field.required" class="rounded border-slate-300">
                                    Required
                                </label>
                            </div>
                        </div>

                        <div class="mt-3" x-show="field.type === 'select'">
                            <label class="admin-form-label text-xs">Options (comma-separated)</label>
                            <input type="text" x-model="field.options" class="admin-input text-sm" placeholder="Option 1, Option 2, Option 3">
                        </div>
                    </div>
                </template>
            </div>

            {{-- Serialized hidden field --}}
            <input type="hidden" name="fields" :value="serializedFields">
        </div>
    </div>

    {{-- ── Settings ────────────────────────────────────────────────────────── --}}
    <div class="admin-card mb-6">
        <div class="admin-card-header">
            <h2 class="text-base font-extrabold text-slate-100">Form Settings</h2>
        </div>
        <div class="admin-card-body space-y-5">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="admin-form-label">Submit Button Label</label>
                    <input type="text" name="settings[submit_label]" value="{{ old('settings.submit_label', $settings['submit_label'] ?? 'Send Message') }}" class="admin-input" placeholder="Send Message">
                </div>
                <div>
                    <label class="admin-form-label">Notification Email</label>
                    <input type="email" name="settings[notification_email]" value="{{ old('settings.notification_email', $settings['notification_email'] ?? '') }}" class="admin-input" placeholder="Leave empty to disable email notifications">
                </div>
                <div class="md:col-span-2">
                    <label class="admin-form-label">Success Message</label>
                    <input type="text" name="settings[success_message]" value="{{ old('settings.success_message', $settings['success_message'] ?? "Thank you! We'll get back to you soon.") }}" class="admin-input">
                </div>
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="admin-btn-primary">
            {{ $form ? 'Save Changes' : 'Create Form' }}
        </button>
        <a href="{{ route('admin.forms.index') }}" class="admin-btn-secondary">Cancel</a>
    </div>
</form>

<script>
function contactFormBuilder(initial) {
    return {
        fields: Array.isArray(initial) ? initial.map(f => ({
            type: f.type || 'text',
            label: f.label || '',
            name: f.name || '',
            required: Boolean(f.required),
            options: Array.isArray(f.options) ? f.options.join(', ') : (f.options || ''),
        })) : [],

        addField() {
            this.fields.push({ type: 'text', label: '', name: '', required: false, options: '' });
        },

        removeField(index) {
            this.fields.splice(index, 1);
        },

        updateName(field) {
            field.name = (field.label || '')
                .toLowerCase()
                .replace(/[^a-z0-9\s]/g, '')
                .trim()
                .replace(/\s+/g, '_');
        },

        get serializedFields() {
            return JSON.stringify(this.fields.map(f => {
                const out = {
                    type: f.type,
                    name: f.name || ('field_' + Math.random().toString(36).slice(2, 6)),
                    label: f.label,
                    required: Boolean(f.required),
                };
                if (f.options && (f.type === 'select')) {
                    out.options = f.options.split(',').map(o => o.trim()).filter(o => o.length > 0);
                }
                return out;
            }));
        },
    };
}
</script>
