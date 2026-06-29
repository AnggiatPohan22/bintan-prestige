# Field Types Skill (Phase 6)

## Purpose
Reference and authoring guide for the Phase 6 field type catalog.
Read this when working on field groups, fields, content entries, or the
field rendering engine.

## Catalog location
`config/field-types.php` — source of truth.
Always read via `FieldTypeRegistry` (config-cached; never queried).

## Registry API
```php
use App\Support\FieldTypeRegistry;

FieldTypeRegistry::all()             // array<string, array> — full catalog
FieldTypeRegistry::keys()            // array<int, string>   — type keys
FieldTypeRegistry::exists('text')    // bool
FieldTypeRegistry::get('text')       // array|null — single type definition
FieldTypeRegistry::filterable()      // array<string, array> — is_filterable=true only
FieldTypeRegistry::filterableKeys()  // array<int, string>
```

## Catalog structure (per type)

| Key              | Type    | Purpose |
|------------------|---------|---------|
| `label`          | string  | Human-readable name in admin type-picker |
| `icon`           | string  | Heroicons icon name (same as config/blocks.php) |
| `category`       | string  | `basic \| choice \| date_time \| media \| relational \| advanced` |
| `description`    | string  | One-line explanation for the admin UI |
| `cast`           | string  | PHP cast for stored value (`string\|integer\|float\|boolean\|array\|date\|datetime`) |
| `is_filterable`  | bool    | `true` → value projected to `content_entry_index` on entry save |
| `sanitizer`      | ?string | `'plaintext'` or `'richtext'` → `InlineContentSanitizer`; `null` = no sanitization |
| `settings_schema`| array   | Sub-fields an editor configures per field instance (same format as `fields` in blocks) |
| `validation_rules`| array  | Base Laravel rules; `{setting_key}` resolved at B2 field validation |
| `admin_partial`  | string  | Blade path for admin input (e.g. `fields.text`) — created at B3 |
| `render_partial` | string  | Blade path for frontend render (e.g. `content.fields.text`) — created at B11 |

## Current 18 types

| Key          | Category    | Cast     | Filterable | Sanitizer  |
|--------------|-------------|----------|------------|------------|
| text         | basic       | string   | ✅          | plaintext  |
| textarea     | basic       | string   | ✅          | plaintext  |
| richtext     | basic       | string   | —           | richtext   |
| number       | basic       | float    | ✅          | —          |
| email        | basic       | string   | ✅          | plaintext  |
| url          | basic       | string   | ✅          | plaintext  |
| toggle       | choice      | boolean  | ✅          | —          |
| select       | choice      | string   | ✅          | —          |
| radio        | choice      | string   | ✅          | —          |
| checkbox     | choice      | array    | —           | —          |
| date         | date_time   | date     | ✅          | —          |
| datetime     | date_time   | datetime | ✅          | —          |
| image        | media       | integer  | —           | —          |
| gallery      | media       | array    | —           | —          |
| file         | media       | integer  | —           | —          |
| relationship | relational  | array    | ✅ (by id)  | —          |
| color        | advanced    | string   | —           | plaintext  |
| repeater     | advanced    | array    | —           | —          |

## Adding a new field type

1. **Config** — add an entry to `config/field-types.php` with ALL required keys.
2. **Admin partial** — create `resources/views/backend/content/fields/{type}.blade.php`.
3. **Render partial** — create `resources/views/content/fields/{type}.blade.php`.
4. **Cast & sanitize** — wire in `ContentEntryService` (cast) and `InlineContentSanitizer` (sanitize) at save time.
5. **Validation** — add rules to the field's `UpdateContentEntryRequest` via `FieldTypeRegistry::get($type)['validation_rules']`.
6. **Filterable** — if `is_filterable = true`, ensure `ContentEntryService::indexField()` handles the new `cast` type.
7. **Test** — add the type key to `A4FieldTypesCatalogTest::EXPECTED_TYPES` and assert the partials exist.

## Filterable fields and content_entry_index

Types with `is_filterable = true` have their values projected to `content_entry_index`
on entry save (B5). The index stores one of `value_string`, `value_number`,
or `value_date` depending on the `cast`. Use `ContentQueryService` to filter
entries by these values — never raw JSON WHERE scans.

## Sanitization rule (NEVER skip)

| Sanitizer    | Call                                          |
|--------------|-----------------------------------------------|
| `'richtext'` | `InlineContentSanitizer::richtext($value)`    |
| `'plaintext'`| `InlineContentSanitizer::plaintext($value)`   |
| `null`       | No sanitization (internal/numeric/structured) |

Never output richtext field values with `{!! !!}` without the sanitizer call first.
`render_partial` views receive pre-sanitized values from `ContentEntryService`.

## Related files
- `config/field-types.php` — catalog definition
- `app/Support/FieldTypeRegistry.php` — registry helper
- `app/Support/InlineContentSanitizer.php` — sanitization
- `tests/Feature/Phase6/A4FieldTypesCatalogTest.php` — completeness guard
- `ai/reports/phase-6/a4-field-types-catalog.md` — task report
