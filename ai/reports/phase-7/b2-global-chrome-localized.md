# Task B2 — Phase 7 Global Chrome Localized

**Phase:** 7 — Internationalization (i18n)
**Date:** 2026-07-09
**Branch:** `feature/phase-7-a1-foundation`
**Type:** Localize global-settings chrome via the B1 sidecar + dedicated admin panel.

---

## Task: B2 — Global chrome localized

Make the site chrome driven by `site_settings` (navigation, footer, CTA, business
identity, SEO defaults) resolve per locale, and give the owner a dedicated admin
panel to enter those translations. Owner chose the **dedicated translation panel**
approach (over per-field tabs) — extend-only, the 8 existing per-group forms are
untouched.

### Changed

**New**
- `app/Support/TranslatableSettings.php` — the **allow-list** of global-chrome
  SiteSetting keys that hold human copy (grouped into sections, each with input
  type). URLs, colours, tracking IDs, locale/language codes, separators, numbers,
  selects and booleans are deliberately excluded.
- `resources/views/backend/settings/translations.blade.php` — the admin panel:
  locale selector, and per field the default-locale value (read-only reference)
  plus a text/textarea input for the selected locale. Uses admin tokens
  (`admin-input`, `admin-btn-primary`, `bg-admin-card`, …).
- `tests/Feature/Phase7/B2GlobalChromeLocalizedTest.php` — 9 tests.

**Edited**
- `tests/Feature/Performance/GlobalSettingsCacheTest.php` — two white-box cache
  tests updated to the new per-locale cache key (`…v1.{locale}`); coverage of the
  discard-and-rebuild guard is preserved.
- `app/Services/GlobalSettingsService.php` — the settings payload is now
  **cached per locale** (`global_settings.public.v1.{locale}`) and each setting's
  `value` is resolved with `translate('value', $locale)` (eager-loaded via
  `withTranslations()` — N+1 guard). The whole downstream pipeline (Support
  classes + Blade + header/footer) stays unchanged; it just receives already
  localized values. `forgetSettingsCache()` clears every per-locale variant.
- `app/Http/Controllers/Admin/SiteSettingController.php` — `translations()` (show
  panel) + `updateTranslations()` (persist via `setTranslation`, allow-list
  guarded, then `forgetSettingsCache()` since sidecar writes don't fire the
  SiteSetting saved-hook).
- `routes/admin.php` — GET/PUT `settings/global-assets/translations`.
- `resources/views/backend/settings/global-assets.blade.php` — a "🌐 Translations"
  entry-point button (shown only when a non-default locale is active).

### How it works
- **Read:** `GlobalSettingsService` resolves values for `app()->getLocale()`,
  falling back to the base column. Cache is per locale, so no runtime cost after
  warm-up. The header CTA, footer note, identity, SEO defaults, etc. all localize
  with **zero changes to their Blade or Support classes**.
- **Write:** the panel lists only allow-listed copy; the owner picks a locale and
  fills each field. Values are stored in the `translations` sidecar
  (`field = 'value'`) on each SiteSetting row; empty clears the row (fallback
  resumes). The default locale stays in the base column and is edited in the
  existing per-group forms.

### Impact
- DB: **none** (uses B1's `translations` table; no new migration).
- Routes: 2 admin routes added (`settings.global-assets.translations[.update]`).
- Frontend: chrome now renders in the active locale where a translation exists;
  default-locale output is byte-identical to before.
- Security: admin-guarded; writes are allow-list-restricted to global-chrome copy
  keys (a rogue key like `canonical_base_url` is ignored); values are plain text
  and escape via the existing Blade `{{ }}` paths.

### Verification
- `B2GlobalChromeLocalizedTest` — **9/9 pass** (per-locale payload resolution +
  fallback; `/id` header shows the ID CTA; `/` shows the base CTA; admin panel
  renders; save creates the sidecar row; empty clears it; non-allow-listed key
  ignored; default locale rejected).
- Full suite — **900/900 pass** (866 baseline + 13 A2 + 12 B1 + 9 B2; no regression).
- PHPStan level 5 — **0 errors**.

### Rollback
`git revert <B2 commit>`. No migration to roll back (B1 owns the table).

### Next
- **B3 — Page Sections localized:** apply the `Translatable` trait to the
  `PageSection` protected model (label/title/subtitle/description/button_text via
  the sidecar; media slots stay shared) so the home page becomes fully bilingual —
  completing milestone **M2** (a visitor can use the whole home page in Indonesian).
