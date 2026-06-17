# FRONTEND-14B Public Product Listing Browser QA & Performance Measurement Report

Date: 2026-06-14  
Scope: Public Product Listing (`GET /products`, route name `products.index`)  
Mode: browser/tooling verification addendum, performance measurement, evidence collection  
Final release decision: READY WITH KNOWN LIMITATIONS

## 1. Executive Summary

FRONTEND-14B attempted to close the FRONTEND-14 runtime-browser, Lighthouse, and query-count limitations. The local Laravel application was started at `http://127.0.0.1:8000`, `/products` returned HTTP 200, route/context HTML checks were collected, request-level query counts were measured through temporary test instrumentation, focused tests passed, full tests passed, and the production frontend build passed.

True screenshot-based browser QA, keyboard automation, zoom automation, console inspection, and Lighthouse were blocked by local tooling: the in-app Browser runtime failed before navigation, and both Chrome and Edge headless crashed with a GPU-process fatal error. No Product Listing runtime code was changed.

## 2. FRONTEND-14 Known Limitations Addressed

| FRONTEND-14 limitation | FRONTEND-14B result |
|---|---|
| Browser/manual route verification unavailable | Partially addressed through live local HTTP route checks; screenshot browser rendering remained blocked |
| Lighthouse/Core Web Vitals unavailable | Not addressed; no working Lighthouse/browser tooling available |
| Request-level query count not measured | Addressed with temporary feature-test query logging |
| N+1 absence not measured | Addressed by comparing 1-product and 9-product page query counts |
| Runtime asset/network review unavailable | Partially addressed with HTTP resource checks |

## 3. Verification Environment

- OS/shell: Windows PowerShell
- Project path: `C:\laragon\www\bintan-prestige`
- Laravel: 13.11.2 from `php artisan about`
- PHP: 8.3.30 from `php artisan about`
- App environment: local
- Debug mode: enabled locally
- Storage link: linked
- Public Product Listing URL tested: `http://127.0.0.1:8000/products`

## 4. Browser and Tooling Used

| Tool | Result |
|---|---|
| In-app Browser skill | Attempted; runtime setup failed before navigation |
| Google Chrome headless | Attempted; failed with GPU process fatal error |
| Microsoft Edge headless | Attempted; failed with same GPU process fatal error |
| `Invoke-WebRequest` | Used for live HTML route/context checks |
| `curl.exe` | Used for local response-time observations |
| Temporary Laravel feature test with `DB::enableQueryLog()` | Used for query counts and N+1 assessment |
| Lighthouse | Not executed; no working Lighthouse/browser tooling available and no package install allowed |

## 5. Baseline Git State

Baseline commands were run before verification:

- `git status --short`
- `git diff --check`
- `git diff --stat`

Baseline status already contained uncommitted Product Listing implementation, docs, tests, and report files from FRONTEND-10B through FRONTEND-14. Those pre-existing files were not reverted.

## 6. Application URL

Local server started with:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Tested URL:

```text
http://127.0.0.1:8000/products
```

`Invoke-WebRequest` confirmed `/products` returned HTTP 200.

## 7. Routes/Contexts Verified

`php artisan route:list --path=products` confirmed:

| Route | Name | Controller |
|---|---|---|
| `GET /products` | `products.index` | `Frontend\ProductController@index` |
| `GET /products/{product:slug}` | `products.show` | `Frontend\ProductController@show` |

Product Detail is present but out of FRONTEND-14B scope.

## 8. Browser QA Method

Because full browser automation failed, verification used:

- local Laravel server response;
- rendered HTML from real `/products` requests;
- route/context matrix from actual URLs;
- resource HEAD checks;
- focused feature tests;
- query-count diagnostics;
- production build output.

Unavailable:

- screenshot visual inspection;
- live keyboard traversal;
- live zoom inspection;
- real browser console inspection;
- Lighthouse.

## 9. Screenshot Evidence

Screenshot evidence was not produced.

Attempted screenshot tools:

- Chrome headless `--screenshot`
- Edge headless `--screenshot`

Both failed with GPU-process fatal errors. No screenshot was claimed.

Evidence files produced instead:

