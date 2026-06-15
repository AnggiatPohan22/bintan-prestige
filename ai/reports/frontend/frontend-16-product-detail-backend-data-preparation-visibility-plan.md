# FRONTEND-16 - Public Product Detail Backend Data Preparation & Visibility Plan

Date: 2026-06-14  
Mode: Planning / audit-only  
Scope: Public Product Detail data preparation, visibility policy, and future implementation sequencing

## 1. Planning Status

FRONTEND-16 is complete as a read-only planning step. No runtime code, route, model, Blade, migration, test, package, or configuration file was changed.

The only intended file change is this report:

- `ai/reports/frontend/frontend-16-product-detail-backend-data-preparation-visibility-plan.md`

## 2. Audit Boundary Confirmation

Confirmed audit/read-only task.

In scope:

- Existing documentation and previous frontend reports.
- Public Product Detail route and controller lookup behavior.
- Product model scopes and relations that directly feed Product Detail.
- Product Detail Blade data preparation and Product Detail section keys.
- Product Detail filters/query parameters only where present.
- Pagination only where directly relevant to Product Detail.
- Images, prices, empty states, WhatsApp CTA, metadata, and Product Detail tests.

Out of scope:

- Product Listing implementation changes.
- Homepage implementation changes.
- Admin Product CRUD changes.
- Sitemap changes.
- Product Detail runtime implementation.
- Page Builder or Page Sections admin behavior beyond Product Detail mapping.

## 3. Required Source Inspection

Primary baseline:

- `ai/reports/frontend/frontend-15-public-product-detail-uiux-data-flow-audit.md`

Referenced previous frontend steps:

- `ai/reports/frontend/frontend-14b-public-product-listing-browser-qa-performance-measurement-report.md`
- `ai/reports/frontend/frontend-14-public-product-listing-final-performance-regression-release-verification-report.md`
- `ai/reports/frontend/frontend-13-product-listing-accessibility-seo-ai-discovery-rendering-report.md`
- `ai/reports/frontend/frontend-08-public-product-listing-price-sorting-semantics-implementation-report.md`
- `ai/reports/frontend/frontend-07-public-product-listing-visibility-query-integrity-implementation-report.md`

Referenced product/global/database docs:

- `docs/modules/products.md`
- `docs/architecture/frontend-backend-sync.md`
- `docs/Product/Product_Detail_UI/product-detail-ui-refresh.md`
- `docs/global-booking-cta-settings.md`
- `docs/global-contact-information-settings.md`
- `docs/global-default-media-placeholder-assets.md`
- `docs/global-seo-default-settings.md`
- `docs/global-structured-data-business-schema.md`
- `docs/database/data-integrity.md`
- `docs/database/relationships.md`
- `docs/performance/README.md`
- `docs/performance/audit-report.md`

Target-verified code and tests:

- `routes/frontend.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Models/Product.php`
- `app/Models/ProductPrice.php`
- `app/Models/ProductImage.php`
- `app/Support/BookingCtaSettings.php`
- `app/Support/ContactInformationSettings.php`
- `app/Support/SeoDefaultSettings.php`
- `resources/views/frontend/products/show.blade.php`
- `tests/Feature/Frontend/ProductDetailBookingFormTest.php`
- `tests/Feature/Frontend/ProductPageSectionKeyTest.php`
- `tests/Feature/Database/ProductPriceIntegrityTest.php`

## 4. FRONTEND-15 Baseline

FRONTEND-15 found a functional Product Detail foundation with release-blocking parity gaps:

- Overall Product Detail Readiness: 69/100.
- Visibility Integrity: 58/100.
- Gallery/Media Readiness: 60/100.
- CTA/WhatsApp Flow: 62/100.
- Testing Readiness: 56/100.

This plan does not rerun the full FRONTEND-15 audit. It uses FRONTEND-15 as the baseline and target-verifies the route, controller, Product model, Blade view, helper classes, docs, and tests needed for FRONTEND-17+ implementation planning.

## 5. Public Routes Evidence

Evidence:

- File path: `routes/frontend.php`
- Relevant route: `GET /products/{product:slug}`
- Route name: `products.show`
- Controller: `Frontend\ProductController@show`

Current behavior:

- Product Detail uses route model binding by slug.
- The route name and URL pattern are stable.

Concrete risk:

