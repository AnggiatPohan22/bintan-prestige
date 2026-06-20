<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FormDefinitionController extends Controller
{
    public function index()
    {
        $forms = FormDefinition::withCount([
            'submissions',
            'submissions as unread_count' => fn ($q) => $q->where('is_read', false),
        ])->orderBy('name')->get();

        return view('backend.contact-forms.index', compact('forms'));
    }

    public function create()
    {
        return view('backend.contact-forms.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                       => ['required', 'string', 'max:255'],
            'slug'                       => ['nullable', 'string', 'max:255', 'unique:form_definitions,slug'],
            'fields'                     => ['required', 'string'],
            'settings.submit_label'      => ['nullable', 'string', 'max:100'],
            'settings.success_message'   => ['nullable', 'string', 'max:1000'],
            'settings.notification_email'=> ['nullable', 'email', 'max:255'],
        ]);

        $slug   = Str::slug($data['slug'] ?? $data['name']);
        $fields = $this->parseFields($data['fields']);

        FormDefinition::create([
            'name'     => $data['name'],
            'slug'     => $slug,
            'fields'   => $fields,
            'settings' => $data['settings'] ?? [],
        ]);

        return redirect()->route('admin.forms.index')
            ->with('success', "Form \"{$data['name']}\" created.");
    }

    public function edit(FormDefinition $form)
    {
        return view('backend.contact-forms.edit', compact('form'));
    }

    public function update(Request $request, FormDefinition $form)
    {
        $data = $request->validate([
            'name'                        => ['required', 'string', 'max:255'],
            'slug'                        => ['nullable', 'string', 'max:255', "unique:form_definitions,slug,{$form->id}"],
            'fields'                      => ['required', 'string'],
            'settings.submit_label'       => ['nullable', 'string', 'max:100'],
            'settings.success_message'    => ['nullable', 'string', 'max:1000'],
            'settings.notification_email' => ['nullable', 'email', 'max:255'],
        ]);

        $slug   = Str::slug($data['slug'] ?? $data['name']);
        $fields = $this->parseFields($data['fields']);

        $form->update([
            'name'     => $data['name'],
            'slug'     => $slug,
            'fields'   => $fields,
            'settings' => $data['settings'] ?? [],
        ]);

        return redirect()->route('admin.forms.edit', $form)
            ->with('success', 'Form updated.');
    }

    public function destroy(FormDefinition $form)
    {
        $name = $form->name;
        $form->delete();

        return redirect()->route('admin.forms.index')
            ->with('success', "Form \"{$name}\" deleted.");
    }

    private function parseFields(string $json): array
    {
        try {
            $fields = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $fields = [];
        }

        return collect($fields)->filter(fn ($f) => ! empty($f['label']))->map(fn ($f) => [
            'type'     => in_array($f['type'] ?? 'text', ['text', 'email', 'phone', 'select', 'textarea', 'checkbox', 'file'], true)
                            ? $f['type']
                            : 'text',
            'name'     => Str::snake(preg_replace('/[^a-z0-9_\s]/i', '', $f['name'] ?? $f['label']) ?: 'field'),
            'label'    => trim($f['label']),
            'required' => (bool) ($f['required'] ?? false),
            'options'  => isset($f['options']) && is_array($f['options'])
                            ? array_values(array_filter(array_map('trim', $f['options'])))
                            : [],
        ])->values()->all();
    }
}