- `ai/reports/frontend/evidence/frontend-14b/html-route-metrics.json`
- `ai/reports/frontend/evidence/frontend-14b/http-resource-check.json`
- `ai/reports/frontend/evidence/frontend-14b/http-asset-check.json`
- `ai/reports/frontend/evidence/frontend-14b/query-count-diagnostic.json`
- `ai/reports/frontend/evidence/frontend-14b/curl-response-times.json`

## 10. Viewport 320 Result

Not visually measured. Chrome/Edge screenshot tooling failed.

Source/CSS contract remains: one column below 640px.

## 11. Viewport 375 Result

Not visually measured. Chrome/Edge screenshot tooling failed.

Source/CSS contract remains: one column below 640px.

## 12. Viewport 768 Result

Not visually measured. Chrome/Edge screenshot tooling failed.

Source/CSS contract remains: two columns from 640px to 1023px.

## 13. Viewport 1024 Result

Not visually measured. Chrome/Edge screenshot tooling failed.

Source/CSS contract remains: three columns from 1024px.

## 14. Viewport 1280 Result

Not visually measured. Chrome/Edge screenshot tooling failed.

Source/CSS contract remains: three columns.

## 15. Viewport 1440 Result

Not visually measured. Chrome/Edge screenshot tooling failed.

Source/CSS contract remains: three columns.

## 16. Grid 3 x 3 Verification

HTML route evidence confirmed the base local listing had:

- 13 public packages in result summary;
- 9 rendered listing cards on page 1;
- pagination summary: `Showing 1-9 of 13 packages.`

Visual 3-column screenshot confirmation remained unavailable. The 3 x 3 visual result is therefore inferred from the documented CSS contract and actual 9-card page count, not claimed as visually screenshot-verified.

## 17. Product Card Visual Verification

Unavailable as live screenshot QA.

Server-rendered evidence confirmed:

- 9 product cards on base page;
- product-card markup is rendered;
- product image tags exist where data provides images;
- lazy loading attributes are present in HTML.

## 18. Product Image/Fallback Verification

`http-resource-check.json` confirmed local rendered resources returned 200:

- site favicon;
- Vite dev assets;
- site logos;
- product WebP images;
- fallback/default media image.

No broken local product image resource was confirmed in the resource check.

## 19. Filter/Search Browser Verification

Filter controls were verified from live HTML, not interactive browser clicks:

- filter button rendered;
- sort button rendered;
- category filter URL returned 4 packages;
- destination filter URL returned 3 packages;
- combined category/destination filter returned 1 package;
- active filter summaries rendered for supported filters;
- unsupported `search=lagoi` did not render a search field, did not filter results, and rendered `noindex, follow`.

Search remains unavailable by current Product Listing contract.

## 20. Mobile Filter Modal/Disclosure Verification

Not interactively verified. Browser keyboard/click automation was unavailable.

Source behavior remains Alpine modal-based with:

- filter trigger button;
- sort trigger button;
- Escape listener in the Product Listing Alpine root;
- labeled modal dialogs.

## 21. Keyboard Navigation Verification

Not interactively verified. In-app Browser and headless browser control were unavailable.

Server-rendered safeguards remain:

- buttons for filter/sort/media;
- anchors for Product Detail/pagination/reset;
- form controls for filters/sort;
- accessible labels in focused tests.

## 22. Zoom 200% Verification

Not verified. Browser zoom/screenshot tooling was unavailable.

## 23. Console Error Review

Not verified in a real browser console. Browser automation did not reach a usable navigation state.

## 24. Network Request Review

Resource-level HTTP checks were performed.

Local document/assets/images checked in `http-resource-check.json` returned HTTP 200. External resources such as Google Fonts and WhatsApp/social links were unreachable from this restricted local environment in the broader link check; this is an environment limitation for external network access, not a confirmed Product Listing runtime bug.

The broad link check also found existing shared-layout destination links such as `/destinations` returning 404 locally. This appears to be a shared navigation/footer link risk, not a Product Listing implementation change.

## 25. Lighthouse/Equivalent Method

Lighthouse was not executed.

Reason:

