# STEP 0 — Phase 3 Audit & Safety Baseline

Date: 2026-06-19
Branch: `feature/cms-phase-3-claude`
Decision: **PASS — baseline established**

---

## 1. Scope

Characterize and lock every contract that Phase 3 (Theme System) will extend or change.
No application code was modified during this step. Only test files were created.

---

## 2. Starting point

- Base branch: `develop` at merge commit `599380e` (Phase 2 release)
- Phase 3 branch: `feature/cms-phase-3-claude` (created from `develop`)
- Recovery branch: `backup/pre-cms-phase-3-claude-20260619`
- Phase 2 test baseline: **296 tests, 2,053 assertions**

---

## 3. Files inspected

| File | What was confirmed |
|------|--------------------|
| `AGENTS.md` | Authority order, protected modules, approval rules |
| `ai/reports/phase-2/cms-phase-2-to-phase-3-handoff.md` | Phase 2 completion evidence and Phase 3 starting rules |
| `ai/reports/phase-2/phase-2-final-audit-report.md` | PASS WITH MINOR NOTES gate decision |
| `ai/skills/cms-architect-skill.md` | Phase architecture and block type system |
| `ai/skills/design-system-skill.md` | Visual tokens and design language |
| `ai/guidelines/00-project-constitution.md` | Authority and protected-area rules |
| `ai/guidelines/05-admin-dashboard-cms-builder.md` | CMS module pattern and admin UX rules |
| `app/Services/GlobalSettingsService.php` | Cache key constants, TTL, invalidation hooks |
| `app/Support/BrandColorSettings.php` | 21 fields, slugs, defaults |
| `app/Support/PageTemplateRegistry.php` | 3 allowlisted templates, view paths, fallback |
| `app/Http/Controllers/Frontend/PageController.php` | Template resolution flow via PageTemplateRegistry |
| `app/Models/Page.php` | `template_id` FK, `status` scope |
| `app/Models/PageTemplate.php` | `blade_file` column, `is_active` scope |
| `app/Models/SiteSetting.php` | Key-value store, cache-invalidation hooks |
| `resources/views/layouts/frontend.blade.php` | Layout assembly: brand-colors + header + footer |
| `resources/views/partials/site-brand-colors.blade.php` | 26 CSS custom properties injected |
| `resources/views/frontend/partials/header.blade.php` | Managed menu vs legacy fallback logic |
| `resources/views/frontend/partials/footer.blade.php` | Managed quick/utility links vs legacy fallback |
| `resources/views/frontend/pages/show.blade.php` | Extends `layouts.frontend`, uses `$templateView` |
| `routes/admin.php` | Phase 2 admin routes — all confirmed intact |
| `routes/frontend.php` | Public page route `GET /pages/{slug}` |
| `tests/Feature/Performance/GlobalSettingsCacheTest.php` | Existing cache coverage |
| `tests/Feature/Frontend/GenericPageRenderingTest.php` | Existing template/block coverage |
| `tests/Feature/Admin/GlobalBrandColorsSettingsTest.php` | Existing brand color coverage |
| `tests/Feature/Frontend/MenuRenderingTest.php` | Existing managed menu coverage |

---

## 4. Contracts locked by STEP 0 characterization tests

### C1 — CSS variable name completeness

The `partials.site-brand-colors` view injects exactly **26** CSS custom properties into the `:root` block.
Phase 3 customizer must emit all 26 names unchanged.

| CSS variable | Source slug |
|---|---|
| `--frontend-black` | `palette_primary` |
| `--frontend-gold` | `palette_secondary` |
| `--frontend-brand-accent` | `palette_accent` |
| `--frontend-white` | `surface_card` |
| `--frontend-gold-pale` | `surface_soft` |
| `--frontend-border` | `surface_border` |
| `--frontend-charcoal` | `text_title` |
| `--frontend-gray` | `text_muted` |
| `--frontend-gold-soft` | `text_on_dark` |
| `--frontend-text-title` | `text_title` |
| `--frontend-text-body` | `text_body` |
| `--frontend-text-muted` | `text_muted` |
| `--frontend-text-link` | `text_link` |
| `--frontend-text-on-dark` | `text_on_dark` |
| `--frontend-surface-body` | `surface_body` |
| `--frontend-surface-card` | `surface_card` |
| `--frontend-surface-soft` | `surface_soft` |
| `--frontend-surface-dark` | `surface_dark` |
| `--frontend-button-primary-bg` | `button_primary_bg` |
| `--frontend-button-primary-text` | `button_primary_text` |
| `--frontend-button-primary-hover-bg` | `button_primary_hover_bg` |
| `--frontend-button-primary-hover-text` | `button_primary_hover_text` |
| `--frontend-button-cta-bg` | `button_cta_bg` |
| `--frontend-button-cta-text` | `button_cta_text` |
| `--frontend-button-submit-bg` | `button_submit_bg` |
| `--frontend-button-submit-text` | `button_submit_text` |

Note: 7 source slugs appear in more than one CSS variable name (e.g., `text_title` drives both `--frontend-charcoal` and `--frontend-text-title`). Both aliases must be preserved.

### C2 — BrandColorSettings field catalog

`BrandColorSettings::fields()` returns **21 fields** with stable slugs and 6-digit hex defaults.
`BrandColorSettings::GROUP` is `'brand_colors'`.
Phase 3 customizer must use the same 21 slugs as its token identifiers.

