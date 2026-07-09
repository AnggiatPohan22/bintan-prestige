# Media Library — Reports & Handoff

Living docs for the Media Library "one door" image-input initiative. Update these
when a section changes so future upgrades stay traceable per area.

| File | Purpose |
|------|---------|
| [`media-library-audit.md`](media-library-audit.md) | **Report** — full audit of every admin image/file input, classified (library internals / already-integrated / done / dead code / out-of-scope / to-convert). Backend impact map. |
| [`media-library-integration-plan.md`](media-library-integration-plan.md) | **Handoff** — implementation plan + per-stage status (Stage 1 Settings → Stage 4 Page Sections, all done), DB approach (path-string, no schema), staged follow-ups. |

## The enforced standard (read before adding any image field)
- Canonical pattern: `ai/skills/media-library-skill.md` → ⭐ Canonical Image Input Standard.
- CI guard: `tests/Feature/Admin/MediaLibraryInputStandardTest.php` fails on any raw
  named `<input type="file">` in admin views (allowed: favicon, theme_zip).
- Constitution rule: `AGENTS.md` §8; UI checklist: `Claude.md` (admin UI ALWAYS/NEVER).

## When you extend a section
1. Follow the standard in the skill file (components, path column, delete-guard, DIRECT_REFERENCES).
2. Update the relevant stage row in `media-library-integration-plan.md`.
3. Keep the CI guard green.
