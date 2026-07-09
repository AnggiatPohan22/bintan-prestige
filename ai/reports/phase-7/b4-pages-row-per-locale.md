# Task B4 — Phase 7 Pages Row-Per-Locale

**Phase:** 7 — Internationalization (i18n)
**Date:** 2026-07-09
**Branch:** `feature/phase-7-a1-foundation`
**Type:** ⚠️ Schema ALTER on existing `pages` (owner-approved; mysqldump taken first).

---

## Task: B4 — Pages row-per-locale + translation group

Each locale becomes a full page record linked by `translation_group_id`; the
default locale keeps its unprefixed URL and SEO. The visual builder is unchanged —
a translated page is just another page with its own block rail. **Demo milestone
(M3 start):** author an Indonesian page from the same admin, builder included.

### Pre-flight (safety)
- **mysqldump backup** taken before the ALTER →
  `storage/app/db-backups/pre-b4-pages-alter-20260709-210206.sql` (255 KB).
- Migration applied to dev DB: 5 pages backfilled `locale='en'`, each with its own
  26-char ULID group, 0 null locales.

### Changed

**Schema**
- `database/migrations/2026_07_09_000002_add_locale_to_pages_table.php` (⚠️,
  additive + reversible): `pages` gains `locale string(10) default 'en'` +
  `translation_group_id char(26)` (indexed). Backfill: `en` + one ULID per row.
  Unique index swapped `slug` → `(slug, locale)`. `down()` restores the original.

**Model / services**
- `app/Models/Page.php` — `locale` + `translation_group_id` fillable; `booted()`
  assigns locale (default) + ULID group on create; `translationSiblings()` /
  `translationIn($locale)`; `scopeForLocale`; `publicUrl()` (locale-aware URL);
  `getUrlAttribute()` → `publicUrl()`; `resolveRouteBinding()` scopes the public
  `{page:slug}` binding by the **URL-derived** locale (admin `{page}` id binding
  untouched).
- `app/Support/Locales.php` — `localeFromRequest()` reads the locale from the URL's
  first segment (safe during route-model binding, which runs before SetLocale).
- `app/Services/PageService.php` — `translateTo($page, $locale)`: creates (or
  returns the existing) sibling in the **same** group, copies the builder block
  tree, starts as a `draft` (idempotent).

**Requests**
- `StorePageRequest` — slug unique within the default locale.
- `UpdatePageRequest` — slug unique within the page's own locale.

**Frontend**
- `app/Http/Controllers/Frontend/PageController.php` — canonical URL uses the
  page's locale-aware path; `localeAlternates()` shares published sibling URLs so
  the switcher lands on the counterpart page and **hides** locales with no
  published translation (A0: untranslated documents hide).
- `resources/views/frontend/partials/locale-switcher.blade.php` — uses
  `$localeAlternates` when present (page context), else the path swap.

**Admin**
- `PageController::translate()` + route `pages/{page}/translate` — "Translate to…".
- `resources/views/backend/pages/edit.blade.php` — Translations panel (Edit
  {LOCALE} / + Translate to {LOCALE}).
- `resources/views/backend/pages/index.blade.php` — per-row locale badge + locale
  URL prefix.

**Tests**
- `tests/Feature/Phase7/B4PagesRowPerLocaleTest.php` — 13 tests.

### Design notes
- **Binding vs middleware ordering.** `SubstituteBindings` runs before
  `SetLocale`, so `resolveRouteBinding` cannot rely on `app()->getLocale()`; it
  uses `Locales::localeFromRequest()` (URL segment). Rendering (translated copy,
  switcher "current") still uses the app locale, which SetLocale has set by then.
- **No builder change.** Each locale's page owns its `page_blocks` rail;
  `translateTo` clones the tree so the owner translates in place.
- **Fallback semantics (A0).** A locale with no published page 404s in that locale
  only; the default locale is always unprefixed and unchanged.

### Impact
- DB: **ALTER `pages`** (2 columns + index swap; migrated, reversible; backup taken).
- Routes: +1 admin (`pages.translate`); public `pages.show` now resolves per locale
  (+ the `{locale}.pages.show` variants from A2's prefix group).
- Frontend: `/pages/{slug}` unchanged for default locale; `/{locale}/pages/{slug}`
  serves that locale (404 if untranslated); switcher lands on the counterpart.
- Security: admin-guarded; slug uniqueness enforced per locale in DB + FormRequests.

### Verification
- `B4PagesRowPerLocaleTest` — **13/13 pass** (model defaults; slug unique per
  locale + cross-locale allowed; default/localized routing; untranslated + draft
  404; switcher offers published / hides unpublished; `translateTo` copies blocks
  into same group as idempotent draft; admin action; publicUrl per locale).
- Full suite — **920/920 pass** (907 + 13 B4; no regression).
- PHPStan level 5 — **0 errors**.

### Rollback
`git revert <B4 commit>` then `php artisan migrate:rollback --step=1` (restores the
global `slug` unique index and drops the two columns). Backup at
`storage/app/db-backups/pre-b4-pages-alter-20260709-210206.sql` if needed.

### Next
- **B5 — Content Entries row-per-locale** (⚠️ same ALTER pattern on
  `content_entries`; `mysqldump` first): locale + translation_group_id,
  locale-aware unique `(content_type_id, slug, locale)`, archive/single controllers
  filter by locale, sidecar index + scheduling/revisions per record. Completes M3.
