# Task B9 — Phase 7 Builder Bridge Locale-Aware (finish)

**Phase:** 7 — Internationalization (i18n)
**Date:** 2026-07-10
**Branch:** `feature/phase-7-a1-foundation`
**Type:** Finish builder-bridge locale awareness + per-locale admin preview (no schema).

---

## Task: B9 — content_field locale-aware + preview aligned to record's locale

`content_query` already filtered by `Locales::current()` from B5. This task
closes the two remaining bridge gaps:

1. `content_field` referencing a specific entry by ID must resolve the **sibling
   in the current locale** (via translation group) so a visitor on `/id` sees
   the ID copy of that field.
2. The **admin builder preview** — which runs on the default-locale route, not
   under a `/{locale}` prefix — must render in the record's own locale so the
   owner sees the translated result while editing.

### Changed

**`app/Support/ContentFieldResolver.php`** — `targetEntry()` for the
`entry_id` path now:
1. Looks up the referenced entry (public + active content type; ANY status).
2. Resolves the sibling in `Locales::current()` via `translationSiblings()`.
3. If a **published** sibling exists → serves it (localized copy).
4. If not, serves the originally referenced entry **only if** it is published
   itself — otherwise returns `null` so the block simply doesn't render.

This preserves the A0 fallback rule for attributes: no available copy → fall
back to the default-locale row. Draft or missing → the block hides.

**`app/Http/Controllers/Frontend/PageController.php`** — `renderPage()` runs
`app()->setLocale($page->locale)` when in preview mode. Effect: opening an
Indonesian page preview from the admin (unprefixed URL) renders the page against
the ID app locale — translated `SiteSetting` chrome, catalog copy, `content_field`
values, and the switcher all resolve correctly.

**`app/Http/Controllers/Admin/ContentEntryBuilderController.php`** —
`previewPayload()` sets `app()->setLocale($entry->locale)`. Same effect for entry
bodies previewed from the builder before save.

**Tests** — `tests/Feature/Phase7/B9BuilderBridgeLocaleTest.php` (6 tests):
`content_field` locale-aware resolution + fallback + hide-when-draft + current-
entry path; page + entry preview render in the record's own locale.

### Design notes
- **Attributes vs documents** (A0 rule). `content_field` renders an **attribute**
  → fall back to default locale to keep the block useful. `content_query` returns
  **documents** → hide untranslated (already B5 behaviour, unchanged).
- **Preview vs public routing.** The public `/id/pages/{slug}` route already sets
  the locale via `SetLocale` middleware; only preview URLs are unprefixed, so the
  fix is scoped to `renderPage($preview: true)` and the entry preview endpoint.
  No public-route change.

### Impact
- DB: **none**.
- Routes: none.
- Frontend: default-locale (unprefixed) URLs unchanged; only the preview and the
  in-page `content_field` block gain locale-aware output.
- Security: unchanged (public + active filters + published gate all preserved).

### Verification
- `B9BuilderBridgeLocaleTest` — **6/6 pass**.
- Full suite — **955/955 pass** (949 + 6 B9; no regression).
- PHPStan level 5 — **0 errors**.

### Rollback
`git revert <B9 commit>`. `content_field` reverts to serving the referenced row
regardless of locale; previews render in EN.

### Milestone
**M4 (Catalog & polish) COMPLETE** — B6–B9 done: catalog + menus + terms
localized; admin translation-status UX; builder bridges + preview locale-aware.

### Next
- **B10 — SEO i18n:** `hreflang` alternates + `x-default`, per-locale sitemap,
  localized canonical/OG/JSON-LD `inLanguage`, localized 404. Then Stage C
  (C1 PHPStan/`{!! !!}` sweep, C2 performance, C3 functional smoke, C4 docs).
