# Task A2 — Phase 7 Locale Foundation

**Phase:** 7 — Internationalization (i18n)
**Date:** 2026-07-09
**Branch:** `feature/phase-7-a1-foundation`
**Type:** Locale foundation — routing + middleware + config + switcher chrome.
**Nothing is translated yet** (that begins B1/B2); A2 is the plumbing.

---

## Task: A2 — Locale foundation

Introduce the locale catalogue, per-locale URL routing, the `SetLocale`
middleware, reserved-prefix protection for locale codes, lang-file scaffolding,
and the frontend locale switcher — with **zero breakage** of existing
default-locale URLs.

### Changed

**New**
- `config/locales.php` — code-first locale catalogue (`en` default + `id`,
  each with native/label/flag/is_active), `default` + `fallback` keys. Mirrors
  `config/media.php`. Adding a locale later = one entry.
- `app/Support/Locales.php` — read-only helper over the catalogue: `active()`,
  `default()`, `nonDefaultActive()`, `isActive()`, `current()`, and
  `localizedUrl()` (rewrites the current path into another locale for the
  switcher; strips/prepends the prefix, default stays bare).
- `app/Http/Middleware/SetLocale.php` — takes the group's locale as a parameter
  (`SetLocale::class.':id'`), validates it against active locales (else default),
  sets `app()->setLocale()`, persists to session. Route-cache safe.
- `lang/en/frontend.php`, `lang/id/frontend.php` — scaffolding for the few
  non-editable chrome strings (language/menu labels). Wiring existing hardcoded
  Blade strings comes in B2/B10.
- `resources/views/frontend/partials/locale-switcher.blade.php` — switcher chrome
  (inline + stacked variants); reuses `frontend-nav__link` styling; emits
  `hreflang`/`lang`/`aria-current`.
- `tests/Feature/Phase7/A2LocaleFoundationTest.php` — 13 tests (routing, names,
  middleware, fallback ordering, reserved prefixes, helper, switcher render).

**Edited**
- `bootstrap/app.php` — frontend routes now register **per active locale**:
  each non-default locale first under a `/{code}` prefix + `{code}.` name prefix
  + `SetLocale:{code}`, then the default locale bare (canonical names) with
  `SetLocale:{default}`. Order is deliberate — the prefixed `Route::fallback`
  must be tried before the bare `.*` fallback so `/{locale}/...` resolves
  correctly while default-locale paths still hit the bare fallback.
- `app/Http/Controllers/Frontend/ContentEntryController.php` — `resolve()` drops
  a leading active-locale segment before route_base/slug resolution, so
  `/id/blog/hello` resolves identically to `/blog/hello` (app locale already set
  by middleware).
- `app/Models/ContentType.php` — added `reservedPrefixes()` merging
  `RESERVED_PREFIXES` with the config locale codes, so a locale prefix (e.g.
  `id`) can never be a content-type `route_base`.
- `app/Http/Requests/Admin/StoreContentTypeRequest.php`,
  `UpdateContentTypeRequest.php` — use `ContentType::reservedPrefixes()`.
- `resources/views/frontend/partials/header.blade.php` — include the switcher in
  the desktop actions and the mobile panel.
- `resources/css/frontend-theme.css` — layout-only rules for the switcher
  (flex/gap/size); colours inherit from `.frontend-nav__link` (no hardcoded hex).

### Impact
- DB: **none** (no schema change; locale is config + routing only).
- Routes: default-locale routes **unchanged** (`route('home')` === `url('/')`);
  new prefixed set added (`id.home`, `id.products.index`, …). `/id`, `/id/products`,
  `/id/{route_base}/{slug}` served; `SetLocale` sets the app locale per request.
- Frontend: locale switcher now visible in header (desktop + mobile). No content
  translated yet — both locales render the same (English) copy by design.
- Security: none (read-only config; FormRequest validation strengthened to block
  locale codes as route_base).

### Verification
- `A2LocaleFoundationTest` — **13/13 pass** (incl. switcher-render proof: `/`
  shows both locales + `hreflang="id"` + the `/id` link; `/id` links back to `/`).
- Full suite — **877/877 pass** (866 baseline + 11, later 13, new tests; no
  regression; B11 routing tests still green).
- PHPStan level 5 — **0 errors**.

### Rollback
`git revert <A2 commit>`. No migrations to roll back; reverting restores the
single bare frontend route group and removes the locale config/middleware/switcher.

### Next
- **B1 — `translations` sidecar + `Translatable` trait** (⚠️ new table). Build
  the polymorphic attribute-translation table + trait (current-locale resolution,
  base-column fallback, eager-load scope), unit-tested in isolation. `mysqldump`
  is only required before ALTERs on existing tables (B4/B5), not for this new
  table — but confirm the additive-migration + no-`migrate:fresh` rule at start.
