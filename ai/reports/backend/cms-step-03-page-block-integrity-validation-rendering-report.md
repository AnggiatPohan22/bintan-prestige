# CMS STEP 3 - Page Block Integrity, Validation, and Rendering Report

Date: 2026-06-18
Status: Completed

## Task

Close the three remaining Generic Pages / Page Builder failures without changing database schema, migrations, routes, models, the global frontend layout, packages, or the editor UI.

## Baseline and Previous Step

- Active branch: `feature/codex-backend-cms-next`
- Restore branch: `backup/pre-codex-master-rules-20260618`
- Restore baseline: `df17e18`
- Latest completed implementation before this task: STEP 2 Generic Pages, Page Blocks, Menus, and Media Library baseline.
- STEP 3 audit had already identified three failures: nested block ownership, Page SEO data not reaching the frontend layout, and a direct FAQ model query in Blade.

The worktree already contained uncommitted STEP 1/2 files before STEP 3. They were preserved and were not folded into this implementation report as new STEP 3 runtime changes.

## Changed

- `app/Http/Controllers/Admin/PageBlockController.php`
  - Rejects update, delete, and visibility toggle when the nested block does not belong to the page in the route.
  - Delegates block-type validation and sanitization to `PageBlockService` before persistence.
- `app/Services/PageBlockService.php`
  - Adds allowlisted schemas for all ten block types and shared background settings.
  - Validates URLs, image paths, relation IDs, limits, enums, booleans, and numeric ranges.
  - Normalizes comma-separated FAQ IDs used by the existing editor.
  - Removes unknown JSON keys.
  - Sanitizes rich HTML to an allowed tag/attribute set, removes event/style attributes and unsafe link protocols, and adds `noopener noreferrer` to links opening a new tab.
- `app/Http/Controllers/Frontend/PageController.php`
  - Passes Page title, description, OG image, and canonical URL to the existing global SEO layout contract.
  - Resolves all ID-based FAQ blocks with one query and preserves each block's configured FAQ order.
  - Prepares inline FAQ items before Blade rendering.
- `resources/views/frontend/blocks/faq.blade.php`
  - Renders only controller-prepared FAQ items and no longer imports or queries the FAQ model.
- `tests/Feature/Admin/PageBlockManagementTest.php`
  - Covers nested ownership for update, delete, and visibility toggle.
  - Covers schema rejection for unsafe links/paths, invalid ranges, missing relations, and invalid enums.
  - Covers JSON allowlisting and rich HTML/link sanitization.
- `tests/Feature/Frontend/GenericPageRenderingTest.php`
  - Covers SEO metadata and canonical output.
  - Covers active FAQ rendering, configured ID order, inactive exclusion, single-query resolution, and the no-query Blade contract.

## Preserved

- Existing ten block types and their editor field names.
- Existing nested routes and controller method contracts.
- Draft preview and public published-page behavior.
- Existing template lookup and default-template fallback.
- Existing Products Grid preparation and limits.
- Existing admin block editor UI.
- Existing database schema and stored JSON column.

## Impact

- DB: none.
- Routes: none.
- Frontend: Page SEO now reaches the existing layout; FAQ blocks render prepared active FAQ data without Blade queries.
- Backend: page/block ownership is enforced for all block mutations with a block route parameter; block JSON is schema-validated and allowlisted.
- Security: unsafe nested mutations, dangerous URL schemes, path traversal, unknown JSON keys, unsafe rich-text attributes, and unsafe rich-text links are rejected or removed.

## Verification

- Baseline focused STEP 3 before implementation: 12 tests, 9 passed, 3 failed.
- `php artisan test tests/Feature/Admin/PageBlockManagementTest.php tests/Feature/Frontend/GenericPageRenderingTest.php` - 15 passed, 141 assertions.
- `php artisan test tests/Feature/Admin/MediaLibraryTest.php tests/Feature/Admin/MenuManagementTest.php tests/Feature/Admin/PageBlockManagementTest.php tests/Feature/Admin/PageManagementTest.php tests/Feature/Frontend/GenericPageRenderingTest.php` - 33 passed, 225 assertions.
- `php artisan test` - 266 passed, 1,841 assertions.
- `php vendor/bin/pint ...` on the five changed PHP files - completed.
- `git diff --check` - passed.

## Rollback

Revert only the seven STEP 3 files listed in this report. Do not reset the whole worktree because unrelated uncommitted STEP 1/2 work is present. The pre-Codex restore point remains `backup/pre-codex-master-rules-20260618` at `df17e18`; any restore to it must be explicitly approved and performed non-destructively.

## Remaining

- No known STEP 3 test failure remains.
- Changes remain uncommitted, as no commit or push was requested.
