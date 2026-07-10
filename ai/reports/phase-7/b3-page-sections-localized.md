# Task B3 — Phase 7 Page Sections Localized

**Phase:** 7 — Internationalization (i18n)
**Date:** 2026-07-09
**Branch:** `feature/phase-7-a1-foundation`
**Type:** Localize the protected `PageSection` module via the B1 sidecar (extend-only).

---

## Task: B3 — Page Sections localized

Make the home (and product-listing) page sections bilingual: the copy columns
translate per locale through the sidecar; media, layout and `extra_data` stay
shared. This completes milestone **M2** — a visitor can use the whole home page
in Indonesian.

### Changed

**Edited**
- `app/Models/Concerns/Translatable.php` — `translate()` now reads the base value
  from the **raw** stored attribute (`$this->attributes[$field]`) instead of
  `getAttribute()`, so a model can expose a localized accessor of the same name
  without recursing back into the trait.
- `app/Models/PageSection.php` — `use Translatable`;
  `$translatable = ['label','title','subtitle','description','button_text']`; and
  locale-aware accessors for those five columns. Reading `$section->title` now
  returns the current locale's value transparently (default locale → base column,
  zero query overhead).
- `app/Http/Controllers/Frontend/HomeController.php`,
  `app/Http/Controllers/Frontend/ProductController.php` — section queries add
  `->withTranslations()` (eager-load current locale — N+1 guard).
- `app/Http/Controllers/Admin/PageSectionController.php` — `update()` validates a
  `translations[{locale}][{field}]` block and persists it via `setTranslation`
  (default locale stays in the base columns). Empty clears the sidecar row.
- `resources/views/backend/page-sections/edit.blade.php` — per-locale translation
  card (one per active non-default locale) with the five copy fields, prefilled
  from `rawTranslation()`.

**New**
- `tests/Feature/Phase7/B3PageSectionsLocalizedTest.php` — 7 tests.

### Design notes
- **Transparent accessors.** Because `HomepageSectionData` and the section Blade
  read `$section->title` etc. directly, localizing via accessors means the whole
  render pipeline localizes with no changes to Support classes or views.
- **Zero default-locale overhead.** `translate()` short-circuits to the base
  column for the default locale before touching the relation, so English pages
  fire no translation queries (verified by test).
- **Scope.** The five scalar copy columns are translated. `extra_data` (JSON
  section-specific copy like hero search labels) is out of B3 scope — a later
  stretch via the same sidecar with per-key handling.

### Impact
- DB: **none** (uses B1's `translations` table).
- Routes: none (reuses the existing PageSection admin update route).
- Frontend: home + product-listing section copy render in the active locale where
  a translation exists; default-locale output is byte-identical to before.
- Security: admin-guarded; only the five allow-listed copy fields are persisted,
  only for active non-default locales; values escape via the existing Blade paths.

### Verification
- `B3PageSectionsLocalizedTest` — **7/7 pass** (accessor default/translated/
  fallback; `HomepageSectionData` localizes title; default-locale = 0 translation
  queries; admin persists translations without touching base; blank clears).
- Trait change regression — `B1TranslatableTraitTest` + `B2GlobalChromeLocalizedTest`
  still green (21/21).
- Full suite — **907/907 pass** (900 + 7 B3; no regression).
- PHPStan level 5 — **0 errors**.

### Rollback
`git revert <B3 commit>`. Base columns retain all default-locale copy; the module
works unchanged without the trait.

### Milestone
**M2 (Chrome speaks) COMPLETE** — B1 + B2 + B3 done: nav/footer/CTA/identity/SEO
defaults (B2) and home page sections (B3) all localize; a visitor can use the
whole home page in Indonesian.

### Next
- **B4 — Pages row-per-locale** (⚠️ ALTER + unique-index on existing `pages`;
  `mysqldump` first). The demo milestone: author an Indonesian page + home from the
  same admin, builder included.