- Because the current controller receives an already-bound `Product $product`, future visibility logic must be careful not to rely only on post-binding status checks.

Recommended future action:

- Keep route name and URL unchanged.
- In FRONTEND-17A, change only the controller lookup strategy if approved, so the route can still accept `/products/{slug}` while the controller queries through `Product::publiclyVisible()`.

Status: confirmed behavior.

## 6. Current Controller Evidence

Evidence:

- File path: `app/Http/Controllers/Frontend/ProductController.php`
- Class/method: `App\Http\Controllers\Frontend\ProductController::show(Product $product)`

Current behavior:

- The method aborts only when `$product->status !== 'published'`.
- The method eager loads `category`, `destination`, `prices`, `images`, `highlights`, `features`, `faqs`, `itineraries`, and `notes`.
- The method passes the raw `$product` plus SEO variables to `frontend.products.show`.

Concrete risk:

- Products hidden from the public listing by inactive or archived Category/Destination can still resolve by direct slug if the Product itself is `published`.
- Blade remains responsible for too much display state, including gallery, WhatsApp message, add-ons, and fallback media.

Recommended future action:

- FRONTEND-17A should align the Product Detail lookup with public visibility.
- FRONTEND-17B should prepare display-ready view payloads before Blade rendering.

Status: confirmed issue.

## 7. Product Model Visibility Evidence

Evidence:

- File path: `app/Models/Product.php`
- Class/method: `App\Models\Product::scopePubliclyVisible()`

Current behavior:

- `publiclyVisible()` applies `published()`.
- It requires a Category with `deleted_at` null and `is_active = true`.
- It requires a Destination with `deleted_at` null and `is_active = true`.
- `category()` and `destination()` relationships use `withTrashed()`.

Concrete risk:

- Product Detail currently does not use the stricter public visibility scope, while Product Listing does.
- `withTrashed()` is useful for admin/history display but can expose stale parent labels on public detail if the controller does not constrain visibility.

Recommended future action:

- Product Detail should use `Product::publiclyVisible()` unless the owner approves a documented exception.
- Do not add a global scope because admin Product queries must remain unaffected.

Status: confirmed issue.

## 8. Product Detail Visibility Decision

Recommended visibility policy:

| Entity state | Detail behavior | Reason |
| --- | --- | --- |
| Product status `published`, Category active/not deleted, Destination active/not deleted | 200 | Matches public listing visibility |
| Product status `draft` | 404 | Current behavior already blocks drafts |
| Product status archived/unknown if introduced later | 404 | Public pages should not expose non-public products |
| Category inactive | 404 | Listing already hides these products |
| Category soft-deleted | 404 | Listing already hides these products |
| Destination inactive | 404 | Listing already hides these products |
| Destination soft-deleted | 404 | Listing already hides these products |
| Missing slug | 404 | Laravel route behavior should remain |

Decision: Product Detail should match `Product::publiclyVisible()`.

Approval required only if stakeholders want Product Detail to remain accessible when the parent Category/Destination is hidden from listing.

## 9. Route Lookup Strategy Decision

Recommended strategy: explicit controller lookup by slug using `Product::publiclyVisible()`.

Implementation intent for a future step:

- Keep `GET /products/{product:slug}` and route name `products.show`.
- In the controller, receive the raw route parameter/slug and query:
  - `Product::query()`
  - `publiclyVisible()`
  - `where('slug', $slug)`
  - required eager loads
  - `firstOrFail()`

Why this is preferred:

- The public scope participates in the database lookup.
- It avoids resolving a hidden Product first and then patching visibility afterward.
- It keeps admin queries and route names unchanged.

Unavailable/unverified behavior:

- No custom public route binding currently exists for Product Detail.
- No evidence was found that category or destination public detail routes exist; do not invent those routes for breadcrumb links.

## 10. Relation Plan

Recommended Product Detail eager-load plan:

| Relation | Current use | Future decision |
| --- | --- | --- |
| `category` | Badge/context and visibility parent | Eager load after public visibility constraints |
| `destination` | Badge/context, metadata/schema context | Eager load after public visibility constraints |
| `prices` | IDR/SGD display and schema offer context | Eager load and prepare backend price state |
| `images` | Gallery | Eager load with deterministic ordering |
| `highlights` | Summary bullets if used | Keep eager load only if rendered in detail payload |
| `features` | Included/excluded/optional/add-on sections | Eager load and group in backend |
| `itineraries` | Itinerary section | Eager load with existing relation order |
| `notes` | Notes section | Eager load with existing relation order |
| `faqs` | FAQ section | Eager load with existing relation order |

