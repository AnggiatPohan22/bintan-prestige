# TEST-01 - Failing Tests After Security Baseline Report

Date: 2026-06-12
Status: Completed

## Summary

Fixed the failing test suite after the security baseline changes without weakening admin authorization, reopening public registration, or changing Laravel runtime logic.

The failures were stale test expectations in the Page Sections admin feature tests. The Page Sections admin flow now separates the page filter index from the selected page section table, so tests that expected section rows directly on the index page needed to target the section management route instead.

## Initial Test Result

Command:

```bash
php artisan test
```

Result:

- Tests: 105
- Passed: 101
- Failed: 4
- Assertions: 509

## Failed Tests Found

1. `Tests\Feature\Admin\PageSectionMediaSlotTest::test_admin_page_sections_syncs_registered_product_pages`
   - File: `tests/Feature/Admin/PageSectionMediaSlotTest.php`
   - Error message: Expected registered product section keys/media status on the Page Sections index response.
   - Root cause: The index page now renders page filter cards, while actual section rows are rendered by `admin.page-sections.sections`.
   - Classification: Stale test expectation after existing Page Sections UI flow change.

2. `Tests\Feature\Admin\PageSectionMediaSlotTest::test_admin_page_sections_index_can_filter_by_page_key`
   - File: `tests/Feature/Admin/PageSectionMediaSlotTest.php`
   - Error message: Expected page-specific section rows while calling the index route.
   - Root cause: Filtering section rows now belongs to the selected page sections route.
   - Classification: Stale test expectation.

3. `Tests\Feature\Admin\PageSectionMediaSlotTest::test_admin_page_sections_index_defaults_to_first_available_page`
   - File: `tests/Feature/Admin/PageSectionMediaSlotTest.php`
   - Error message: Expected `home.hero` section row on the index page.
   - Root cause: The index page lists available page options and no longer opens section rows by default.
   - Classification: Stale test expectation.

4. `Tests\Feature\Admin\PageSectionMediaSlotTest::test_admin_page_sections_index_uses_frontend_display_order`
   - File: `tests/Feature/Admin/PageSectionMediaSlotTest.php`
   - Error message: Expected `home.faq` and `home.footer_cta` rows in frontend display order on the index page.
   - Root cause: Frontend display order applies to the selected page sections table, not the page filter index.
   - Classification: Stale test expectation.

## Root Cause Per Failed Test

All four failures came from `PageSectionMediaSlotTest` expecting section table content on `admin.page-sections.index`.

The current admin Page Sections structure is:

- `admin.page-sections.index`: page filter/card overview.
- `admin.page-sections.sections`: selected page section table.

The tests were updated to assert against the correct route for each responsibility.

## Files Changed

- `tests/Feature/Admin/PageSectionMediaSlotTest.php`
- `ai/reports/testing/test-01-failing-tests-after-security-baseline-report.md`

## Whether Each Fix Changed Test Or Runtime Code

- `test_admin_page_sections_syncs_registered_product_pages`: test-only change.
- `test_admin_page_sections_can_filter_section_rows_by_page_key`: test-only change.
- `test_admin_page_sections_index_lists_available_page_options`: test-only change.
- `test_admin_page_sections_index_uses_frontend_display_order`: test-only change.

No runtime Laravel code was changed.

## Final Php Artisan Test Result

Command:

```bash
php artisan test
```

Result:

- Tests: 105
- Passed: 105
- Failed: 0
- Assertions: 524

Target achieved: full test suite is green.

## Additional Verification

Focused Page Sections suite:

```bash
php artisan test --filter=PageSectionMediaSlotTest
```

Result:

- Tests: 13
- Passed: 13
- Assertions: 72

## Security Impact

- Public registration remains disabled.
- Admin route protection remains enforced.
- `is_admin` authorization baseline remains intact.
- No admin middleware, auth route, user model, controller, migration, or database schema was changed.
- Tests continue using admin-capable users where admin routes are exercised.

## Remaining Risks

- Granular authorization policy/gate implementation is still pending after SECURITY-06A.
- Page Sections tests now match the current route split, but deeper authorization tests for per-action admin permissions are still future work.

## Recommended Next Step

Proceed to the next security authorization step only after committing or otherwise preserving the green TEST-01 baseline.
