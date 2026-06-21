# A3 — Block System Audit & Library Expansion Plan

## Date: 2026-06-21
## Branch: feature/phase-5-stage-a-foundation
## HEAD audited: 76bf1dc
## Status: COMPLETE — A3.1–A3.4 IMPLEMENTED AND VALIDATED

---

## 1. Position and scope

Phase 5 Stage A currently has:

- **A1 complete:** the block registry was extracted to `config/blocks.php`, an authenticated JSON registry endpoint was added, and transient preview payload rendering was added in commit `750f9c9`.
- **A2 complete:** shared admin components, grouped navigation, list/form migrations, and the final admin visual polish are present through HEAD `76bf1dc`.
- **Next step:** A3 — audit the current block authoring path and define the safe expansion order.

The initial A3 pass was deliberately report-only. Owner approval was received after the plan, and the approved A3.1 + A3.2 scope was then implemented without schema, route, controller, model, package, or existing block behavior changes.

---

## 2. Files and evidence reviewed

### Planning and prior reports

- `AGENTS.md`
- `AGENTS.override.md`
- `CLAUDE.md`
- `ai/skills/phase5-visual-builder-skill.md`
- `ai/skills/page-builder-skill.md`
- `ai/skills/cms-architect-skill.md`
- `ai/skills/testing-qa-skill.md`
- `ai/guidelines/05-admin-dashboard-cms-builder.md`
- `ai/reports/phase-5/a1-backend-readiness-audit.md`
- `ai/reports/phase-5/a2-admin-ux-refactor-plan.md`

### Current implementation

- `config/blocks.php`
- `app/Services/PageBlockService.php`
- `app/Http/Controllers/Admin/PageBlockController.php`
- `app/Http/Controllers/Frontend/PageController.php`
- `routes/admin.php`
- `resources/views/backend/pages/partials/block-editor.blade.php`
- all admin block partials in `resources/views/backend/pages/partials/blocks/`
- all frontend block partials in `resources/views/frontend/blocks/`
- `resources/views/frontend/pages/_blocks.blade.php`
- `tests/Feature/Admin/PageBlockManagementTest.php`
- `tests/Feature/Frontend/GenericPageRenderingTest.php`

Git history for A1/A2 and the diff from the Phase 4 baseline were also inspected. Existing untracked `.claude/` and `ai/promt/` content was not touched.

---

## 3. Current block inventory

The live registry contains **11 block types**, not the 7-block minimum described in the original Phase 5 outline.

| Block | Registry | Defaults + validation | Admin form | Frontend render | Notes |
|---|---:|---:|---:|---:|---|
| Hero | yes | yes | yes | yes | Overlay, height, image and CTA controls |
| Text | yes | yes | yes | yes | Sanitized rich HTML |
| Image | yes | yes | yes | yes | Media picker, alt, caption, width |
| Gallery | yes | yes | yes | yes | Multi-image, lightbox, carousel behavior |
| CTA | yes | yes | yes | yes | Three visual styles |
| Products Grid | yes | yes | yes | yes | Resolved data prepared outside the Blade partial |
| FAQ | yes | yes | yes | yes | Inline or library-backed content |
| Testimonials | yes | yes | yes | yes | Inline cards with ratings/avatar |
| Map | yes | yes | yes | yes | Validated HTTP(S) embed URL |
| Divider | yes | yes | yes | yes | Line, space, or gold-line |
| Contact Form | yes | yes | yes | yes | Phase 4 form-builder integration |

All types also receive the shared `background` object with color, image, position, repeat, size, and opacity.

### Coverage already provided by existing blocks

- Spacer is already approximated by Divider `style=space`.
- Accordion content is already covered by FAQ for question/answer use cases.
- Basic carousel behavior is already present in Gallery.
- Inquiry forms are already covered by Contact Form.
- Review cards are already covered by Testimonials.
- Destination embeds are already covered by Map.

Those capabilities should be extended only when a materially different authoring need is proven; duplicating them under new names would create avoidable maintenance.

---

## 4. Current block-authoring path

Adding one block currently requires coordinated edits in several places:

1. Add type, label, and icon to `config/blocks.php`.
2. Add defaults to `PageBlockService::defaultDataFor()`.
3. Add validation to `PageBlockService::rulesFor()`.
4. Add an admin form partial using the type's kebab-case filename.
5. Add a frontend render partial using the same filename convention.
6. Add management, validation, empty-render, and public-render test coverage.

The renderer and block editor consistently derive partial names using `str_replace('_', '-', $block->block_type)`, which is a useful convention to preserve.

### Finding: registry is only partially authoritative

`config/blocks.php` is now the authoritative list of type names, labels, and icons, and is exposed through the A1 JSON endpoint. However, it does **not** yet declare:

- category (`layout`, `content`, `media`, `travel`, `conversion`);
- description/keywords for the future inserter;
- editable field schema;
- default values;
- validation contract;
- supported style controls;
- whether the block can contain children.

Defaults and rules remain centralized in `PageBlockService`, while field markup remains in Blade partials. Therefore the current mapping is consistent but not yet a single machine-readable `type → fields → render` contract suitable for Stage B's dynamic settings panel.

### Finding: style controls are not uniform

All types support background settings. A few types add bespoke controls, such as Hero height/overlay, Gallery columns/gap/aspect ratio, Image width, and CTA style. There is no common contract for spacing, alignment, content width, or responsive behavior.

This is acceptable for the current form editor, but Stage B needs a declared `supports` map so its settings panel does not guess which controls apply.

### Finding routed to A4, not changed here

Most data-backed blocks use controller-prepared `resolvedProducts` and `resolvedFaqItems`. The Contact Form frontend partial still resolves `FormDefinition` from inside Blade. That architecture/performance issue is outside this report-only A3 scope and should be measured and handled in A4 rather than mixed into block expansion.

---

## 5. Constraints that shape the expansion

### Flat storage blocks layout containers

`page_blocks` remains a flat ordered list. There is no parent/children relation. Therefore Columns/Grid and Group/Section cannot honestly ship as nestable layout blocks without the separately approved nesting design identified in A1.

Implementing fake nesting inside each container's `data` JSON would create a second block format and bypass the existing `PageBlock` model, revision, reorder, validation, and rendering contracts. That approach is **not recommended**.

### No package is needed for A3 leaf blocks

The existing Laravel, Blade, Alpine, Tailwind, and media-picker stack is sufficient for the proposed non-container blocks. SortableJS remains a Stage B decision and is not part of A3.

### Database and protected architecture remain unchanged until approval

No migration, route rename, controller rewrite, model rename, or dependency change is justified merely to expand leaf blocks. Container/nesting support is the only A3-related item that may require schema work, and it must be handled as a separate explicit approval gate.

---

## 6. Ranked block library plan

Priority balances builder usefulness, Bintan travel value, overlap with existing blocks, implementation risk, and nesting dependency.

| Rank | Proposed block | Category | Stage | Dependency/risk | Reason |
|---:|---|---|---|---|---|
| 1 | Heading | Content | Stage A | low, no schema | Gives semantic H2–H6 control without storing headings inside generic rich text |
| 2 | Button / Button Group | Conversion | Stage A | low, no schema | Supports one or more actions independently from Hero/CTA |
| 3 | Video / Embed | Media | Stage A | medium, strict URL provider allowlist | High-value tourism content; must avoid arbitrary unsafe embed HTML |
| 4 | Stats / Counter | Content | Stage A | low, no schema | Useful for trust signals and destination highlights; render statically first |
| 5 | Tour Itinerary Timeline | Travel | Stage A | low-medium, no schema | Strong domain value and currently unavailable in generic Pages |
| 6 | Pricing Table | Travel/Conversion | Stage A | medium, no schema | Useful for packages and comparisons; must remain content data, not booking business logic |
| 7 | Quote | Content | Stage A optional | low, overlaps Testimonials | Add only if editorial attribution/citation differs materially from testimonial cards |
| 8 | Group / Section | Layout | Stage A foundation | **schema/design approval** | Required for nested composition and shared section styling |
| 9 | Columns / Grid | Layout | Stage A foundation | **depends on nesting** | Required for 2–4 column visual layouts; cannot be a truthful leaf block |
| 10 | List | Content | defer | overlaps sanitized Text | Add only if structured list controls are needed by the Stage B settings panel |
| 11 | Accordion / Tabs | Content | defer | partial overlap with FAQ; accessibility work | Tabs need a distinct use case before adding complexity |
| 12 | Review / Rating | Travel | defer | overlaps Testimonials | Existing testimonial items already support rating/avatar |
| 13 | Booking / Inquiry Widget | Conversion | defer | business-flow/security review | Contact Form already covers general inquiry; booking needs explicit product context |
| 14 | Destination Map | Travel | defer | overlaps Map | Extend Map only after requirements prove multiple markers or CMS destination binding |
| 15 | Image Carousel / Slider | Media | defer | overlaps Gallery | Existing Gallery already supplies carousel/lightbox behavior |