Do not eager load unrelated admin-only relations or booking records in Product Detail.

## 11. Relation Ordering Decision

Evidence:

- File path: `app/Models/Product.php`
- Relevant relations: `features()`, `itineraries()`, `faqs()`, `notes()`, `highlights()`, `images()`, `prices()`

Current behavior:

- Features, itineraries, FAQs, notes, and highlights have model-level `sort_order` ordering.
- Itineraries also order by `start_time`.
- Images do not have model-level ordering; current Blade sorts `$product->images` by `sort_order`.
- Prices do not need display ordering if prepared by currency.

Recommended future action:

- Move image ordering into the controller/support eager-load constraint for Product Detail.
- Keep existing ordered relations for features/itineraries/notes/faqs/highlights.
- Do not reinterpret `start_time` as formatted display time. The migration also has a `time` string, and current Blade displays `time`; use `start_time` only for ordering unless a product content rule is approved.

Status: confirmed issue for image ordering ownership; potential risk for itinerary time semantics.

## 12. Backend-to-Blade Contract Summary

Recommended future view payload:

| Payload key | Purpose |
| --- | --- |
| `product` | Existing model for simple labels and route generation |
| `priceState` | IDR/SGD/missing-price display state and schema-ready values |
| `galleryState` | Primary image, gallery images, thumbnail images, fallback flags |
| `featureGroups` | Included/excluded/optional/add-on/important feature collections |
| `itineraryItems` | Ordered itinerary display rows |
| `noteItems` | Ordered notes |
| `faqItems` | Ordered FAQ rows |
| `whatsappCta` | Validity, number, labels, base messages, fallback behavior |
| `breadcrumbItems` | Visible breadcrumb labels and URLs that actually exist |
| `metadata` | Title, description, canonical, robots, image |
| `sectionState` | Boolean section visibility derived from prepared collections |

Blade should render prepared state and avoid building business logic.

## 13. Price State Decision

Evidence:

- File path: `docs/modules/products.md`
- Relevant section: Product Listing price semantics.
- File path: `docs/database/data-integrity.md`
- Relevant section: Product Price Integrity.
- File path: `tests/Feature/Frontend/ProductDetailBookingFormTest.php`
- Relevant tests: IDR display, SGD display, missing price display.

Current behavior:

- Product Detail can display IDR price.
- Product Detail can display SGD-only price.
- Missing prices render `Price on request`.
- `product_prices(product_id, currency)` is unique after DB-04.

Recommended future action:

- Backend prepares `priceState` with:
  - IDR value and formatted label if present.
  - SGD value and formatted label if present.
  - primary display currency: IDR first, then SGD, then missing.
  - secondary display currency when both exist.
  - `has_price` boolean.
  - `schema_price` and `schema_currency` only when a real price exists.
- Do not treat missing price as zero.
- Do not convert currencies.
- Do not change zero-price policy in FRONTEND-17A without approval.

Status: confirmed behavior plus potential policy risk for zero prices.

## 14. Gallery State Decision

Evidence:

- File path: `resources/views/frontend/products/show.blade.php`
- Relevant section: top `@php` gallery preparation and `products.show.gallery`.
- File path: `docs/global-default-media-placeholder-assets.md`
- Relevant section: default media product fallback.

Current behavior:

- Blade builds `$galleryImages`.
- Thumbnail is pushed first when present.
- Product images are appended after sorting in Blade.
- Default product media is used if no Product image exists.
- Gallery items are deduplicated by URL.
- Main gallery images render inside Alpine `<template x-for>`.

Concrete risk:

- No server-rendered main image if JavaScript fails or crawler does not execute Alpine.
- Image ordering and fallback logic live in Blade.
- Main image lacks explicit width/height/loading policy.

Recommended future action:

- Backend prepares `galleryState`:
  - `primary_image`
  - `images`
  - `thumbnails`
  - `has_gallery`
  - `uses_placeholder`
  - `placeholder_fit`
