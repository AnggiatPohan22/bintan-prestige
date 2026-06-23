# B4 — Inline Editing

**Date:** 2026-06-22  
**Branch:** `feature/phase-5-stage-b-visual-builder`  
**HEAD before B4:** `6346b80473095ae9ad522e836de2ffb9255c8793`  
**Status:** COMPLETE — implementation verified, browser interaction requires manual confirmation

## Implemented

- Click-to-edit canvas fields for Heading, Text, Hero, and CTA blocks.
- Plaintext fields: Heading `text`; Text `heading`; Hero `title`, `subtitle`,
  `cta_text`; CTA `title`, `description`, `button_text`.
- Richtext field: Text `body_html`.
- Canvas edits and the B3 settings panel mutate the same Alpine block-tree object.
- Builder-only empty placeholders, hover outline, and focus outline.
- Selection toolbar for Bold, Italic, HTTP(S) Link, and Clear Formatting.
- Schema metadata in `config/blocks.php` identifies inline fields and their
  `plaintext` or `richtext` contract.
- Save-tree continues to use the existing endpoint; no route/controller/schema
  or dependency changes were introduced.

## Server-side sanitization

`App\Support\InlineContentSanitizer` provides the strict B4 allowlist. The existing
`PageBlockService::validateAndSanitizeData()` applies it using the block registry:

- plaintext strips every HTML tag;
- richtext permits the approved formatting tags;
- only safe classes plus HTTP(S) link attributes survive;
- event attributes, script/style/embed/form elements, JavaScript/data URIs, and
  unrelated attributes are removed;
- `_blank` links receive `noopener noreferrer`;
- Text rendering sanitizes again to protect transient preview fallback data.

No new package was needed. The PHP DOM extension is unavailable in this runtime,
so the implementation uses a tested custom allowlist.

## Verification

- Pint scoped check: PASS.
- Blade view compilation: PASS.
- Vite production build: PASS.
- Focused PageBlock suite: 20 tests / 354 assertions / 0 failures.
- Final full suite: 613 tests / 3100 assertions / 0 failures.
- PHPStan level 5: 0 errors.
- XSS vectors: allowed markup retained; script, style, iframe, image event,
  inline event, JavaScript URI, encoded JavaScript URI, and plaintext HTML cases
  neutralized.
- Builder preview regression test: inline markers rendered and transient raw
  preview payload sanitized.
- HTTP smoke: login 200; admin pages 302; robots.txt 200.
- Admin route count: 155, unchanged.
- `git diff --check`: PASS.

Browser automation could not connect to either Laragon localhost or the temporary
Laravel QA server, although command-line HTTP smoke succeeded. Manually confirm:

1. Heading/Text/Hero/CTA fields accept direct canvas typing.
2. Canvas changes immediately appear in the selected block's settings panel.
3. Settings-panel changes refresh the corresponding canvas field.
4. Selecting Text body copy shows the richtext toolbar and all four actions work.
5. Save and reload preserve sanitized content.

## Impact

- DB/schema: none.
- Routes/controllers/models: none.
- Packages: none.
- Public URLs/auth/CSRF: unchanged.
- Frontend: public rendering conditions remain unchanged; builder canvas gets
  editable empty placeholders and defensive richtext sanitization.

## Rollback

Revert the future B4 commit, or restore only the B4 files listed in its diff and
remove `app/Support/InlineContentSanitizer.php` plus this report. No database
rollback is required.

## Next

B5 — Reusable Patterns & Saved Blocks, after owner review of B4 manual browser QA.