Sorted slugs (locked):
`button_cta_bg`, `button_cta_text`, `button_primary_bg`, `button_primary_hover_bg`,
`button_primary_hover_text`, `button_primary_text`, `button_submit_bg`, `button_submit_text`,
`palette_accent`, `palette_primary`, `palette_secondary`, `surface_body`, `surface_border`,
`surface_card`, `surface_dark`, `surface_soft`, `text_body`, `text_link`, `text_muted`,
`text_on_dark`, `text_title`

### C3 — CSS var → slug mapping

A `SiteSetting` row with `key = 'brand.palette.primary'` and `value = '#aabbcc'` renders
as `--frontend-black: #aabbcc` in the frontend HTML. The slug-to-CSS-var mapping is
implemented in `partials.site-brand-colors.blade.php` and must remain in sync with
`BrandColorSettings::fields()`.

### C4 — PageTemplateRegistry structure

`PageTemplateRegistry` is the single source of truth for allowed page templates.

| Key | View path | Schema type |
|-----|-----------|-------------|
| `default` | `frontend.templates.default` | `WebPage` |
| `full-width` | `frontend.templates.full-width` | `WebPage` |
| `contained` | `frontend.templates.contained` | `Article` |

- `keyFor(null)` → `'default'`
- `keyFor('')` → `'default'`
- `keyFor('any-unknown-value')` → `'default'`

Phase 3 STEP 5 (Theme Template Hierarchy) must extend this resolution chain, not bypass it.
The three view files exist on disk and must remain in place.

### C5 — GlobalSettingsService cache key constants

| Constant | Value |
|----------|-------|
| `SETTINGS_CACHE_KEY` | `'global_settings.public.v1'` |
| `ASSETS_CACHE_KEY` | `'global_assets.public.v1'` |
| `CACHE_TTL_MINUTES` | `30` |

Phase 3 theme cache keys must use a different prefix (proposed: `'theme.*'`) to avoid collision.

### C6 — Frontend layout assembly

Every public page response produced by `layouts/frontend.blade.php` contains:
- `:root {` CSS block (from `partials.site-brand-colors`)
- `<header` element with `class="frontend-header` (from `frontend.partials.header`)
- `<footer` element (from `frontend.partials.footer`)

The layout file literally includes all three partials; Phase 3 theme switcher may add
a layer above these includes but must not remove or reorder them for the base case.

---

## 5. Test results

### STEP 0 characterization tests only

```
php artisan test tests/Feature/Phase3/ThemeBaselineCharacterizationTest.php
Tests:  16 passed
Assertions: 79
```

### Full suite after adding STEP 0 tests

```
php artisan test
Tests:  312 passed (296 Phase 2 baseline + 16 new)
Assertions: 2,132
```

No existing tests were broken. No application code was changed.

---

## 6. Existing Phase 2 coverage that STEP 0 complements (not duplicates)

| Existing test | What it covers | What C0 adds |
|---|---|---|
| `GlobalBrandColorsSettingsTest::test_frontend_layout_injects_brand_color_css_variables` | Checks 3 specific CSS var values | C1 checks ALL 26 var names are present |
| `GlobalSettingsCacheTest` | Cache hit/miss behavior | C5 locks the exact constant strings |
| `GenericPageRenderingTest::test_missing_template_view_falls_back_to_default_template` | Fallback via HTTP request | C4 locks the registry keys and view paths directly |
| `MenuRenderingTest` | Managed vs legacy menu items | C6 locks the full layout assembly at HTTP level |

---

## 7. Remaining risks entering Phase 3 (carried forward from Phase 2)

1. Browser / Lighthouse QA still not completed — LCP, CLS, INP unmeasured on many-block pages.
2. Slug redirect history deferred — old slug URLs 404 after a slug change.
3. Normalized media-usage relations deferred — `page_blocks.data` stores media IDs as raw JSON.
4. Repository-wide Pint debt outside Phase 2 surface.
5. `PageTemplateRegistry` is a hardcoded PHP constant array — Phase 3 STEP 5 must extend it carefully to support theme-level template overrides without breaking the existing allowlist behavior.
6. `GlobalSettingsService` cache invalidation is tied to `SiteSetting` model events — Phase 3 `ThemeCustomizerService` must implement its own invalidation independently.

---

## 8. Recommended next step

**STEP 1 — Theme Directory Architecture & Discovery**

Define the `themes/` directory structure, create the `themes` table and `Theme` model,
build `ThemeDiscoveryService` to scan for `theme.json` manifests, and create the admin
Theme Library index page.

Pre-conditions for STEP 1:
- [ ] STEP 0 tests passing — **DONE** (16/16, 79 assertions)
- [ ] Full suite clean — **DONE** (312/312, 2,132 assertions)
- [ ] No existing application code changed — **DONE**

---

## Task: STEP 0 — Audit & Safety Baseline

### Changed
- `tests/Feature/Phase3/ThemeBaselineCharacterizationTest.php` — created; 16 characterization tests across 6 contracts

### Impact
- DB: none
- Routes: none
- Frontend: none
- Security: none
- Backend: none

### Rollback
```bash
git rm tests/Feature/Phase3/ThemeBaselineCharacterizationTest.php
```

### Next
STEP 1 — Theme Directory Architecture & Discovery