- Primary image order:
  1. Product thumbnail.
  2. First Product image by `sort_order`.
  3. `default_media.product`.
  4. Empty image state.
- Render the first image server-side in FRONTEND-19, then enhance navigation with Alpine.
- Do not add filesystem checks during request rendering unless approved.

Status: confirmed issue.

## 15. WhatsApp State Decision

Evidence:

- File path: `app/Support/BookingCtaSettings.php`
- Class/methods: `whatsappNumber()`, `whatsappUrl()`, `renderMessage()`
- File path: `resources/views/frontend/products/show.blade.php`
- Relevant section: `$waNumber`, `$waMessageText`, `bookingWhatsappUrl()`
- File path: `docs/global-booking-cta-settings.md`
- Relevant sections: Product detail CTA and number priority.

Current behavior:

- Product number has priority.
- Global Booking CTA can use override/contact fallback.
- Contact information provides WhatsApp fallback.
- `BookingCtaSettings::whatsappUrl()` returns a `https://wa.me/` URL even when the number is empty.
- Product Detail Alpine `bookingWhatsappUrl()` also builds `https://wa.me/${this.waNumber}`.

Concrete risk:

- Primary booking CTA can render a malformed or no-recipient WhatsApp link.
- Conversion and tracking become unreliable when Product/global/contact number is empty.

Recommended future action:

- Backend prepares `whatsappCta`:
  - `enabled`
  - `has_number`
  - `number`
  - `chat_label`
  - `booking_label`
  - `base_message`
  - `booking_base_message`
  - `fallback_label`
  - `fallback_url` if no number is available
- If no number exists, do not render a primary WhatsApp booking link.
- Future implementation may render a safe contact fallback only if an actual route or email/phone behavior is present.

Status: confirmed issue.

## 16. Optional Sections Decision

Evidence:

- File path: `resources/views/frontend/products/show.blade.php`
- Relevant sections: `products.show.features`, `products.show.itinerary`, `products.show.notes`, `products.show.faq`, `products.show.overview`
- File path: `docs/Product/Product_Detail_UI/product-detail-ui-refresh.md`
- Relevant section: Product Detail section key mapping.

Current behavior:

- Features, itinerary, notes, and FAQ sections are conditional.
- Overview currently renders its wrapper even when `description` is empty.
- Product Detail section keys are documented and tested.

Recommended future action:

| Section | Future render rule |
| --- | --- |
| Overview | Render only when description exists, or use an approved fallback |
| Features | Render when any feature group has rows |
| Itinerary | Render when itinerary rows exist |
| Notes | Render when notes exist |
| FAQ | Render when FAQ rows exist |
| Booking | Render when Product is public; CTA disabled/fallback if WhatsApp number unavailable |

Do not rename section keys without approval.

Status: confirmed issue for empty Overview.

## 17. Related Product Decision

Current behavior:

- No related Product section was found in the current Product Detail Blade.
- No Product Detail related-product controller query was found.

Recommended decision:

- Defer Related Products until explicit approval.

If approved later, recommended option:

- Use `Product::publiclyVisible()`.
- Exclude current Product.
- Prefer same Category and same Destination.
- Fallback to same Category only if not enough rows.
- Use deterministic ordering such as latest or admin-approved sort.
- Limit to a small number, for example 3 on desktop.
- Reuse existing Product card only if its data contract is satisfied.

Status: unavailable behavior. Do not implement or assume related products in FRONTEND-17A.

## 18. Breadcrumb and Metadata Decision

Evidence:

- File path: `app/Http/Controllers/Frontend/ProductController.php`
- Method: `show()`
- File path: `docs/global-seo-default-settings.md`
- Relevant section: Product SEO Priority.
- File path: `docs/global-structured-data-business-schema.md`
- Relevant sections: Product SEO and structured data.

Current behavior:

- Controller prepares Product Detail title, description, keywords, canonical URL, SEO image, and `socialShareType`.
- Visible breadcrumb is absent.
- Structured data can render BreadcrumbList/Product schema through shared builder behavior.

Recommended future action:

- Prepare `metadata` in backend:
  - title: `meta_title` then Product name.
  - description: `meta_description` then short description then safe excerpt from description.
  - canonical: `canonical_url` then route URL.
  - image: `og_image_url` then thumbnail then prepared gallery primary image/default media if valid.
  - robots: public visible products use `index, follow`.
