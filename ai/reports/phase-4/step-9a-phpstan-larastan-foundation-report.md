# STEP 9A — PHPStan / Larastan Foundation and Level 5 Stabilization

## Scope and Git context

Resolve only the PHPStan/Larastan blocker from STEP 9. Performance remains out of scope.

- Branch: `feature/phase-4-step9-release-gate`
- Starting/current HEAD: `e2a3080bcd4e48923640086673e58cec1ddf395f`
- Claude baseline: `ace347ec0e4c6112d8e82672afb6b6429f1adaf1`
- Restore branch: `backup/pre-phase4-step9-claude-baseline` (verified at the baseline hash)
- No checkout, reset, restore, stash, clean, merge, rebase, commit, tag, or push was performed.

## Environment and compatibility

- Laravel Framework: `13.11.2`
- PHP: `8.3.30`
- Composer: `2.9.4`
- Larastan: `3.10.0`
- PHPStan: `2.2.2`

Larastan `^3.10.0` was selected because it requires PHP `^8.2`, supports Illuminate `^13`, and requires PHPStan `^2.2.0`.

Approved command:

```text
composer require --dev "larastan/larastan:^3.10.0" --no-interaction
```

The Windows invocation initially wrote exact constraint `3.10.0` because the caret was consumed. The manifest was normalized to `^3.10.0` and the lockfile resynchronized with a package-limited Composer operation.

## Dependency changes

- Added `larastan/larastan` `v3.10.0` as a development dependency.
- Added transitive `phpstan/phpstan` `2.2.2`.
- Added transitive `iamcal/sql-parser` `v0.7`.
- Composer resolution: 3 installs, 0 updates, 0 removals.
- No production, framework, or unrelated locked dependency changed.

## Configuration

Canonical file: `phpstan.neon`

```neon
includes:
    - vendor/larastan/larastan/extension.neon
    - vendor/nesbot/carbon/extension.neon

parameters:
    level: 5
    paths:
        - app
        - routes
```

- Effective level: 5
- Analysis paths: `app`, `routes`
- Application exclusions: none
- Ignore rules: none
- Baseline: not generated or used
- Composer script: `composer analyse` → `phpstan analyse --no-progress`

## Initial analysis and classification

Initial command: `vendor/bin/phpstan analyse --no-progress --error-format=json`.

Result: **95 errors**.

| Area | Errors |
|---|---:|
| Controllers and requests | 16 |
| Models | 11 |
| Services | 18 |
| Support classes | 50 |
| Console/contracts/facades/middleware/mail/observers/plugins/providers/routes | 0 |

Classification:

1. Configuration/integration: none; Larastan booted Laravel successfully.
2. Missing Eloquent relationship generics: primary source of cascade errors.
3. Low-risk typing: nullable access, return types, builder types, and collection callbacks.
4. Probable runtime defects: none confirmed after inspection.
5. False positives: none ignored; each finding received precise treatment.
6. Protected/architectural: narrow controller typing only; no public-contract change.
7. Owner decision required during stabilization: none.

## Fix batches

### Batch 1 — relationship typing

Added precise `HasMany`/`BelongsTo` generics to existing model relationships, explicit relation return types, narrow `PageBlock` display-property annotations, and the complete documented `Page::status` union.

Result: **95 → 31 errors**, with no executable behavior change.

### Batch 2 — low-risk typing and narrowing

- Reused a verified email user instance.
- Removed an unused `ThemeService` controller dependency.
- Added polymorphic `instanceof` narrowing in `MenuItem::resolveUrl()`.
- Preserved null-coalescing behavior while removing redundant nullsafe access.
- Corrected paginator current-page counting and explicit `str_pad()` conversion.
- Corrected optional theme-manifest PHPDoc keys.
- Removed one redundant `array_values()` call.

Result: **31 → 7 errors**.

### Batch 3 — final precise types

- Added local `Builder<Product>` assertions around the existing `publiclyVisible()` scope.
- Made product-feature collection callbacks and nullable filtering explicit.
- Corrected the default-media boolean guard.
- Removed the final redundant nullsafe access.

