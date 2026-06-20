<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Mail\ContactFormSubmission as ContactFormSubmissionMail;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ContactFormController extends Controller
{
    public function submit(Request $request, FormDefinition $form)
    {
        // Honeypot check: if _hp is filled, silently succeed without saving.
        if ($request->filled('_hp')) {
            return back()->with('contact_success_' . $form->id, $form->successMessage());
        }

        $rules = $this->buildRules($form);
        $validated = $request->validate($rules);

        // Handle file uploads.
        $data = [];
        foreach ($form->fields as $field) {
            $name = $field['name'];

            if ($field['type'] === 'file' && $request->hasFile($name)) {
                $path = $request->file($name)->store('form-submissions', 'public');
                $data[$name] = $path;
            } elseif (isset($validated[$name])) {
                $data[$name] = $validated[$name];
            }
        }

        $submission = FormSubmission::create([
            'form_id'    => $form->id,
            'data'       => $data,
            'is_read'    => false,
            'ip_address' => $request->ip(),
        ]);

        // Send notification email.
        $to = $form->notificationEmail();
        if (filled($to)) {
            try {
                Mail::to($to)->send(new ContactFormSubmissionMail($form, $submission));
            } catch (\Throwable) {
                // Email failures must not block the user.
            }
        }

        return back()->with('contact_success_' . $form->id, $form->successMessage());
    }

    private function buildRules(FormDefinition $form): array
    {
        $rules = ['_hp' => ['nullable', 'string', 'max:0']];

        foreach ($form->fields as $field) {
            $name     = $field['name'];
            $required = $field['required'] ?? false;
            $type     = $field['type'] ?? 'text';

            $base = $required ? ['required'] : ['nullable'];

            $typeRules = match ($type) {
                'email'    => ['email:rfc'],
                'file'     => ['file', 'max:5120'],
                'checkbox' => ['boolean'],
                default    => ['string', 'max:10000'],
            };

            $rules[$name] = array_merge($base, $typeRules);
        }

        return $rules;
    }
}
