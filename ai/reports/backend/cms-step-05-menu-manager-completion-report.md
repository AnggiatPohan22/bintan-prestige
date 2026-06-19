# CMS STEP 5 - Menu Manager Completion Report

Date: 2026-06-19
Status: Completed

## Task

Complete and verify the existing Menu Manager so managed header and footer locations are the
frontend source of truth, internal targets stay publicly eligible, hierarchy and reorder writes
remain valid, and legacy Global Assets links are used only while a location has not yet been
managed.

## Baseline

- Active branch: `feature/codex-backend-cms-next`
- Restore branch: `backup/pre-codex-master-rules-20260618`
- Restore baseline: `df17e18`
- Previous completed task: STEP 4 Media Library integrity and Page Builder picker integration.
- The worktree already contained uncommitted STEP 1-4 work. It was preserved and was not folded
  into this report as new STEP 5 runtime work.

## Audit Result

STEP 5 was substantially implemented in the worktree before final verification. The completion
pass found two test-only failures: a deterministic factory slug collision and use of a `TestView`
method unavailable in the installed Laravel version. Both were corrected within the approved test
files. Additional assertions were added for every internal target type and for preventing a parent
that already owns children from becoming a child.

## Changed

- `app/Rules/EligibleMenuLinkTarget.php`
  - Validates that Page, Product, Category, and Destination targets are currently available on the
    public site before a menu item can reference them.
- `app/Rules/ValidMenuItemParent.php`
  - Restricts parents to root items in the same header menu.
  - Rejects footer children, grandchildren, self-parenting, and moving an item with children under
    another item.
- `app/Providers/AppServiceProvider.php`
  - Invalidates menu caches after Menu, MenuItem, Page, Product, Category, or Destination state
    changes, including Category and Destination restore events.
  - Shares both menu items and managed-location state with the frontend partials.
- `app/Services/MenuService.php`
  - Resolves all three locations through per-location cache entries while batching cold database
    resolution.
  - Filters stale internal targets at render time.
  - Distinguishes a missing location from an intentionally empty or disabled managed location.
  - Enforces exact, unique, single-sibling reorder payloads and persists reorder writes in a
    transaction.
  - Keeps sibling sort orders dense after create, move, and delete operations.
- `app/Http/Controllers/Admin/MenuController.php`
  - Supplies only publicly eligible Page, Product, Category, and Destination choices to the editor.
- `app/Http/Controllers/Admin/MenuItemController.php`
  - Uses the Menu service for next positions, reorder validation, dense sibling normalization, and
    cache refresh while preserving existing endpoints.
- `app/Http/Requests/Admin/StoreMenuItemRequest.php`
  - Applies eligible-target and valid-parent validation when creating items.
- `app/Http/Requests/Admin/UpdateMenuItemRequest.php`
  - Applies the same validation with update-aware self and child protection.
- `resources/views/backend/menus/edit.blade.php`
  - Shows the parent selector only for the header location; footer locations remain flat.
- `resources/views/frontend/partials/header.blade.php`
  - Uses the managed header tree for both desktop and mobile, including intentionally empty menus,
    with legacy navigation fallback only when the location is absent.
- `resources/views/frontend/partials/footer.blade.php`
  - Uses independently managed `footer_quick` and `footer_utility` sources, with per-location legacy
    fallback only while each location is absent.
- `tests/Feature/Admin/MenuManagementTest.php`
  - Covers authentication, CRUD targets, hierarchy, nested ownership, all internal target states,
    cache invalidation, exact sibling reorder, dense ordering, and batched cold-cache resolution.
- `tests/Feature/Frontend/MenuRenderingTest.php`
  - Covers identical desktop/mobile header trees, managed-empty behavior, legacy fallback, and
    independent Menu Manager footer sources.
- `ai/reports/backend/cms-step-05-menu-manager-completion-report.md`
  - Records the completed implementation, verification, impact, and rollback boundary.

## Preserved

- Header and mobile use the same `header` menu location and identical tree.
- `footer_quick` and `footer_utility` remain separate flat locations.
- Legacy Global Assets navigation and footer links remain available as transition fallback only
  when the corresponding Menu Manager location does not exist.
- URL and anchor links, `_self` and `_blank`, and existing `noopener noreferrer` rendering.
- Existing Alpine drag reorder UI and routes.
- Existing menu, menu item, Page, Product, Category, and Destination models and schema.
- Existing route names, controller method contracts, admin authorization, and public layout.

## Impact

- DB: none.
- Routes: none.
- Models: none.
- Packages: none.
- Frontend: managed-empty locations no longer resurrect legacy links; stale internal links disappear
  as soon as their target is no longer publicly eligible.
- Backend: parent and reorder integrity are enforced; cache invalidation follows every relevant
  source model state change.
- Security: cross-menu parents, invalid hierarchy changes, unavailable internal targets, duplicate
  or partial reorder sets, and mixed sibling groups are rejected through validation.

## Verification

- Initial focused STEP 5: 16 tests, 14 passed, 2 test-only errors.
- `php artisan test tests/Feature/Admin/MenuManagementTest.php tests/Feature/Frontend/MenuRenderingTest.php`
  after completion and Pint: 16 passed, 87 assertions.
- Focused STEP 2-5 regression: 49 passed, 328 assertions.
- `php artisan test`: 282 passed, 1,944 assertions.
- Laravel Pint on the ten STEP 5 PHP files: completed; four files received formatting-only fixes.
- `git diff --check`: passed.
- Browser desktop/mobile QA: not executed because the in-app browser process could not start under
  the Windows sandbox. Header/mobile/footer rendering and source selection were verified through
  feature tests; no visual-browser result is claimed.

## Rollback

Revert only the fourteen STEP 5 files listed in this report. Do not reset or clean the worktree
because approved uncommitted STEP 1-4 work is present. The pre-Codex restore point remains
`backup/pre-codex-master-rules-20260618` at `df17e18`; restoring to it requires explicit owner
instruction and a non-destructive plan.

## Remaining

- Repeat desktop and mobile browser QA when the local browser runtime is available.
- No known STEP 5 automated test failure remains.
- Changes remain uncommitted and unpushed, as no commit or push was requested.