- Prepare `breadcrumbItems`:
  - Home.
  - Products.
  - Current Product.
- Do not link Category/Destination in breadcrumb unless a real public route exists.

Status: confirmed issue for visible breadcrumb; confirmed existing metadata foundation.

## 19. Security and Escaping Decision

Evidence:

- File path: `resources/views/frontend/products/show.blade.php`
- Relevant sections: description, itinerary, notes, FAQ, feature rendering.

Current behavior:

- Product description uses `{!! nl2br(e($product->description)) !!}`, which escapes content before line-break rendering.
- Other text inspected in Product Detail uses Blade escaped output.
- WhatsApp messages include Product and form data encoded into a URL.

Concrete risk:

- WhatsApp message construction can include unreviewed dynamic text, but URL encoding reduces link injection risk.
- Moving display prep to backend must preserve escaping expectations and avoid returning raw HTML as trusted content.

Recommended future action:

- Keep Product Detail descriptions as escaped plain text.
- Do not introduce raw HTML rendering without a sanitizer and approval.
- Prepare WhatsApp message text server-side as plain text, then URL encode at link generation.

Status: potential risk.

## 20. Public Filters and Query Parameters

Current behavior:

- Product Detail route has a slug path parameter.
- No Product Detail filter or search query parameter was found.
- Product Listing has filters and normalized query state, but that remains separate.

Recommended future action:

- Do not add Product Detail filters/search parameters.
- Do not read arbitrary query parameters in Product Detail metadata or CTA messages.

Status: unavailable behavior.

## 21. Pagination Decision

Current behavior:

- Product Detail has no pagination.
- Related Products are not implemented.

Recommended future action:

- No Product Detail pagination work in FRONTEND-17A through FRONTEND-21.
- If Related Products are approved, keep them as a bounded collection, not a paginator, unless the user explicitly asks for paged related results.

Status: unavailable behavior.

## 22. Empty State Decision

Recommended future empty-state rules:

| State | Render behavior |
| --- | --- |
| No description | Hide Overview or use approved fallback |
| No feature rows | Hide Features section |
| No itinerary rows | Hide Itinerary section |
| No notes | Hide Notes section |
| No FAQs | Hide FAQ section |
| No prices | Show `Price on request`, no zero price |
| No Product/gallery image | Use `default_media.product` if configured, otherwise render safe no-image frame |
| No WhatsApp number | Do not render no-recipient WhatsApp link |

## 23. Image and Asset Decision

Current behavior:

- Product thumbnail and Product images are application-owned Product data.
- `default_media.product` can supply fallback image.
- Product image path existence was not verified in this planning step.

Recommended future action:

- Keep source priority Product image first, default media second.
- Add width/height/loading/fetchpriority policy in FRONTEND-19.
- Do not change upload validation, storage paths, or media schema in Product Detail implementation.

Status: confirmed behavior plus unverified asset existence.

## 24. SEO and AI Discovery Decision

Current behavior:

- Product Detail metadata exists.
- Product facts are largely server-rendered when present.
- Main gallery active image is client-enhanced.
- Visible breadcrumb is absent.

Recommended future action:

- Fix visibility before schema/SEO expansion.
- Add visible breadcrumb in FRONTEND-18 or FRONTEND-21.
- Keep Product schema truthful: no fake ratings, reviews, availability, or offers beyond actual prepared price data.
- FAQ schema remains deferred until FAQ content and schema policy are explicitly approved.

Status: confirmed issue for breadcrumb and gallery SSR; potential risk for schema completeness.

## 25. Performance Decision

Current behavior:

- Product Detail eager loads rendered relations in one `load()` call.
- Exact Product Detail query count was not measured.
- Gallery can include multiple full-size images.

Recommended future action:

- FRONTEND-17A should use a single scoped Product lookup with eager loads.
- FRONTEND-22 should measure query count and resource weight.
- Do not add caching in FRONTEND-17A unless query diagnostics show a real need.

Status: unverified query count.

## 26. Test Coverage Decision

Evidence:

- File path: `tests/Feature/Frontend/ProductDetailBookingFormTest.php`
- File path: `tests/Feature/Frontend/ProductPageSectionKeyTest.php`
- File path: `tests/Feature/Database/ProductPriceIntegrityTest.php`

Current behavior:

