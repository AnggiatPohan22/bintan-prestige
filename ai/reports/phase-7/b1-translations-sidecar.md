# Task B1 — Phase 7 `translations` Sidecar + Translatable Trait

**Phase:** 7 — Internationalization (i18n)
**Date:** 2026-07-09
**Branch:** `feature/phase-7-a1-foundation`
**Type:** New table (⚠️ schema gate — shape pre-approved at A0 §3.4) + trait.

---

## Task: B1 — Attribute-translation engine

Build the polymorphic attribute-translation sidecar and the `Translatable` trait
that resolves per-locale values with fallback, plus the eager-load N+1 guard. The
trait is unit-tested in isolation and attached to its first real consumer.

### Changed

**New**
- `database/migrations/2026_07_09_000001_create_translations_table.php` — the
  `translations` table exactly per A0 §3.4:
  `id, translatable_type, translatable_id, locale(10), field(100), value(longtext),
  timestamps`, `unique(type,id,locale,field)` + `index(type,id,locale)`. Additive
  + reversible (`down()` drops it). Applied to dev DB (`migrate --force`, 284ms).
- `app/Models/Translation.php` — sidecar model, `morphTo translatable()`.
- `app/Models/Concerns/Translatable.php` — the trait:
  - `translations()` morphMany.
  - `translate($field, $locale=null)` — current/given locale with **fallback to
    the base column**; default locale always reads the base column.
  - `rawTranslation()` / `hasTranslation()` — in-memory reads over the loaded
    relation (no per-attribute query).
  - `setTranslation($field, $locale, $value)` — upsert; default locale writes the
    base column; empty value clears the row (fallback resumes).
  - `scopeWithTranslations($locale=null)` — eager-load current/given locale only
    (**the C2 N+1 guard**); `scopeWithAllTranslations()` for admin edit screens.
  - `bootTranslatable()` — purges translations on hard/force delete; **keeps them
    on soft delete** (for restore).

**Edited**
- `app/Models/SiteSetting.php` — attaches `Translatable` with
  `protected array $translatable = ['value']` as the trait's first consumer
  (extend-only; protected module untouched structurally). Inert until B2 wires the
  per-locale admin UI + frontend reads — no existing behaviour changes.

### Design notes
- **Base column = default locale.** The sidecar stores non-default locales only,
  so every protected module keeps working even if Phase 7 were reverted.
- **`$translatable` is declared on the model, not the trait.** A trait can't
  declare the property without fatally conflicting with a model's own default, so
  the trait reads `$this->translatable` directly (documented contract).
- **N+1:** reads go through the loaded `translations` relation; `withTranslations()`
  loads it once per query. Verified: loading 3 models + resolving each = **2
  queries total**, not per-attribute.

### Impact
- DB: **migration added** — `translations` (new table; no existing table touched).
- Routes: none.
- Frontend: none (nothing translated yet; B2 begins wiring reads).
- Security: none (values are plain text; they will escape like base values — C1).

### Verification
- `B1TranslatableTraitTest` — **12/12 pass** (resolution, fallback, empty-clear,
  non-translatable guard, current-locale, default→base write, upsert, N+1 guard =
  2 queries, locale-scoped load, hard-delete purge, soft-delete keep + force purge).
- Full suite — **891/891 pass** (866 baseline + 13 A2 + 12 B1; no regression;
  SiteSetting/GlobalSettings tests green).
- PHPStan level 5 — **0 errors**.

### Rollback
`git revert <B1 commit>` then `php artisan migrate:rollback --step=1` (drops
`translations`). No existing table or data affected.

### Next
- **B2 — Global chrome localized:** wire `SiteSetting` values (navigation, footer,
  CTA, identity, SEO defaults) through the sidecar; add per-locale tabs to the
  Global Assets text fields in admin; frontend chrome reads `translate('value')`
  for the current locale. First visible translation.