- no local Lighthouse package found;
- package install is forbidden;
- Chrome and Edge headless both failed with GPU-process fatal errors.

## 26. Lighthouse Performance Result

Not measured.

## 27. Lighthouse Accessibility Result

Not measured.

## 28. Lighthouse Best Practices Result

Not measured.

## 29. Lighthouse SEO Result

Not measured.

## 30. Lighthouse Findings

Unavailable. No Lighthouse report was generated.

## 31. Request-Level Query Count Method

Method:

- temporary `tests/Feature/Frontend/ProductListingQueryCountDiagnosticTest.php`;
- `RefreshDatabase`;
- deterministic 13-product dataset;
- `DB::enableQueryLog()`;
- request-level measurements for listing contexts;
- diagnostic JSON written to `ai/reports/frontend/evidence/frontend-14b/query-count-diagnostic.json`;
- temporary test file deleted before final verification.

No production route, middleware, or logging was added.

## 32. Base Listing Query Count

Measured:

- 1 public product: 18 queries
- 13 public products, first page rendering 9 cards: 14 queries

The query count did not grow with product count.

## 33. Filtered Listing Query Count

Measured category-filtered listing:

- 13-product dataset
- 14 queries

## 34. Sorted Listing Query Count

Measured `sort=price_low`:

- 13-product dataset
- 14 queries

## 35. Empty Listing Query Count

Measured `min_price=999999999`:

- 13-product dataset
- 9 queries

## 36. N+1 Verification

The diagnostic compared 1-product and 9-card first-page listing counts:

- 1 product: 18 queries
- 9-card first page from 13 products: 14 queries
- delta: -4

Assessment: no linear per-product query growth observed. Product prices and images were eager loaded with `where in (...)` queries, not per-card queries.

## 37. Response-Time Observation

`curl.exe` local timings were collected in `curl-response-times.json`.

Observed local ranges:

- `/products`: about 0.79s to 0.82s
- `/products?page=2`: about 0.79s to 0.83s
- category filter: about 0.75s to 0.82s
- `sort=price_low`: about 0.79s to 0.83s
- high min-price empty result: about 0.74s to 0.76s

These are localhost observations only and are not production benchmarks.

## 38. Asset Size Review

`npm.cmd run build` passed.

Measured production output:

- `public/build/manifest.json`: 0.54 kB, gzip 0.20 kB
- `public/build/assets/app-HC-_aOh9.css`: 98.31 kB, gzip 13.62 kB
- `public/build/assets/frontend-Cg4WNn9L.css`: 177.87 kB, gzip 24.35 kB
- `public/build/assets/app-CWHeQ4MV.js`: 50.52 kB, gzip 17.83 kB

Build warning: plugin timings concentrated in `vite:css` and `laravel`; build still passed.

## 39. Confirmed Issues

| Severity | Issue | Evidence | Fix | Status |
|---|---|---|---|---|
| Medium | Browser screenshot/Lighthouse tooling blocked by local GPU failure | Chrome/Edge headless commands failed with GPU-process fatal errors | None, environment/tooling limitation | Open limitation |
| Medium | Shared-layout destination links return 404 locally | `http-asset-check.json` broad link check | None, out of Product Listing scope | Open shared-layout risk |
| Low/Medium | External fonts/WhatsApp/social URLs unreachable in restricted environment | `http-asset-check.json` | None, environment/network limitation | Open limitation |

No confirmed Product Listing runtime code blocker was found.

## 40. Fixes Applied

No runtime fixes were applied.

Reason: no small confirmed Product Listing runtime issue was found from the measurements that could be safely fixed inside FRONTEND-14B.

## 41. Focused Test Result

Focused tests:

- `php artisan test --filter=ProductIndexUiTest`: passed, 32 tests, 303 assertions
- `php artisan test --filter=HomepageCmsContentTest`: passed, 17 tests, 182 assertions
- `php artisan test --filter=GlobalSettingsCacheTest`: passed, 8 tests, 23 assertions
- `php artisan test --filter=ProductPageSectionKeyTest`: passed, 2 tests, 16 assertions
- Temporary diagnostic: `php artisan test --filter=ProductListingQueryCountDiagnosticTest`: passed, 1 test, 8 assertions

