# CMS STEP 7 - Frontend Page, Template, and SEO Report

Date: 2026-06-19
Status: Completed

## Task

Complete generic Page frontend metadata, canonical URLs, Open Graph fallbacks, template-specific
structured data, strict template resolution, empty-block rendering, responsive behavior,
accessibility, and query-level performance for pages containing many blocks.

## Baseline

- Active branch: `feature/codex-backend-cms-next`
- Restore branch: `backup/pre-codex-master-rules-20260618`
- Restore baseline: `df17e18`
- Previous completed task: STEP 6 Page Builder Admin UX completion.
- Initial Generic Page baseline: 7 passed, 65 assertions.
- Existing uncommitted STEP 1-6 work was preserved.

## Changed

- `app/Support/PageTemplateRegistry.php`
  - Defines the exact executable template allowlist: `default`, `full-width`, and `contained`.
  - Maps allowlisted templates to Blade views and `WebPage` or `Article` schema types.
- `app/Support/PageRenderData.php`
  - Resolves all FAQ library references in one query and prepares visible FAQ schema items.
  - Reuses one prepared Product collection for repeated Product Grid blocks with identical filters
    and limits.
- `app/Http/Controllers/Frontend/PageController.php`
  - Delegates relation-backed block preparation to `PageRenderData`.
  - Resolves templates only through the exact allowlist.
  - Prepares page/global SEO fallbacks, public canonical URL, social metadata, preview robots,
    template schema type, and FAQ schema data.
- `app/Http/Controllers/Admin/PageController.php`
  - Lists only active, allowlisted templates in Page create/edit screens.
- `app/Http/Requests/Admin/StorePageRequest.php`
- `app/Http/Requests/Admin/UpdatePageRequest.php`
  - Reject inactive or unsupported template IDs.
- `app/Support/SeoDefaultSettings.php`
  - Resolves relative canonical paths against the configured canonical base URL, falling back to
    the application URL when no global base is configured.
- `app/Support/StructuredDataBuilder.php`
  - Adds generic `WebPage` and contained-template `Article` schema.
  - Adds generic Page breadcrumbs and Page FAQ schema while preserving Product schema behavior.
- `resources/views/partials/site-structured-data.blade.php`
  - Passes Page, template schema type, prepared Page FAQ data, and Page image context to the shared
    JSON-LD graph.
- `resources/views/frontend/blocks/gallery.blade.php`
  - Adds carousel semantics, keyboard-operable images, accessible lightbox dialog, focus entry,
    focus return, focus trapping, labeled controls, responsive modal sizing, async image decoding,
    and autoplay timer cleanup.
- `resources/views/frontend/blocks/cta.blade.php`
  - Avoids empty CTA sections and adds responsive spacing, labeling, wrapping, and focus state.
- `resources/views/frontend/blocks/faq.blade.php`
  - Connects question buttons and answer regions with stable IDs, ARIA state, and focus styling.
- `resources/views/frontend/blocks/map.blade.php`
  - Adds a labeled section and responsive aspect-ratio container while preserving lazy iframe
    loading and the existing referrer policy.
- `resources/views/frontend/blocks/products-grid.blade.php`
  - Produces no public markup or inline style when no products resolve.
- `tests/Feature/Frontend/GenericPageRenderingTest.php`
  - Covers global SEO and Open Graph fallbacks, canonical base URLs, preview noindex behavior,
    template schemas, breadcrumb/FAQ JSON-LD, block accessibility/responsiveness, and empty output.
- `tests/Feature/Frontend/GenericPagePerformanceTest.php`
  - Exercises 39 visible blocks and verifies one Page Block query, one Product query for six
    identical Product Grid configurations, and one FAQ query for repeated FAQ blocks.
- `tests/Feature/Admin/PageManagementTest.php`
  - Covers allowlisted template display and rejection of inactive or unsupported templates.

## Preserved

- Existing Page schema, routes, models, templates, preview route, and public published-only rule.
- Existing Page-level meta title, description, and OG image precedence.
- Global SEO, business identity, menu, and structured-data settings.
- Product detail/listing structured data and relation preparation.
- All ten block types and their stored JSON contracts.
- Divider blocks remain renderable because the visual divider itself is intentional content.

## SEO and Structured Data Contract

- Title: Page meta title, then Page title, then the existing global layout rules.
- Description: Page meta description, then global meta description.
- Social title: Page meta title or Page title.
- Social description: Page meta description, then global OG description, then global description.
- Social image: Page OG image, then global SEO default image, then global social-share image.
- Canonical: the public Page path resolved against the configured canonical base URL.
- Preview: canonical remains the public Page URL and robots becomes `noindex, nofollow`.
- Schema: `WebPage` for Standard/Full-width, `Article` for Contained, plus BreadcrumbList and
  FAQPage when visible prepared FAQ content exists.

## Performance Audit

- FAQ library blocks remain one-query batched regardless of repeated references.
- Identical Product Grid filter/limit combinations reuse one eager-loaded Product collection.
- Each genuinely unique Product Grid configuration still requires its own bounded query set; this
  preserves per-block filters and limits without loading the entire Product catalogue.
- Product Grid limits remain capped at 12 items per block.
- Gallery and content images remain lazy loaded; Gallery adds async decoding; Map remains lazy.
- Global settings and Menu Manager data keep their existing caches.
- Remaining runtime risks:
  - rendered HTML/DOM still grows with the number of visible blocks and repeated product cards;
  - Hero uses a CSS background image without responsive `srcset` or verified preload, so it remains
    the primary LCP risk;
  - CLS, LCP, and INP require browser measurement and cannot be claimed from feature tests.

## Impact

- DB/schema/migrations: none.
- Routes: none.
- Models: none.
- Packages: none.
- Frontend: generic Pages now have consistent fallback SEO, allowlisted templates, Page-aware
  schema, empty-output protection, and stronger responsive/accessibility behavior.
- Backend: relation-backed Page data is prepared once per unique configuration before Blade.
- Security: unsupported template records cannot be selected, saved, or executed.

## Verification

- Initial Generic Page baseline: 7 passed, 65 assertions.
- Focused STEP 7: 18 passed, 145 assertions.
- SEO/Product/global structured-data regression: 96 passed, 906 assertions.
- Focused STEP 2-7 regression: 59 passed, 412 assertions.
- `php artisan test`: 292 passed, 2,028 assertions.
- Laravel Pint on changed PHP files: passed after formatting.
- Blade view compilation and compiled-view cleanup: passed.
- `git diff --check`: passed.
- Browser responsive/accessibility/Lighthouse QA: not executed because the in-app browser process
  could not start under the Windows sandbox. No visual or Core Web Vitals result is claimed.

## Rollback

Revert only the eighteen STEP 7 files listed in this report. Do not reset or clean the worktree
because approved uncommitted STEP 1-6 work remains present. The pre-Codex restore point remains
`backup/pre-codex-master-rules-20260618` at `df17e18`; restoration requires explicit owner
instruction and a non-destructive plan.

## Remaining

- Repeat desktop/mobile keyboard, focus-order, gallery dialog, and map QA when browser runtime is
  available.
- Measure LCP, CLS, INP, transferred image bytes, and DOM size on a representative published Page.
- Consider responsive Hero image sources/preload only as a separate measured optimization task.
- No known STEP 7 automated test failure remains.
- Changes remain uncommitted and unpushed.
