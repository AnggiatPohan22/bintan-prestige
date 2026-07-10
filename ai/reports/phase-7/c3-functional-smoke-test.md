# Task C3 — Phase 7 Functional Smoke Test

**Phase:** 7 — Internationalization (i18n)
**Date:** 2026-07-10
**Branch:** `feature/phase-7-a1-foundation`
**Type:** Release gate — automated behavioural fences (no production code change).

---

## Task: C3 — Behavioural smoke matrix

Grand plan §6 C3 requires per-locale route checks, fallback correctness, and
"existing unprefixed URLs unchanged". These are all now regression fences.

### Changed

- `tests/Feature/Phase7/C3FunctionalSmokeTest.php` — **13 tests** across five
  behavioural axes:

**1. Locale route matrix.**
- Default-locale public routes all `200`: `/`, `/products`, `/pages/{slug}`,
  `/blog` (entry archive), `/sitemap.xml` (with the correct content-type
  header), `/robots.txt`.
- Localized public routes all `200`: `/id`, `/id/products`, `/id/pages/{slug}`,
  `/id/blog`.
- Route names verified: `home`, `products.index` (unprefixed) AND `id.home`,
  `id.products.index` (prefixed) both resolve to their correct URLs.

**2. Document fallback semantics (A0 §3.5 — "hide untranslated").**
- Untranslated page → **404 in that locale only**, still `200` in the default.
- Draft translation → **404 in its locale only**, sibling in the other locale
  still `200`.
- Untranslated content entry → **404 in that locale**.

**3. Attribute fallback (A0 §3.4 — "fall back to base").**
- When an ID page has a base column value but no title translation, the base
  column IS the ID fallback and is served correctly.

**4. Admin guards on every new Phase 7 endpoint.**
- `POST admin.pages.translate` → guest gets redirect to login.
- `POST admin.content-types.entries.translate` → guest gets redirect to login.
- `GET admin.settings.global-assets.translations` → guest gets redirect to login.

**5. Legacy-URL parity (grand plan §12 DoD "default-locale URLs unchanged").**
- `<html lang="en">` + `<link rel="canonical" href="…/">` on default home.
- Switcher present but current locale is EN (aria-current); ID link points
  at `/id`.
- `route('pages.show', 'terms')` returns the **byte-identical unprefixed**
  URL — existing backlinks + sitemaps unchanged.
- `ContentType::reservedPrefixes()` still contains every locale code
  (`id`, `en`), so a locale code can never become a content-type `route_base`.

### Design notes
- **Fences over one-shot smoke.** Same discipline as C1/C2 — every axis has an
  explicit failing assertion with a helpful message. A future refactor that
  breaks "default URLs unchanged" fails a named test instead of a subtle
  behaviour drift.
- **No manual QA items opened.** Every checklist point in the grand plan §12
  DoD list that's programmatically observable is now covered by a test. The
  remaining items ("owner can translate from admin", "end-to-end proof shipped
  bilingual") are lifecycle acceptance and belong to C4 / production sign-off.

### Impact
- Production code: **unchanged**.
- Test suite: +13 tests (~3.2 s).
- CI signal: any Phase 7 semantic regression (routing, fallback, admin guard,
  URL parity) fails a named test.

### Verification
- `C3FunctionalSmokeTest` — **13/13 pass**.
- Full suite — **990/990 pass** (977 + 13 C3; no regression).
- PHPStan level 5 — **0 errors**.

### Rollback
`git revert <C3 commit>`. Test-only change.

### Next
- **C4 — Documentation:** `docs/modules/internationalization.md` (developer
  reference), `ai/skills/i18n-skill.md` (canonical i18n standard, Skill Map
  row), CHANGELOG Phase 7 entry, AGENTS.md + CLAUDE.md phase-line sync, Phase 8
  prep notes in the handoff. That closes Phase 7.