- Existing tests cover Product Detail page render, section keys, booking form markup, add-ons, IDR price display, missing price display, and SGD price display.
- Existing tests do not cover inactive/soft-deleted Category or Destination visibility.
- Existing tests do not cover no-recipient WhatsApp fallback.
- Existing tests do not cover server-rendered primary gallery image.
- Existing tests do not cover Product Detail query count.

Recommended future action:

- FRONTEND-17A should add focused visibility tests.
- FRONTEND-17B/20 should add CTA fallback tests.
- FRONTEND-19 should add gallery fallback/order tests.
- FRONTEND-22 should add query/resource diagnostics.

Status: confirmed test gap.

## 27. Product Detail Implementation Mapping

| Future step | Scope |
| --- | --- |
| FRONTEND-17A | Public visibility alignment and route/controller lookup tests |
| FRONTEND-17B | Backend data preparation payload for price/gallery/CTA/sections |
| FRONTEND-18 | Layout hierarchy, visible breadcrumb, Overview empty behavior |
| FRONTEND-19 | Gallery SSR, image attributes, fallback media, active-state semantics |
| FRONTEND-20 | Booking and WhatsApp CTA validity, message ownership, no-number fallback |
| FRONTEND-21 | Optional section polish, accessibility, SEO/schema alignment |
| FRONTEND-22 | Browser QA, query count, resource/performance measurement |
| FRONTEND-23 | Final regression/release readiness report |

## 28. FRONTEND-17A Proposed Scope

Recommended first implementation step:

- Align Product Detail lookup with `Product::publiclyVisible()`.
- Keep route name and URL unchanged.
- Add tests for:
  - published Product with active parents returns 200.
  - draft Product returns 404.
  - inactive Category returns 404.
  - soft-deleted Category returns 404.
  - inactive Destination returns 404.
  - soft-deleted Destination returns 404.
  - invalid slug returns 404.
- Do not change layout, gallery, WhatsApp message content, schema, or admin code in FRONTEND-17A.

## 29. FRONTEND-17B Proposed Scope

Recommended second backend step:

- Add a controller private method or support class for Product Detail view data.
- Prepare `priceState`, `galleryState`, `whatsappCta`, `featureGroups`, `sectionState`, `breadcrumbItems`, and `metadata`.
- Keep Blade section keys stable.
- Add focused tests for prepared empty states and view contract.

Approval point:

- Decide whether a dedicated support/view-model class is preferred over private controller methods.

## 30. FRONTEND-18 Proposed Scope

Recommended layout step:

- Add visible breadcrumb.
- Hide or fallback Overview when description is empty.
- Adjust section hierarchy only within existing Product Detail UI.
- Keep `products.show.*` section keys stable.

Approval point:

- Confirm breadcrumb labels and whether Category/Destination should be text-only context or omitted from breadcrumb.

## 31. FRONTEND-19 Proposed Scope

Recommended gallery/media step:

- Render primary image server-side.
- Use backend-prepared gallery state.
- Add image attributes and loading policy.
- Keep Alpine only as enhancement.
- Add fallback-media tests.

Approval point:

- Confirm whether placeholder alt should be Product-contextual or preserve stored default-media alt.

## 32. FRONTEND-20 Proposed Scope

Recommended booking/CTA step:

- Prepare WhatsApp CTA state in backend.
- Render primary WhatsApp buttons only when a recipient number exists.
- Keep booking form as no-database WhatsApp message flow.
- Preserve tracking attributes where applicable.

Approval point:

- Confirm no-number fallback behavior.
- Confirm Product Detail message template content.

## 33. FRONTEND-21 Proposed Scope

Recommended accessibility/SEO step:

- Add gallery active state semantics.
- Add price screen-reader context consistent with Product Listing.
- Align visible breadcrumb and BreadcrumbList schema.
- Review Product/FAQ schema truthfulness.

Approval point:

- Confirm whether FAQPage schema should be added for Product Detail FAQs.

## 34. FRONTEND-22 Proposed Scope

Recommended QA/performance step:

- Run browser QA.
- Measure Product Detail query count.
- Inspect gallery image resource behavior.
- Check responsive behavior.
- Run focused tests and broader suite only if runtime code changed.

## 35. FRONTEND-23 Proposed Scope

Recommended release step:

- Final Product Detail regression report.
- Score visibility, data flow, CTA, gallery, accessibility, SEO, performance, tests, and launch readiness.
- Confirm no accidental route/schema/admin changes.

## 36. Confirmed Issues

### F16-C01 - Product Detail visibility does not match Product Listing visibility

- File path: `app/Http/Controllers/Frontend/ProductController.php`
- Class/method: `ProductController::show(Product $product)`
- Current behavior: only Product status is checked before rendering.
- Concrete risk: Products hidden from listing by inactive or archived parents can remain accessible and indexable by direct slug.
- Recommended future action: FRONTEND-17A should use `Product::publiclyVisible()` for Product Detail lookup and add visibility tests.

### F16-C02 - Product Detail still prepares display state inside Blade

- File path: `resources/views/frontend/products/show.blade.php`
- Component/section: top `@php`, `products.show.gallery`, `products.show.booking`
- Current behavior: gallery, fallback media, WhatsApp number/message, add-ons, and labels are prepared in Blade.
- Concrete risk: Blade owns business/display rules, making CTA, gallery, and empty-state behavior harder to test.
- Recommended future action: FRONTEND-17B should move Product Detail display preparation into controller/support layer.

### F16-C03 - WhatsApp booking CTA can render without a recipient number

- File path: `resources/views/frontend/products/show.blade.php`
- Component/method: Alpine `bookingWhatsappUrl()`
- Current behavior: link is built as `https://wa.me/${this.waNumber}`.
- Concrete risk: empty recipient can produce a no-recipient WhatsApp URL and break the primary booking path.
- Recommended future action: FRONTEND-20 should render WhatsApp CTA only when backend-prepared `has_number` is true, with an approved fallback for missing number.

### F16-C04 - Main gallery is not server-first

- File path: `resources/views/frontend/products/show.blade.php`
- Component/section: `products.show.gallery`
- Current behavior: main images render inside Alpine `<template x-for>`.
- Concrete risk: no useful main Product image without JavaScript, weaker crawler/image discovery, and potential LCP/resource issues.
- Recommended future action: FRONTEND-19 should render primary image server-side and enhance gallery controls with Alpine.

## 37. Potential Risks

### F16-P01 - Itinerary time semantics are ambiguous

- File path: `database/migrations/2026_05_24_170152_create_product_itineraries_table.php`
- Relevant fields: `time`, `start_time`, `sort_order`
- Current behavior: Product model orders by `sort_order` and `start_time`; Blade displays `time`.
- Concrete risk: treating `start_time` as display time could format existing data incorrectly.
- Recommended future action: keep `time` as display text and `start_time` as ordering input unless a content rule is approved.

### F16-P02 - Product image asset existence was not verified

- File path: `resources/views/frontend/products/show.blade.php`
- Component/section: gallery image URLs
- Current behavior: Product images are rendered from `asset('storage/' . $image->image)`.
- Concrete risk: missing storage files could render broken images.
- Recommended future action: verify asset availability during FRONTEND-19 browser/resource QA, not during this planning step.

### F16-P03 - Query count is still unmeasured

- File path: `app/Http/Controllers/Frontend/ProductController.php`
- Method: `show()`
- Current behavior: relations are eager loaded, but no Product Detail query-count evidence exists.
- Concrete risk: later additions such as related products or schema may add hidden query cost.
- Recommended future action: measure in FRONTEND-22 after backend/data-flow changes.

## 38. Unavailable or Unverified Behavior

- No Product Detail free-text search or filter parameter was found.
- No Product Detail pagination was found.
- No related Products section was found.
- No public Category or Destination detail route was verified.
- No Product Detail browser/Lighthouse screenshot evidence was collected in this planning step.
- No Product Detail query count was collected in this planning step.

## 39. Approval Points

Before implementation, approve:

- Product Detail should exactly match `Product::publiclyVisible()` and return 404 for inactive/soft-deleted parents.
- Explicit controller slug lookup is acceptable while keeping the route URL/name unchanged.
- Whether backend preparation should live in a support/view-model class or private controller methods.
- WhatsApp no-number fallback behavior.
- Product Detail WhatsApp message content.
- Breadcrumb labels and whether Category/Destination appear as text-only context.
- Whether Related Products are deferred or included in a later step.
- Whether FAQ schema is wanted for Product Detail FAQs.
- Whether zero price should remain valid display data or become invalid business data.

