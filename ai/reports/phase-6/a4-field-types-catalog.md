# A4 — `config/field-types.php` Field Type Catalog

> **Task:** A4 — scaffold the field type catalog (code-only, no DB, no gate)
> **Status:** ✅ DONE — 2026-06-30
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** none (code-only, no migration)
> **Skills:** backend-skill · field-types-skill (created here)

---

## What was built

### `config/field-types.php`
Central catalog of **18 field types** (the handoff §5 table listed 16 rows; date/datetime
and email/url were split into distinct entries because they have different casts,
input types, and validation rules). Each entry declares:

| Key              | Purpose |
|------------------|---------|
| `label`          | Human-readable name |
| `icon`           | Heroicons icon name |
| `category`       | `basic\|choice\|date_time\|media\|relational\|advanced` |
| `description`    | Admin type-picker copy |
| `cast`           | PHP cast for `ContentEntry.data` values |
| `is_filterable`  | Whether to project to `content_entry_index` (B5) |
| `sanitizer`      | `plaintext\|richtext\|null` (InlineContentSanitizer) |
| `settings_schema`| Sub-fields the admin configures per field instance (same array shape as blocks) |
| `validation_rules`| Base Laravel rules; `{setting_key}` resolved at B2 |
| `admin_partial`  | Blade path for admin input — stubs created at B3 |
| `render_partial` | Blade path for frontend render — stubs created at B11 |

**18 types:** text, textarea, richtext, number, email, url, toggle, select, radio,
checkbox, date, datetime, image, gallery, file, relationship, color, repeater.

### `app/Support/FieldTypeRegistry.php`
Thin static helper over `config('field-types')` (config-cached — never queried).
Follows the pattern of `PageTemplateRegistry`.

| Method               | Returns |
|----------------------|---------|
| `all()`              | Full catalog array |
| `keys()`             | All type keys |
| `exists(string)`     | bool |
| `get(string)`        | Definition array or null |
| `filterable()`       | Sub-catalog of filterable types only |
| `filterableKeys()`   | Filterable type keys |

### `tests/Feature/Phase6/A4FieldTypesCatalogTest.php`
4 tests (no DB needed — no `RefreshDatabase`):
- `test_all_required_types_are_present` — asserts all 18 keys exist and no extra types
- `test_each_type_has_all_required_keys` — asserts every entry has all 11 required keys, correct PHP types
- `test_filterable_types_match_specification` — asserts exactly the 11 filterable types per handoff §5
- `test_registry_helpers_work_correctly` — exists/get/keys/null-path

### `ai/skills/field-types-skill.md`
New skill file per §12 Sync Matrix rule ("Creates `config/field-types.php` (A4) → create `ai/skills/field-types-skill.md`").

---

## Report (AGENTS.md §11)

### Changed
- `config/field-types.php` — **new** — 18-type catalog (mirrors `config/blocks.php` convention).
- `app/Support/FieldTypeRegistry.php` — **new** — typed static helper wrapping `config('field-types')`.
- `tests/Feature/Phase6/A4FieldTypesCatalogTest.php` — **new** — 4 tests, 351 assertions, 0 DB queries.
- `ai/skills/field-types-skill.md` — **new** — field type authoring guide + registry API reference.

### Impact
- DB: none. Routes: none. Packages: none. Frontend: none.
- Config: new `config/field-types.php` (config-cacheable; never queried at runtime).
- Security: no new input surface; catalog is read-only code.
- Builder: untouched. Phase 5 registry untouched.

### Verification
- A4 tests: **4/4 pass**, 351 assertions.
- Full suite: **642/642 pass**, 3632 assertions. PHPStan level 5: **0 errors**.

### Rollback
`git revert <commit>` removes the catalog, registry, tests, and skill file; no migration
to reverse. If config cache is warm: `php artisan config:clear`.

### Next
- **Milestone 1 is now COMPLETE.** A0 → A1 → A2 → A3 → A4 all DONE.
- **Milestone 2 begins at B1 — Content Types module** (⚠️ schema gate: new table `content_types`).
  Owner must approve before the B1 migration is written.
- B2 (Field Groups + Fields) and B4 (Content Entries) are also schema-gated.