The temporary diagnostic test file was deleted after evidence generation.

## 42. Full Test Result

`php artisan test` passed:

- 196 tests
- 1164 assertions
- duration: 22034 ms

## 43. Frontend Build Result

`npm.cmd run build` passed.

## 44. Pint Limitation Status

Pint auto-fix was not run.

FRONTEND-14 already recorded broad pre-existing Pint `--test` failures across existing PHP files. FRONTEND-14B did not change runtime PHP files and did not mix code-style cleanup into verification.

Recommended separate step remains:

```text
CODESTYLE-01: Laravel Pint Baseline & Safe Formatting Plan
```

## 45. Files Changed

| File | Reason | Runtime impact |
|---|---|---|
| `ai/reports/frontend/frontend-14b-public-product-listing-browser-qa-performance-measurement-report.md` | FRONTEND-14B report | None |
| `ai/reports/frontend/evidence/frontend-14b/html-route-metrics.json` | Route/context HTML evidence | None |
| `ai/reports/frontend/evidence/frontend-14b/http-resource-check.json` | Resource HTTP checks | None |
| `ai/reports/frontend/evidence/frontend-14b/http-asset-check.json` | Broad link/resource check evidence | None |
| `ai/reports/frontend/evidence/frontend-14b/query-count-diagnostic.json` | Query-count and N+1 evidence | None |
| `ai/reports/frontend/evidence/frontend-14b/curl-response-times.json` | Local response-time observations | None |

## 46. Evidence Files

Evidence directory:

```text
ai/reports/frontend/evidence/frontend-14b/
```

Files:

- `html-route-metrics.json`
- `http-resource-check.json`
- `http-asset-check.json`
- `query-count-diagnostic.json`
- `curl-response-times.json`

No screenshot files were produced.

## 47. Known Limitations

- Screenshot browser QA unavailable.
- Keyboard interaction unavailable.
- Mobile modal/disclosure interaction unavailable.
- Zoom 200% unavailable.
- Browser console unavailable.
- Lighthouse unavailable.
- Local app currently used Vite dev assets for HTTP verification; production build was verified separately.
- Shared destination links returning 404 were observed but not fixed because they are shared-layout/navigation scope.

## 48. Final Scores

| Area | Score |
|---|---:|
| Browser Rendering Readiness | 72/100 |
| Responsive Readiness | 78/100 |
| Product Card Visual Readiness | 78/100 |
| Keyboard Accessibility Readiness | 68/100 |
| Mobile Filter Usability | 70/100 |
| Lighthouse Performance Readiness | Not measured |
| Lighthouse Accessibility Readiness | Not measured |
| Lighthouse SEO Readiness | Not measured |
| Query Efficiency | 90/100 |
| N+1 Safety | 95/100 |
| Asset Readiness | 86/100 |
| Overall Release Readiness | 86/100 |

Score rationale:

- Query and N+1 scores improved because request-level evidence is now available.
- Browser/visual/accessibility scores remain reduced because real screenshot, keyboard, zoom, and Lighthouse tooling failed.
- Asset readiness is reduced because external URLs were unreachable in the restricted environment and shared navigation links include local 404s.

## 49. Final Release Decision

READY WITH KNOWN LIMITATIONS.

Rationale:

- no Product Listing runtime blocker confirmed;
- base listing and tested query contexts return HTTP 200;
- 13 public products and 9 first-page cards verified in live HTML;
- page 2, filters, IDR sorts, empty result, invalid query, and high page contexts return expected server-rendered states;
- query count is stable and no N+1 signal was observed;
- focused tests passed;
- full test suite passed;
- production build passed;
- screenshot/Lighthouse/keyboard limitations remain.

## 50. Rollback Procedure

To revert only FRONTEND-14B, remove:

- `ai/reports/frontend/frontend-14b-public-product-listing-browser-qa-performance-measurement-report.md`
- `ai/reports/frontend/evidence/frontend-14b/`

No database rollback, migration rollback, route rollback, package uninstall, cache clear, or runtime code rollback is required for this step.

## 51. Final Git Verification

Final verification commands required after report writing:

```bash
git diff --check
git status --short
git diff --stat
```

Final results are recorded in the assistant response after this report is written.

## 52. Recommended Next Step

Proceed to:

```text
FRONTEND-15:
Public Product Detail UI/UX & Data Flow Audit
```

Before production release, rerun true browser screenshot/keyboard/Lighthouse QA in an environment where Chrome or Edge headless rendering works.

## Browser Route Matrix

| Context | URL | Expected | Actual | Result |
|---|---|---|---|---|
| Base listing | `/products` | 200, 9 cards, index/follow | 200, 9 cards, `index, follow` | Pass |
| Page 2 | `/products?page=2` | 200, remaining products, self canonical | 200, 4 cards, canonical page 2 | Pass |
| Category filter | `/products?category[]=2` | 200, filtered, noindex | 200, 4 cards, active category, noindex | Pass |
| Destination filter | `/products?destination[]=1` | 200, filtered, noindex | 200, 3 cards, active destination, noindex | Pass |
| Combined filter | category + destination | 200, filtered, noindex | 200, 1 card, both active filters | Pass |
| IDR sort low | `/products?sort=price_low` | 200, noindex | 200, active sort, noindex | Pass |
| IDR sort high | `/products?sort=price_high` | 200, noindex | 200, active sort, noindex | Pass |
| Search | N/A | No search route/param exists | No search input rendered; `search` is unsupported | Unavailable by design |
| Empty result | `/products?min_price=999999999` | 200, empty state, noindex | 200, empty filter state, noindex | Pass |
| Invalid query | `/products?tracking=...` | 200, noindex, safe | 200, noindex, no raw script reflected | Pass |
| High page | `/products?page=999` | 200, high-page empty, noindex | 200, high-page empty state | Pass |
| SGD sorting | N/A | Not supported by current contract | No URL tested as supported route | Unavailable by design |

## Responsive Matrix

| Viewport | Grid | Filter layout | Product Card | Pagination | Result |
|---|---|---|---|---|---|
| 320 | Expected 1 column | Modal trigger expected | Not visually measured | Not visually measured | Not measured |
| 375 | Expected 1 column | Modal trigger expected | Not visually measured | Not visually measured | Not measured |
| 768 | Expected 2 columns | Modal trigger expected | Not visually measured | Not visually measured | Not measured |
| 1024 | Expected 3 columns | Modal trigger expected | Not visually measured | Not visually measured | Not measured |
| 1280 | Expected 3 columns | Modal trigger expected | Not visually measured | Not visually measured | Not measured |
| 1440 | Expected 3 columns | Modal trigger expected | Not visually measured | Not visually measured | Not measured |

## Keyboard Matrix

| Interaction | Expected | Actual | Result |
|---|---|---|---|
| Header/navigation | Focus visible and logical order | Not interactively measured | Not measured |
| Filter trigger | Focusable button opens modal | Source/tests confirm button; live key not measured | Not measured |
| Sort trigger | Focusable button opens modal | Source/tests confirm button; live key not measured | Not measured |
| Product links | Focusable anchors | Server HTML confirms anchors | Partial |
| Pagination | Focusable anchors/current state | Server HTML confirms labels/current state | Partial |
| Escape closes modal | Modal should close on Escape | Not interactively measured | Not measured |

## Lighthouse Evidence

| Category | Result |
|---|---|
| Lighthouse | Not executed: no Lighthouse package, package install forbidden, Chrome/Edge headless failed |

## Query Evidence

| Scenario | Product count | Query count | N+1 indication | Notes |
|---|---:|---:|---|---|
| Base listing, 1 product | 1 | 18 | Baseline | Includes initial shared settings/cache behavior |
| Base listing, 9-card page from 13 products | 13 | 14 | No linear growth | Prices/images eager loaded by `where in` |
| Page 2 | 13 | 14 | No linear growth | 4 cards |
| Category filter | 13 | 14 | No linear growth | Active filter |
| Unsupported search parameter | 13 | 14 | No linear growth | Search unsupported; no filtering |
| Price sort low | 13 | 14 | No linear growth | IDR sort aggregate |
| Empty high min price | 13 | 9 | No card query growth | Empty state |
