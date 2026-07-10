# Task B7 — Phase 7 Menus & Terms Localized

**Phase:** 7 — Internationalization (i18n)
**Date:** 2026-07-10
**Branch:** `feature/phase-7-a1-foundation`
**Type:** Localize `MenuItem` + `Term` via the B1 sidecar (extend-only, **no schema change**).

---

## Task: B7 — Menu labels + taxonomy terms bilingual

Apply the sidecar pattern to the two remaining structural modules. **Menu
structure stays shared** across locales (A0 §3.5 Option A): only labels
translate. Terms translate name + description.

### Changed

**Models**
- `app/Models/MenuItem.php` — `use Translatable`; `$translatable = ['label']`;
  locale-aware `getLabelAttribute()`.
- `app/Models/Term.php` — `use Translatable`; `$translatable = ['name',
  'description']`; accessors for both.

**Frontend (menu tree)**
- `app/Services/MenuService.php`:
  - `buildMany()` eager-loads `translations` on root items **and** children
    (`->withTranslations()` — N+1 guard).
  - Cache is **keyed per locale** (`menu.tree.v2.{location}.{locale}`).
  - `forget()` invalidates every locale variant of the given location (plus the
    legacy unsuffixed key). `cacheKey()` helper centralises the key format.
- Frontend menu rendering (`normalizeTree()` reads `$item->label`) auto-localizes
  through the model accessor — zero Blade changes.

**Admin write side (per-locale sidecar persistence)**
- `MenuItemController::syncLabelTranslations()` after store/update.
- `TermController::syncTranslations()` after store/update (name + description).
- `Store/Update MenuItemRequest` — `translations.*.label` allow-listed.
- `Store/Update TermRequest` — `translations.*.name/description` allow-listed.

**Admin views**
- `resources/views/backend/menus/edit.blade.php` — drawer form gains a per-locale
  label card; Alpine `form.translations` state seeded on openEdit; base label
  bound to `form.label` (default column).
- `resources/views/backend/menus/partials/item-row.blade.php` — shows
  `getRawOriginal('label')` (admin is EN-only, §1.2), and passes
  `translations: {…}` to `openEdit()` so per-locale inputs are pre-filled.
- `resources/views/backend/taxonomies/terms/form.blade.php` — per-locale card
  (name + description) shown on the edit variant.

**Tests**
- `tests/Feature/Phase7/B7MenusAndTermsLocalizedTest.php` — 5 tests.

### Design notes
- **Structure shared, labels localized.** A single menu tree keeps every
  locale's navigation aligned; only text differs. Adding a locale later = one
  config entry + labels typed in.
- **Cache-per-locale mandatory.** Menu output is stored resolved, so caching
  without a locale suffix would freeze one locale's labels globally. `forget()`
  drops all variants at once so any menu edit invalidates every locale.
- **Admin item-row shows base column.** Even if the admin dashboard is ever
  browsed under `?lang=id` (`SetLocale` runs on frontend only), the row keeps
  showing the base label — the base column IS the default-locale label.

### Impact
- DB: **none** (uses B1's `translations` table).
- Routes: none.
- Frontend: header + footer menus + admin-provided widget nav render in the
  active locale where a translation exists; default output byte-identical.
- Security: admin-guarded; empty clears the row.

### Verification
- `B7MenusAndTermsLocalizedTest` — **5/5 pass** (MenuItem label localize +
  fallback; MenuService tree localizes; cache keyed per locale; Term localizes
  name + description).
- Full suite — **943/943 pass** (938 + 5 B7; no regression).
- PHPStan level 5 — **0 errors**.

### Rollback
`git revert <B7 commit>`. Base columns retain all default-locale labels/copy.

### Next
- **B8 — Admin translation UX polish:** translation-status columns on entry list
  views (●/○ per locale), locale filter, consistent switcher affordance across
  modules; finish content-field bridge locale-awareness (B9 remainder) +
  per-locale builder preview.