### Recommended Stage A shipment

Use small, reviewable batches after approval:

- **A3.1 — Authoring contract:** enrich registry metadata and document one repeatable block checklist. No new visual block yet.
- **A3.2 — Core leaf blocks:** Heading, Button Group, Video/Embed.
- **A3.3 — Travel leaf blocks:** Stats, Tour Itinerary Timeline, Pricing Table.
- **A3.4 — Layout foundation:** decide and implement Group + Columns only after explicit nesting/schema approval.

Do not start A3.3 until A3.2 is tested. Do not start A3.4 by embedding an alternate nested JSON system as a shortcut.

---

## 7. Shared authoring contract for every approved block

Each block should follow the existing architecture rather than introduce a parallel system.

### Registry metadata

Add only metadata needed by both the current editor and future Stage B inserter:

```php
'heading' => [
    'label' => 'Heading',
    'icon' => 'heading',
    'category' => 'content',
    'description' => 'Add a semantic section heading.',
    'keywords' => ['title', 'heading'],
    'supports' => [
        'background' => true,
        'spacing' => true,
        'alignment' => true,
        'children' => false,
    ],
],
```

Do not move validation into client-visible config. Server-side rules remain authoritative in `PageBlockService`. A future safe field-schema projection can be designed separately for Stage B without exposing raw Laravel validation internals.

### Per-block implementation checklist

For each approved type:

1. Registry metadata and default data.
2. Strict server-side validation and sanitization.
3. Admin form partial with inline errors and existing admin classes.
4. Frontend partial that renders nothing for empty content.
5. Theme-token-compatible output; no new hardcoded brand colors/fonts.
6. Accessible semantic markup and keyboard behavior where interactive.
7. Media picker reuse when media is needed.
8. CRUD/default/validation tests.
9. Empty-render and public-render tests.
10. Focused tests, full suite, and PHPStan before the batch is complete.
11. A short report recording impact and rollback.

### Style-support baseline

Before Stage B, declare a conservative common support model:

- background: existing shared object;
- spacing: tokenized preset names, not arbitrary CSS;
- alignment: allowlisted values relevant to the block;
- content width: allowlisted presets;
- children: boolean capability, false for all leaf blocks.

Actual controls should be added incrementally and validated server-side. The registry must describe capability; it must not become a place for executable rendering logic.

---

## 8. Likely implementation files — not approved for editing yet

The exact production set depends on which A3 batch the owner approves. The expected pattern is:

### Shared files

- `config/blocks.php` — add approved type metadata.
- `app/Services/PageBlockService.php` — defaults, validation, and sanitization.
- `tests/Feature/Admin/PageBlockManagementTest.php` — defaults, CRUD, validation/security.
- `tests/Feature/Frontend/GenericPageRenderingTest.php` — public and empty rendering.

### One pair per approved type

- `resources/views/backend/pages/partials/blocks/<type>.blade.php`
- `resources/views/frontend/blocks/<type>.blade.php`

### Possible protected files only if proven necessary

- `app/Http/Controllers/Admin/PageBlockController.php`
- `app/Http/Controllers/Frontend/PageController.php`
- `routes/admin.php`
- `app/Models/PageBlock.php`
- a new migration under `database/migrations/`

The protected list above is **not approved by this report**. It applies only to Group/Columns nesting or a proven data-preparation need and requires a narrow diff plan plus explicit owner approval.

No package/dependency file is expected for Stage A block expansion.

---

## 9. Verification plan for implementation batches

Minimum per approved batch:

```bash
php artisan test tests/Feature/Admin/PageBlockManagementTest.php
php artisan test tests/Feature/Frontend/GenericPageRenderingTest.php
vendor/bin/phpstan analyse --no-progress
```

Then run the full suite before marking the batch complete:

```bash
php artisan test
```

Manual checks:

- Add each new block from the existing page editor.
- Save valid and invalid field combinations.
- Reorder, hide, show, and delete without changing existing controls.
- Preview draft payload and render the published page.
- Confirm empty blocks emit no public wrapper.
- Confirm mobile layout, theme-token behavior, and keyboard/accessibility behavior.
- Confirm all 11 existing block types still add, update, preview, and render.

---

## 10. Implementation and validation result

### Implemented

- Enriched all block registry entries with category, description, keywords, and a consistent `supports` contract.
- Added Heading with semantic H2–H6 and alignment controls.
- Added Button Group with up to six validated links, three visual styles, and alignment controls.
- Added Video/Embed with YouTube/Vimeo allowlisting and server-side canonicalization to privacy-conscious embed URLs.
- Added matching admin forms, frontend renderers, defaults, validation, sanitization, completeness tests, public-render tests, and empty-render tests.
- Added Stats with bounded structured items and semantic `<dl>` output.
- Added Tour Itinerary with up to 30 validated timeline steps and semantic ordered-list output.
- Added Pricing Table with up to six plans, bounded feature lists, safe CTA URLs, and accessible responsive cards.
- Added canonical nested storage through nullable self-referencing `page_blocks.parent_block_id`.
- Added Group and Columns layout blocks. Columns accept Group blocks as column slots; Groups accept nested content blocks.
- Added same-page parent validation, cycle prevention, five-level depth limit, and sibling-scoped ordering.
- Added recursive rendering from one flat database query and nested transient preview payload support.
- Preserved nested parent mapping during page duplication and revision snapshot/restore.
- Deleting a container safely promotes its direct children to root through `nullOnDelete`.

### Files changed

- `config/blocks.php`
- `app/Services/PageBlockService.php`
- `app/Models/Page.php`
- `app/Models/PageBlock.php`
- `app/Services/PageService.php`
- `app/Support/PageRenderData.php`
- `app/Http/Controllers/Admin/PageBlockController.php`
- `app/Http/Controllers/Admin/PageController.php`
- `app/Http/Controllers/Frontend/PageController.php`
- `resources/views/backend/pages/partials/block-editor.blade.php`
- `resources/views/frontend/pages/_blocks.blade.php`
- `tests/Feature/Admin/PageBlockManagementTest.php`
- `tests/Feature/Admin/PageManagementTest.php`
- `tests/Feature/Frontend/GenericPageRenderingTest.php`
- `tests/Feature/Phase4/PageRevisionTest.php`
- `ai/reports/phase-5/a3-block-library-plan.md`

### Files created

- `resources/views/backend/pages/partials/blocks/heading.blade.php`
- `resources/views/backend/pages/partials/blocks/button-group.blade.php`
- `resources/views/backend/pages/partials/blocks/video-embed.blade.php`
- `resources/views/frontend/blocks/heading.blade.php`
- `resources/views/frontend/blocks/button-group.blade.php`
- `resources/views/frontend/blocks/video-embed.blade.php`
- `resources/views/backend/pages/partials/blocks/stats.blade.php`
- `resources/views/backend/pages/partials/blocks/tour-itinerary.blade.php`
- `resources/views/backend/pages/partials/blocks/pricing-table.blade.php`
- `resources/views/frontend/blocks/stats.blade.php`
- `resources/views/frontend/blocks/tour-itinerary.blade.php`
- `resources/views/frontend/blocks/pricing-table.blade.php`
- `database/migrations/2026_06_21_000003_add_parent_block_id_to_page_blocks_table.php`
- `resources/views/backend/pages/partials/blocks/group.blade.php`
- `resources/views/backend/pages/partials/blocks/columns.blade.php`
- `resources/views/frontend/blocks/group.blade.php`
- `resources/views/frontend/blocks/columns.blade.php`

### Validation