Result: **7 → 0 errors**.

## Files changed

Foundation:

- `composer.json`, `composer.lock`, `phpstan.neon`

Controllers/request:

- `app/Http/Controllers/Admin/SiteSettingController.php`
- `app/Http/Controllers/Admin/ThemeController.php`
- `app/Http/Controllers/Auth/VerifyEmailController.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Http/Requests/Auth/LoginRequest.php`

Models:

- `AuditLog.php`, `Menu.php`, `MenuItem.php`, `Page.php`, `PageBlock.php`
- `PageSection.php`, `PageSectionMedia.php`, `PageTemplate.php`
- `Product.php`, `ProductFaq.php`, `ProductFeature.php`, `ProductHighlight.php`
- `ProductImage.php`, `ProductItinerary.php`, `ProductNote.php`, `ProductPrice.php`
- `Theme.php`, `Widget.php`

Services/support:

- `app/Services/MenuService.php`, `app/Services/ThemeService.php`
- `BookingCtaSettings.php`, `CategoryDestinationDisplayState.php`, `DefaultMediaAssets.php`
- `FooterSettings.php`, `HomepageContent.php`, `NavigationSettings.php`
- `ProductDetailDisplayState.php`, `SeoDefaultSettings.php`, `SocialMediaLinkSettings.php`
- `StructuredDataSettings.php`, `TrackingIntegrationSettings.php`

Documentation:

- This report
- `ai/reports/phase-4/phase-4-progress-handoff.md`

## Final analysis

```text
composer analyse
Effective level: 5
Analysis paths: app, routes
Errors: 0
Result: PASS
```

No ignore rule was added. No baseline was created.

## Verification

- Focused auth/menu/page-section/frontend/theme/product tests: **211 tests, 1382 assertions, 0 failures**.
- Phase 4 tests: **204 tests, 454 assertions, 0 failures**.
- Full suite: **596 tests, 2765 assertions, 0 failures**.
- Counts are unchanged from STEP 9. No test or assertion was removed, skipped, weakened, or rewritten.
- `composer validate`: `./composer.json is valid`.

## Dependency audit

`composer audit --locked` reported **9 advisories affecting 6 existing packages**:

- `guzzlehttp/guzzle`: 2 medium
- `guzzlehttp/psr7`: 3 medium
- `laravel/framework`: 1 medium; installed 13.11.2 is affected below 13.12.0
- `symfony/http-foundation`: 1 medium
- `symfony/polyfill-intl-idn`: 1 low
- `symfony/routing`: 1 medium

The three STEP 9A packages are not in the advisory list. No security update was attempted because STEP 9A forbids unrelated framework/dependency updates. A separately approved security-update task is required.

## Preserved architecture and behavior

- Database/schema/migrations: unchanged
- Routes/public contracts: unchanged
- Authentication/authorization rules: unchanged
- Plugin lifecycle/sandboxing: unchanged
- Frontend/admin UI: unchanged
- Validation/tests: not weakened
- Claude Phase 4 services and abstractions: preserved

## Remaining blockers

1. STEP 9 performance target remains unresolved and was intentionally not worked on.
2. Dependency advisories require an owner-approved security update plan.
3. Phase 4 Release Gate remains `NO-GO` until remaining blockers are resolved and the main gate is rerun.

## Rollback

- Immediate checkpoint: `e2a3080bcd4e48923640086673e58cec1ddf395f`.
- Claude restore reference: `backup/pre-phase4-step9-claude-baseline` at `ace347ec0e4c6112d8e82672afb6b6429f1adaf1`.
- Restore only STEP 9A files after diff review and owner approval; do not reset, clean, or rewrite history.

## STEP 9A recommendation

**PASS**

The PHPStan/Larastan blocker is resolved at level 5 with zero errors, zero ignores, no baseline, valid Composer metadata, and no regression. This PASS applies only to STEP 9A; Phase 4 overall remains `NO-GO`.
