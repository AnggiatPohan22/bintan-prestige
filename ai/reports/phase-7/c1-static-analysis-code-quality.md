# Task C1 — Phase 7 Static Analysis & Code Quality

**Phase:** 7 — Internationalization (i18n)
**Date:** 2026-07-10
**Branch:** `feature/phase-7-a1-foundation`
**Type:** Release gate — sweep only, minimal code (regression fences).

---

## Task: C1 — Static analysis + `{!! !!}` audit including translated output

### What was checked

**PHPStan level 5 — 0 errors**
Confirmed after every task; final re-run today is clean.

**No debug leaks in `app/` + `resources/`**
Grep for `dd(`, `dump(`, `var_dump(`, `print_r(` → **0 matches**.

**No stale TODO / FIXME / HACK / XXX markers**
Grep across `app/` + `resources/` → **0 matches**.

**No unsafe mass assignment**
Grep for `->update($request->all())`, `->create($request->all())`,
`->fill($request->all())` → **0 matches**. Every write path goes through a
FormRequest or an explicit whitelist array.

**No hardcoded credentials / API keys**
Grep on `password =>`, `api_key =`, `secret_key =` in `app/` → **0 matches** (all
values source from `.env`/`config`).

**`{!! !!}` raw-echo audit — every occurrence classified.**

| File | Line | Content | Safe? | Why |
|---|---|---|---|---|
| `layouts/admin.blade.php` | 37 | `{!! $adminAppearanceCss !!}` | ✅ | Admin CSS built from validated hex colors (comment already notes it). |
| `frontend/blocks/text.blade.php` | 29 | `{!! $body !!}` | ✅ | `$body` is `InlineContentSanitizer::richtext(...)` at line 8. |
| `frontend/blocks/content-field.blade.php` | 22 | `{!! $field['html'] !!}` | ✅ | `ContentFieldResolver::format()` runs `InlineContentSanitizer::richtext()` for the richtext branch (returns `text=''`, `html=sanitized`). |
| `frontend/blocks/columns/group.blade.php` | inline styles | `{!! $style !!}` | ✅ | Structural style string built from `e()`-escaped scalars. |
| `frontend/widgets/text.blade.php` | 13 | `{!! InlineContentSanitizer::richtext($content) !!}` | ✅ | Sanitized inline. |
| `frontend/widgets/html.blade.php` | 4 | `{!! $code !!}` | ✅ | Intentional HTML widget (admin-only), part of the CMS spec. |
| `frontend/products/show.blade.php` | 303 | `{!! nl2br(e($descriptionState['plain_text'])) !!}` | ✅ | `e()` first, then `nl2br`. |
| `frontend/content-entries/single.blade.php` | 21 | `{!! json_encode(..., JSON_HEX_TAG\|…) !!}` | ✅ | Hex-escape flags → `</script>` cannot break out. |
| `partials/site-structured-data.blade.php` | 27 | `{!! $structuredDataJson !!}` | ✅ | Built by `StructuredDataBuilder::jsonLd()` with the same hex-escape flags (A1 fix). |
| `partials/tracking-{head,body-*}.blade.php` | 3× | `{!! $tracking['custom_*_script'] !!}` | ✅ | Owner-provided tracking snippets by design (Google Tag, Meta Pixel, etc.). |
| `backend/media/picker.blade.php` | 18 | `{!! $adminAppearanceCss !!}` | ✅ | Same admin CSS pattern. |
| `vendor/pagination/*.blade.php` | — | Laravel-published pagination | ✅ | Framework-owned; unchanged. |

**Zero raw-echo of translation-sourced values.**
Grep on `\{!!.*(translate|rawTranslation|->translations|Locales::)` and
`\{!!.*(section|product|category|destination|item|entry|term)->` in
`resources/views/` → **0 matches**. Sidecar values only reach Blade through
locale-aware accessors, which downstream code renders via `{{ }}` (or, in the
one JSON-LD case, through hex-escaped `json_encode`).

### Regression fences

Even with the audit clean, added a defensive test so a future refactor cannot
silently introduce a raw-echo of a translated value:

- `tests/Feature/Phase7/C1EscapeAuditTest.php` — 3 tests:
  1. Translated `SiteSetting` value with `<script>` renders escaped in the
     frontend chrome (`/id` header CTA).
  2. Translated `PageSection.title` with `<img onerror>` renders escaped in the
     home hero.
  3. Translated business name containing `</script>` cannot break out of the
     site-wide JSON-LD `<script type="application/ld+json">` block (extends
     A1's `StructuredDataEscapeTest` to the sidecar path).

### Verification
- PHPStan level 5 — **0 errors**.
- `C1EscapeAuditTest` — **3/3 pass**.
- Full suite — **967/967 pass** (964 + 3 C1; no regression).

### Rollback
Documentation + tests only. `git revert <C1 commit>` removes the fences; no code
change to roll back.

### Next
- **C2 — Performance audit:** confirm localized routes ≤300ms warm (public
  `/`, `/id`, `/products`, `/id/products`, `/pages/{slug}`, `/id/pages/{slug}`,
  `/blog/{slug}`); explicitly assert **no per-attribute translation queries**
  on listing/detail paths; switcher + hreflang add no queries.
