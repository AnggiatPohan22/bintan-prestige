# Task B10 — Phase 7 SEO i18n

**Phase:** 7 — Internationalization (i18n)
**Date:** 2026-07-10
**Branch:** `feature/phase-7-a1-foundation`
**Type:** SEO surfaces localized end-to-end (no schema change).

---

## Task: B10 — SEO i18n

Every locale-observable SEO surface now localizes:
- `<html lang>` dynamic per locale
- `<link rel="alternate" hreflang="…">` + `x-default` in head
- Canonical URL locale-prefixed for non-default locales (already at B4/B5)
- OG `og:locale` reflects the current locale (`en_US`, `id_ID`)
- JSON-LD `WebPage`/`Article` + `WebSite` schemas emit `inLanguage`
- `sitemap.xml` lists every locale's Page and ContentEntry URL with
  `xhtml:link` alternates + `x-default`
- Localized 404 view

### Changed

**New**
- `resources/views/partials/site-hreflang.blade.php` — head partial: emits
  `<link rel="alternate">` per active locale (from `$localeAlternates` when
  provided by controller; else generic path swap) + an `x-default` pointing
  at the default-locale URL. Skips when only one locale is active.
- `resources/views/errors/404.blade.php` — localized 404 view (chrome intact,
  Bahasa Indonesia + English strings from `lang/{code}/frontend.php`). Sets
  `app()->setLocale(Locales::localeFromRequest())` at the top because the
  exception handler bypasses route-group middleware.
- `tests/Feature/Phase7/B10SeoI18nTest.php` — 9 tests.

**Edited**
- `resources/views/frontend/frontend.blade.php` AND
  `resources/views/layouts/frontend.blade.php` (both — there are two active
  layouts; the second used to be missed): `<html lang>` uses
  `Locales::current()`; new `@include('partials.site-hreflang')`.
- `resources/views/partials/site-social-share-meta.blade.php` — `og:locale`
  uses `Locales::ogLocale(Locales::current())`.
- `config/locales.php` — added `og_locale` per code (`en_US`, `id_ID`); adding
  a locale later = one config entry.
- `app/Support/Locales.php` — `ogLocale()` helper (falls back to
  `{code}_{UPPER(code)}` for unlisted entries).
- `app/Support/StructuredDataBuilder.php` — `inLanguage` on `WebPage/Article`
  page schema and `WebSite` schema.
- `app/Http/Controllers/Frontend/SitemapController.php` — full rewrite: groups
  Pages **and** published ContentEntries by `translation_group_id` and emits
  one `<url>` per row with the whole group as alternates.
- `resources/views/frontend/sitemap.blade.php` — `xmlns:xhtml`, per-URL
  `<xhtml:link>` alternates, plus `x-default` for the default-locale URL.
- `lang/en/frontend.php`, `lang/id/frontend.php` — `not_found_*` strings.

### Design notes
- **`x-default`** always points at the default-locale URL — this is the SEO
  convention Google recommends and lines up with the "unprefixed default" URL
  strategy locked at A0 §3.2.
- **Head vs sitemap alternates.** Head-level `hreflang` on a Page/Entry uses the
  controller-provided `$localeAlternates` (only published siblings — no
  half-translated leaks; matches A0 §3.5). Sitemap uses the same rule per group.
- **404 outside middleware.** Exception handler doesn't run route-group
  middleware, so the 404 view derives locale from the URL segment via
  `Locales::localeFromRequest()` and calls `app()->setLocale()` itself. This is
  the same helper used for route-model binding at B4.
- **Two layouts, one bug avoided.** The project has both
  `frontend/frontend.blade.php` **and** `layouts/frontend.blade.php` active
  (theme-service routed). Both had to gain the dynamic `<html lang>`, the
  hreflang include, and share the same social-share partial for OG:locale.
  The test caught this because updating only the first left the second serving
  the old `$seoDefaults['language']` value.

### Impact
- DB: **none**.
- Routes: none.
- Frontend: every locale-observable head + sitemap surface now correct; default-
  locale URL output byte-identical.
- Security: none (all strings escape through Blade `{{ }}` or `JSON_HEX_*`
  builder).

### Verification
- `B10SeoI18nTest` — **9/9 pass** (`<html lang>` dynamic; OG locale; hreflang
  alternates on chrome and page — hides untranslated; canonical locale-prefixed;
  JSON-LD `inLanguage`; sitemap lists every locale + `xhtml:link` alternates for
  Pages **and** Content Entries; 404 localized).
- Full suite — **964/964 pass** (955 + 9 B10; no regression).
- PHPStan level 5 — **0 errors**.

### Rollback
`git revert <B10 commit>`. Head SEO reverts to a single `<html lang="en">`, no
hreflang, sitemap lists only default-locale Pages.

### Milestone
**B10 COMPLETE — every functional Phase 7 task shipped.** Only Stage C release
audit remains: C1 static analysis + `{!! !!}` sweep, C2 performance
(localized routes ≤300ms warm, translation N+1 checks), C3 smoke test, C4 docs.

### Next
- **C1 — Static Analysis & Code Quality:** already have PHPStan level 5 / 0
  errors, but sweep `{!! !!}` incl. translated output; sanitizer + escape audit;
  dead-code + TODO scan.
