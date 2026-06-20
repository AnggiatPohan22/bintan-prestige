# Step 6 — Contact Form Builder (Core Plugin)

**Date:** 2026-06-20
**Branch:** feature/phase-4-plugin-system
**Tests:** M1–M12 (12/12 pass) | Full suite: 548 tests, 2648 assertions, 0 failures

---

## Task: STEP 6 — Contact Form Builder

### Changed

**Migrations (new)**
- `database/migrations/2026_06_20_000003_create_plugin_contactform_form_definitions_table.php` — `form_definitions` table (id, name, slug unique, fields JSON, settings JSON nullable, timestamps)
- `database/migrations/2026_06_20_000004_create_plugin_contactform_form_submissions_table.php` — `form_submissions` table (id, form_id FK cascade, data JSON, is_read bool default false, ip_address nullable, timestamps)

**Models (new)**
- `app/Models/FormDefinition.php` — fillable, JSON casts for fields/settings, hasMany submissions, helper methods: unreadCount(), submitLabel(), successMessage(), notificationEmail()
- `app/Models/FormSubmission.php` — fillable, JSON cast for data, boolean cast for is_read, belongsTo FormDefinition

**Plugin Provider (new)**
- `app/Plugins/ContactForm/ContactFormServiceProvider.php` — extends PluginServiceProvider; onUninstall() deletes all FormDefinition records

**Controllers (new)**
- `app/Http/Controllers/Admin/FormDefinitionController.php` — index, create, store, edit, update, destroy; private parseFields() sanitizes JSON from builder
- `app/Http/Controllers/Admin/FormSubmissionController.php` — index (scoped to form), markRead, destroy
- `app/Http/Controllers/Frontend/ContactFormController.php` — submit(); honeypot check; buildRules() generates array-format validation rules from form fields; stores FormSubmission; sends ContactFormSubmissionMail; silently swallows mail failures

**Mail (new)**
- `app/Mail/ContactFormSubmission.php` — Mailable; subject "New Submission: {form->name}"; view emails.contact-form-submission

**Views — Backend (new)**
- `resources/views/backend/contact-forms/index.blade.php` — form list with unread submission badge
- `resources/views/backend/contact-forms/create.blade.php` — create page (includes form-builder partial)
- `resources/views/backend/contact-forms/edit.blade.php` — edit page (includes form-builder partial)
- `resources/views/backend/contact-forms/partials/form-builder.blade.php` — Alpine.js drag-and-drop field manager; supports text, email, phone, textarea, select, checkbox, file; serializes to hidden `<input name="fields">`
- `resources/views/backend/form-submissions/index.blade.php` — paginated submissions inbox; mark read/unread; delete; filter unread

**Views — Email (new)**
- `resources/views/emails/contact-form-submission.blade.php` — HTML notification email

**Views — Page Blocks (new)**
- `resources/views/backend/pages/partials/blocks/contact-form.blade.php` — block editor partial; dropdown to select form by ID; title + description fields
- `resources/views/frontend/blocks/contact-form.blade.php` — renders form HTML for all field types; honeypot hidden div; session-based success message

**Routes (modified)**
- `routes/admin.php` — added `/admin/forms` resource routes (index, create, store, edit, update, destroy, submissions.index named admin.forms.*) + `/admin/form-submissions` routes (markRead PATCH, destroy DELETE named admin.form-submissions.*)
- `routes/frontend.php` — added `POST /forms/{form:slug}/submit` → forms.submit

**Controllers — existing (modified)**
- `app/Http/Controllers/Admin/PageController.php` — edit() now passes `formDefinitions` to view

**Services — existing (modified)**
- `app/Services/PageBlockService.php` — added `contact_form` entry to defaultDataFor() and rulesFor()

**Views — existing (modified)**
- `resources/views/backend/pages/partials/block-editor.blade.php` — passes `$formDefinitions` to block @include calls
- `resources/views/backend/partials/sidebar.blade.php` — added "Forms" link (fa-envelope-open-text) in Content section

**Tests (new)**
- `tests/Feature/Phase4/ContactFormBuilderTest.php` — 12 tests (M1–M12)

---

### Bug Fixed During Implementation

**Issue:** `ContactFormController::buildRules()` used pipe-notation strings (`'string|max:10000'`, `'file|max:5120'`) as single elements in an array, causing `BadMethodCallException: Method validateString|max does not exist` on form submissions.

**Root cause:** When building rules as an array, each element must be a single rule string or Rule object. Pipe notation is only valid when the *entire* rules value is a single string (e.g. `'required|string|max:255'`). Mixing array + pipe strings breaks the validator.

**Fix:** Changed `match` to return arrays; merged with `array_merge`:
```php
$typeRules = match ($type) {
    'email'    => ['email:rfc'],
    'file'     => ['file', 'max:5120'],
    'checkbox' => ['boolean'],
    default    => ['string', 'max:10000'],
};
$rules[$name] = array_merge($base, $typeRules);
```

---

### Impact

- **DB:** 2 new tables — `form_definitions`, `form_submissions`
- **Routes:** admin `/admin/forms/*`, `/admin/form-submissions/*`; frontend `POST /forms/{form:slug}/submit`
- **Frontend:** new `contact_form` block type renderable on any page
- **Security:** honeypot anti-spam; dynamic validation per field definition; mail failures silently swallowed (no UX leak); no dangerous functions; no hardcoded content

---

### Rollback

```bash
# Drop new tables
php artisan migrate:rollback --step=2

# Revert modified files
git checkout app/Http/Controllers/Admin/PageController.php
git checkout app/Services/PageBlockService.php
git checkout resources/views/backend/pages/partials/block-editor.blade.php
git checkout resources/views/backend/partials/sidebar.blade.php
git checkout routes/admin.php
git checkout routes/frontend.php

# Remove new files
git clean -f app/Http/Controllers/Admin/FormDefinitionController.php \
              app/Http/Controllers/Admin/FormSubmissionController.php \
              app/Http/Controllers/Frontend/ContactFormController.php \
              app/Mail/ContactFormSubmission.php \
              app/Models/FormDefinition.php \
              app/Models/FormSubmission.php \
              app/Plugins/ContactForm/ \
              resources/views/backend/contact-forms/ \
              resources/views/backend/form-submissions/ \
              resources/views/emails/contact-form-submission.blade.php \
              resources/views/backend/pages/partials/blocks/contact-form.blade.php \
              resources/views/frontend/blocks/contact-form.blade.php \
              tests/Feature/Phase4/ContactFormBuilderTest.php
```

---

### Next

**STEP 7 — Analytics Dashboard** (next in schedule after STEP 6).