## 40. Backend Impact

No backend code changed in FRONTEND-16.

Future backend impact:

- `ProductController::show()` will change in FRONTEND-17A/17B if approved.
- Optional support class may be introduced for Product Detail view data.
- No schema change is recommended.
- No admin query behavior should change.

## 41. Frontend Impact

No frontend runtime file changed in FRONTEND-16.

Future frontend impact:

- Product Detail Blade should render backend-prepared payloads.
- Section keys should remain stable.
- Gallery should become server-first with Alpine enhancement.
- Visible breadcrumb and empty Overview behavior should be handled in layout steps.

## 42. Route Impact

No route file changed in FRONTEND-16.

Future route impact:

- Keep `products.show`.
- Keep `/products/{product:slug}` URL shape unless a future task explicitly approves a route change.
- Controller method signature/lookup may change internally.

## 43. Database Impact

No database file changed in FRONTEND-16.

Future database impact:

- No migration is required for the recommended visibility/data-preparation work.
- Existing Product price unique index supports deterministic IDR/SGD preparation.
- Product parent FK/delete integrity remains unchanged.

## 44. Security Impact

No security-sensitive file changed in FRONTEND-16.

Future security considerations:

- Keep text escaped in Blade.
- Do not introduce raw Product description HTML without sanitization.
- Do not expose hidden Products via direct slug.
- Avoid no-recipient WhatsApp links.

## 45. SEO Impact

No SEO runtime file changed in FRONTEND-16.

Future SEO considerations:

- Visibility alignment should happen before schema/metadata expansion.
- Public Product Detail pages should be indexable only when publicly visible.
- Breadcrumb UI should align with BreadcrumbList schema.
- Product schema should use only real Product and price data.

## 46. Performance Impact

No performance/runtime file changed in FRONTEND-16.

Future performance considerations:

- Keep Product Detail query bounded.
- Add query-count evidence after implementation.
- Render primary image server-side and control gallery resource loading.

## 47. Documentation Impact

This report is the documentation artifact for FRONTEND-16.

No existing documentation was edited.

## 48. Scores

Scores are awarded after completing the inspection above.

| Area | Score | Justification |
| --- | ---: | --- |
| Planning Completeness | 92/100 | Required sources, route/controller/model/Blade/docs/tests were inspected and mapped to implementation steps |
| Visibility Policy Readiness | 84/100 | Product Listing scope provides a clear reusable policy; implementation needs approval and tests |
| Backend Data Contract Readiness | 78/100 | Required payloads are clear, but a support class/controller ownership choice remains |
| Price Preparation Readiness | 88/100 | IDR/SGD/missing-price rules and database uniqueness are documented and tested |
| Gallery Preparation Readiness | 70/100 | Source priority is clear, but SSR image behavior and asset QA remain future work |
| WhatsApp CTA Readiness | 68/100 | Number priority is documented, but no-number fallback requires approval and implementation |
| SEO/AI Discovery Readiness | 76/100 | Metadata foundation exists; breadcrumb and public visibility must be aligned |
| Test Plan Readiness | 74/100 | Existing tests cover basics; visibility, CTA, gallery, and query-count tests are missing |
| Overall FRONTEND-16 Readiness | 82/100 | The implementation path is clear and scoped, with explicit approval gates for unresolved policies |

## 49. Recommended Next Implementation Step

Start FRONTEND-17A:

- Align Product Detail public lookup with `Product::publiclyVisible()`.
- Keep route URL/name unchanged.
- Add focused visibility tests.
- Do not modify Product Detail layout, gallery, WhatsApp message content, schema, admin code, or database schema in that step.

## 50. Files Changed

Changed:

- `ai/reports/frontend/frontend-16-product-detail-backend-data-preparation-visibility-plan.md`

No other project file should be changed.

## 51. Verification

Required commands for this read-only planning step:

- `git diff --check`
- `git status --short`

`php artisan test` was not run because no runtime code changed and this step intentionally changed only the planning report.

## 52. Rollback Note

To roll back FRONTEND-16, delete only:

- `ai/reports/frontend/frontend-16-product-detail-backend-data-preparation-visibility-plan.md`

No database rollback, route rollback, cache clear, package uninstall, or runtime code rollback is required.