```text
php artisan test tests/Feature/Admin/PageBlockManagementTest.php tests/Feature/Admin/PageManagementTest.php tests/Feature/Frontend/GenericPageRenderingTest.php tests/Feature/Phase4/PageRevisionTest.php
PASS — 56 tests, 592 assertions

vendor/bin/phpstan analyse --no-progress
PASS — 0 errors

vendor/bin/pint config/blocks.php app/Services/PageBlockService.php tests/Feature/Admin/PageBlockManagementTest.php tests/Feature/Frontend/GenericPageRenderingTest.php
PASS

php artisan test
PASS — 610 tests, 3072 assertions

php artisan migrate:status | Select-String "2026_06_21_000003"
PASS — migration detected as Pending; development database was not mutated
```

The validation loop found and corrected three local implementation defects before the final green run: an empty Heading fallback, two redundant URL parser expressions reported by PHPStan, and one missing closure capture in nested preview validation. No existing test was removed or weakened.

### Impact

- **DB:** one additive nullable self-FK column. Existing rows remain valid roots because the default is `null`. No data backfill or destructive operation.
- **Routes:** none.
- **Backend:** block placement, sibling reorder, duplicate, revisions, and preview now understand nesting while preserving existing top-level behavior.
- **Frontend:** Group/Columns recursively render existing block partials; relation-backed FAQ/Product preparation traverses the tree without Blade queries.
- **Security:** parent must belong to the same page; cycles, invalid container children, excessive depth, unsafe links, and oversized payloads are rejected.
- **Packages:** none.

---

## 11. Risks and rollback

| Risk | Mitigation |
|---|---|
| Registry and service drift apart | Add completeness tests for every registered type before adding blocks |
| New blocks duplicate existing capabilities | Use the overlap decisions in Section 6; extend existing blocks where appropriate |
| Unsafe embed content | Store allowlisted HTTP(S) URLs only; never accept raw iframe/script HTML |
| Schema migration fails in an environment with database drift | Run `php artisan migrate:status`, back up data, then run the single migration through the normal deployment process |
| Invalid parent graph or cycle | Same-page container validation, cycle detection, depth limit, self-FK, and regression tests |
| Nested rendering introduces N+1 queries | Load visible blocks once, build the tree in memory, and prepare relation-backed data from the flattened in-memory tree |
| Revision restore loses parent IDs | Snapshot original IDs and restore relationships in a second mapping pass |
| Deleting a container removes content unexpectedly | `nullOnDelete` promotes direct children to root instead of cascading deletion |
| Frontend becomes visually inconsistent | Use Phase 3 theme tokens and the common `supports` contract |
| A3 grows into a rewrite | Implement one approved batch at a time and preserve the current editor as fallback |

Risk level:

- A3.1 registry metadata: **low**.
- A3.2 core leaf blocks: **medium-low**.
- A3.3 travel leaf blocks: **medium-low**.
- A3.4 nested schema/render/revision foundation: **medium-high**, mitigated by focused and full-suite coverage.

Rollback before migration: reverse only the files listed in Section 10 after reviewing their diff. After a focused commit, use `git revert <a3-commit>`. If the migration has been applied and no nested content must be retained, run the normal targeted rollback for this migration before reverting application code. Never reset or restore the whole repository.

---

## 12. Completion, commit readiness, and A4 gate

A3.1–A3.4 are complete and validated. The registry now exposes **19 block types**. Existing routes, authentication, packages, original migrations, existing block contracts, and top-level page behavior are preserved.

### Safe to commit?

**YES, with deployment notes.** The implementation is internally consistent, focused, formatter-clean, PHPStan-clean, and the full suite passes. Commit only the A3 files listed in Section 10; exclude existing untracked `.claude/` and `ai/promt/` content. Do not claim that the development database has already been migrated.

Before manual admin/frontend QA or deployment, explicitly approve and run the pending migration through the project's normal database-change process. The migration itself was fully exercised by `RefreshDatabase` in the test environment.

### Ready for A4?

**YES for A4 audit and code-level profiling.** A3 implementation is complete and no known automated regression remains. For live browser/database profiling in A4, apply the pending A3.4 migration first; otherwise use the isolated test database and clearly report the live-environment limitation.

Stage B remains out of scope until A4 and A5 are completed and the Stage A owner sign-off is given.
