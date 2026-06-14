# FRONTEND-15 Public Product Detail UI/UX & Data Flow Audit

Date: 2026-06-14  
Scope: Public Product Detail only (`GET /products/{product:slug}`, route name `products.show`)  
Mode: audit/read-only only  
Status: Completed with confirmed issues and implementation approval points

## 1. Executive Summary

Public Product Detail is present, server-rendered through Blade, and already supports product title, category/destination badges, short and long descriptions, gallery, prices, highlights, features, itinerary, notes, FAQs, booking form, WhatsApp CTA, SEO metadata, and Product/Breadcrumb JSON-LD.

The page is not yet ready to treat as equal to the completed Product Listing release baseline. The main confirmed gap is visibility integrity: Product Detail blocks draft products, but it does not apply the Product Listing `Product::publiclyVisible()` parent policy. A published product under an inactive or archived Category/Destination can still be reached by direct slug URL. The second major issue is gallery resilience: the main product image is rendered through an Alpine `<template>`, so the primary gallery image is JavaScript-dependent and lacks explicit image dimensions.

No runtime code, database schema, migrations, routes, controllers, models, Blade, CSS, JavaScript, config, or tests were changed. The only intentional change for this step is this audit report.

## 2. Audit Scope and Method

Inspection followed the requested order and stayed inside Product Detail scope. Product Listing, Homepage, Admin Product CRUD, sitemap, and unrelated frontend sections were inspected only where they provide shared product data, shared components, or prior baseline evidence.

Method:

- Read `AGENTS.md`.
- Resolved the pasted task brief as `FRONTEND-15`.
- Read required frontend reports by step number and content: FRONTEND-10, 10B, 12, 13, 14, and 14B.
- Read product docs, frontend/backend sync docs, Product Detail UI docs, Page Section mapping docs, SEO/global booking/default media docs, database integrity docs, and relevant skills/guidelines.
- Inspected actual route registration, controller flow, model scopes/relations, Blade view, shared price component, structured data builder, CSS contracts, factories, migrations, and Product Detail tests.
- Did not run browser QA or full test suite because this step is read-only and no runtime code changed.

## 3. Previous Frontend Baseline Used

Baseline used:

- FRONTEND-14: Product Listing final release verification, release-ready with known limitations.
- FRONTEND-14B: Product Listing HTTP/query-count addendum; browser screenshot/Lighthouse still unavailable.
- FRONTEND-10/10B: product card and 9-item listing pagination baseline.
- FRONTEND-12: listing responsive/image/empty-state contract.
- FRONTEND-13: listing accessibility, SEO, AI-discovery, metadata, and ItemList baseline.
- `docs/modules/products.md`: Product Listing uses `Product::publiclyVisible()`; Product Detail parent visibility was explicitly deferred.
- `docs/architecture/frontend-backend-sync.md`: Product Detail behavior remains separate from the Product Listing policy.

## 4. Product Detail Route Map

| Route/context | Controller | View | Visibility protection | Result |
| ------------- | ---------- | ---- | --------------------- | ------ |
| `GET /products/{product:slug}` named `products.show` | `App\Http\Controllers\Frontend\ProductController@show` | `frontend.products.show` | Only checks `$product->status !== 'published'`; no active/non-archived parent check | Confirmed route exists; visibility gap confirmed |
| Invalid slug | Laravel implicit route model binding on `{product:slug}` | N/A | Missing Product model binding fails before controller | 404 expected by framework binding; no dedicated test found |
| Draft Product direct URL | `ProductController@show` | N/A | `abort_if($product->status !== 'published', 404)` | Confirmed blocked by code; no focused Product Detail test found |
| Published Product with inactive/archived parent | `ProductController@show` | `frontend.products.show` | No `publiclyVisible()` or parent `is_active`/`deleted_at` check | Confirmed issue: accessible by direct URL from code path |
| Admin product routes | `routes/admin.php` under `auth` + `admin` middleware | backend views | Separate admin middleware group | Separation confirmed; admin detail not audited |

Evidence:

- `routes/frontend.php`: `GET /products/{product:slug}` maps to `FrontendProductController::class, 'show'` and is named `products.show`.
- `bootstrap/app.php`: `routes/frontend.php` is loaded inside the `web` middleware group.
- `app/Http/Controllers/Frontend/ProductController.php`, `show(Product $product)`: receives implicit model binding and aborts only when status is not `published`.

## 5. Controller and Data Flow

`ProductController@show` currently:

- receives `Product $product` through slug route binding;
- blocks non-published products;
- eager loads `category`, `destination`, `prices`, `images`, `highlights`, `features`, `faqs`, `itineraries`, and `notes`;
- passes the raw loaded Product model to Blade;
- prepares basic SEO variables: title, description, keywords, canonical, image, and social share type.

Current behavior:

- Product entity data is loaded before Blade.
- Much display preparation still happens in `resources/views/frontend/products/show.blade.php`, including WhatsApp number/message selection, add-on options, gallery array building, gallery de-duplication, and feature grouping.

Concrete risk:

- Product Detail data flow does not match the stronger Product Listing pattern where controller/support classes prepare most display state.
- Edge behavior for empty WhatsApp number, gallery count, add-ons, and grouped features is harder to test because it is assembled in Blade and Alpine.

Recommended future action:

- In FRONTEND-16, prepare a Product Detail view payload in the controller or a support class while preserving existing visual output and field names.

## 6. Published/Draft Visibility

Confirmed behavior:

- Draft and invalid non-`published` status Products are blocked by `abort_if($product->status !== 'published', 404)`.
- Product status values are documented as `draft` and `published`.

Risk:

- Focused Product Detail tests do not currently assert draft direct URL behavior.

Recommended future action:

- Add a Product Detail visibility test for draft Product 404 before changing runtime logic.

## 7. Category/Destination Visibility

Confirmed issue:

- Product Listing uses `Product::publiclyVisible()` and requires a published Product plus active, non-archived Category and Destination.
- Product Detail does not use `Product::publiclyVisible()` and does not check `category.is_active`, `category.deleted_at`, `destination.is_active`, or `destination.deleted_at`.
- `Product::category()` and `Product::destination()` use `withTrashed()`, so archived parents can still be loaded and shown.

Concrete risk:

- A Product removed from the public listing by inactive/archived parent policy can remain public at `/products/{slug}`.
- This creates stale public inventory, SEO inconsistency, and possible private/draft-adjacent content exposure through direct URLs.

Recommended future action:

- Apply a Product Detail public visibility policy that matches listing visibility, or document an approved exception before implementation.

Classification: confirmed issue, High.

## 8. Route Binding and 404 Behavior

Confirmed behavior:

- Product Detail uses slug binding through `{product:slug}`.
- Missing slugs should 404 via Laravel implicit route model binding.
- Draft Product is explicitly aborted after binding.

Unavailable/unverified behavior:

- No focused Product Detail test was found for invalid slug.
- No focused Product Detail test was found for inactive Category, archived Category, inactive Destination, or archived Destination direct URL behavior.

Recommended future action:

- Add route/visibility tests before implementation changes.

## 9. Relations Loaded

| Relation | Used by | Eager loaded | Ordered | Risk |
| -------- | ------- | -----------: | ------: | ---- |
| `category` | badges, schema category | Yes | N/A | Parent active/archive not checked |
| `destination` | badges, schema additional property | Yes | N/A | Parent active/archive not checked |
| `prices` | `product-price` component, schema Offer | Yes | No explicit order | Safe for IDR/SGD lookup from loaded collection |
| `images` | gallery | Yes | Sorted in Blade by `sort_order` | Display ordering prepared in Blade |
| `highlights` | summary highlights | Yes | Model relation orders by `sort_order` | Present but not listed in task relation set; still used |
| `features` | section groups and add-ons | Yes | Model relation orders by `sort_order` | Grouping/filtering happens in Blade |
| `itineraries` | timeline | Yes | Model orders by `sort_order`, `start_time` | Time formatting is raw field display |
| `notes` | notes panel | Yes | Model relation orders by `sort_order` | Optional wrapper safe |
| `faqs` | FAQ details list | Yes | Model relation orders by `sort_order` | No FAQ schema yet; acceptable for audit scope |
| related Products | Not present | No | N/A | Section unavailable |

## 10. Query and Eager-Loading Findings

Confirmed behavior:

- Product Detail eager loads the relations it renders.
- No direct `Product::`, `PageSection::`, `DB::`, or `::query()` pattern was found inside `show.blade.php`.
- The shared price component reads `$product->idr_price` and `$product->sgd_price`; those accessors use the loaded `prices` collection when relation is already eager loaded.

Potential risk:

- Query count for Product Detail was not measured in this step.
- If future Product Detail logic adds related products or Page Section content, a query-budget test should be added.

Recommended future action:

- Add a request-level Product Detail query-count diagnostic in the verification step after backend data preparation.

## 11. Backend-to-Blade Contract

Confirmed issue:

- `show.blade.php` contains a large top-level `@php` block that prepares booking settings, WhatsApp numbers/messages, CTA labels, add-on options, gallery image arrays, placeholder fallback, and unique image filtering.
- Feature groups are filtered in Blade with `$product->features->where('label', $label)`.

Concrete risk:

- Presentation remains coupled to business/display preparation.
- Future changes to WhatsApp templates, gallery behavior, or add-on pricing can duplicate logic across Blade, Alpine, tests, and docs.

Recommended future action:

- Move gallery payload, CTA payload, grouped features, add-on options, and SEO/schema-ready facts to a support class or controller payload.

Classification: confirmed issue, Medium.

## 12. Product Detail Information Hierarchy

Actual order:

1. Hero wrapper.
2. Gallery.
3. Category/Destination badges.
4. H1 Product title.
5. Short description.
6. Duration/Pickup meta.
7. Price card and WhatsApp chat CTA.
8. Optional CTA note.
9. Highlights.
10. Overview.
11. Features.
12. Itinerary.
13. Notes.
14. FAQ.
15. Sticky booking sidebar with price, date, guest counters, duration, meeting point, add-ons, pickup note, and WhatsApp booking CTA.

Findings:

- One H1 is present in the Product Detail view.
- Price and CTA are visible high in the page and repeated in the booking sidebar.
- Meeting point appears only in the sidebar, not in the top summary.
- There is no visible breadcrumb before the H1.
- Overview panel always renders even if description is empty.

Recommended future action:

- Keep section order stable until approval, but consider adding visible breadcrumb and conditional overview rendering in future Product Detail layout work.

## 13. Hero and Gallery Findings

Confirmed issue:

- The main gallery image is rendered inside an Alpine `<template x-for>`.
- If JavaScript/Alpine does not run, the primary gallery image is not visible as normal server-rendered HTML.
- Main gallery images lack explicit `width`, `height`, and `loading` attributes.
- All gallery images are represented as full main-frame `<img>` elements with `x-show`, so multiple full images may be requested.

Current behavior:

- Thumbnail URL is pushed first when present.
- Product gallery images are appended from `$product->images`, sorted by `sort_order` in Blade.
- If no product image exists and `default_media.product` exists, one placeholder image is pushed.
- Duplicate URLs are removed with `unique('url')`.
- Prev/next buttons render when more than one image exists.
- Thumbnail row renders at most four images.
- No modal/lightbox is present.

Concrete risk:

- Gallery can appear broken without JavaScript.
- LCP and bandwidth can degrade on products with many gallery images.
- Image SEO/accessibility is weaker because the main visual is not plainly server-rendered.

Recommended future action:

- Render the first gallery image as normal HTML, keep Alpine for enhancement, add dimensions/loading policy, and add a no-JS fallback before considering any lightbox.

Classification: confirmed issue, High.

## 14. Thumbnail and Fallback Findings

Confirmed behavior:

- Product thumbnail is preferred through `$product->thumbnail_url`.
- Product gallery relation images are appended after thumbnail.
- Default product media fallback is used only when thumbnail/gallery are empty.
- If no placeholder exists, the UI shows `No Image`.

Potential risk:

- Stored file existence is not checked by Product Detail; a broken storage path can produce a broken image.
- Gallery and thumbnail alt text use Product name only, not per-image alt text or destination context.

Recommended future action:

- Keep upload pipeline unchanged for now, but add Product Detail tests for no thumbnail, empty gallery, default product fallback, and broken-media policy after approval.

## 15. Price Rendering Findings

Confirmed behavior:

- Product Detail reuses `frontend.components.product-price`.
- IDR displays first when present.
- SGD displays as fallback when IDR is missing.
- SGD displays as secondary when both IDR and SGD exist.
- Missing price renders `Price on request` and Product Detail tests assert no `Rp 0`.

Potential risk:

- Detail/booking contexts do not include the screen-reader price prefix that listing added in FRONTEND-13.
- `amount zero` is treated as a real zero price because the component checks `!== null`; admin requests allow `min:0`.

Recommended future action:

- Decide whether zero is a valid business price. If not, tighten write validation in a separate approved backend step.

## 16. Duration and Meeting-Point Findings

Confirmed behavior:

- Duration displays in the top summary and booking card.
- Pickup displays in the top summary.
- Meeting point displays in the booking card and WhatsApp booking message.
- Missing duration/meeting point fallback to `-`.

Risk:

- `-` avoids exceptions but is weak content for users and AI discovery.
- Long meeting points are right-aligned in the booking card and may need browser QA for wrapping.

Recommended future action:

- Prepare human-readable empty labels in backend and add responsive/manual QA coverage.

## 17. Product Description Findings

Confirmed behavior:

- Short description renders escaped.
- Full description renders through `{!! nl2br(e($product->description)) !!}`, so text is escaped before line breaks are inserted.
- No unsafe raw Product description HTML was found.

Confirmed issue:

- The Overview panel renders unconditionally, even when Product description is empty/null.

Risk:

- Empty panel reduces polish and creates thin content blocks.

Recommended future action:

- Conditionally render Overview only when description exists, unless business approves a fallback text.

Classification: confirmed issue, Medium.

## 18. Main CTA Findings

Confirmed behavior:

- Primary summary CTA is an anchor to `https://wa.me/{number}?text={message}`.
- Booking sidebar CTA is an anchor with Alpine-bound `bookingWhatsappUrl()`.
- Both CTAs use `target="_blank"` and `rel="noopener noreferrer"`.
- Both include `data-whatsapp-tracking="product"` and product tracking attributes.
- No online payment or checkout wording was found.

Confirmed issue:

- CTAs render even when WhatsApp number is empty, producing a `wa.me` URL without recipient.

Risk:

- Booking action can fail at the most important conversion point.

Recommended future action:

- In future implementation, prepare CTA validity in backend and render a safe fallback/contact state when no number is available.

Classification: confirmed issue, High.

## 19. WhatsApp Flow Findings

| CTA | Source | URL construction | Missing-data behavior | Risk |
| --- | ------ | ---------------- | --------------------- | ---- |
| Summary chat CTA | Product number, or global booking CTA if enabled | Blade builds `https://wa.me/{{ $waNumber }}?text={{ $waMessage }}` | Empty number still renders link | Broken conversion risk |
| Booking form CTA | Alpine state from Blade-provided number/message/product facts | `bookingWhatsappUrl()` returns `https://wa.me/${this.waNumber}?text=...` | Empty number still renders link | Broken conversion risk |
| Header/footer shared CTAs | Global settings via layout partials | Shared support helpers | Outside Product Detail scope | Not audited deeply |

Confirmed behavior:

- Product-specific number is preferred when global Product CTA is enabled.
- Global booking message templates support `{site_name}`, `{product_name}`, `{product_url}`, and `{page_url}`.
- Booking form message includes Product, Date, Adults, Children, Duration, Meeting Point, Add-ons, and Product URL.

Risk:

- WhatsApp URL/message preparation is split across Blade, `BookingCtaSettings`, and Alpine.
- Selected currency/price context is not included in the booking form message.

Recommended future action:

- Centralize WhatsApp URL payload and message defaults in backend/support code; keep visitor-entered date/guest values client-side.

## 20. Features Findings

Confirmed behavior:

- Product Detail loads `features`.
- Section renders only when `$product->features->count()`.
- Feature groups include included, excluded, optional, addon, and important.
- Items render as an unordered list and are escaped.
- Add-on booking options reuse features with `label = addon`.

Risk:

- Grouping is done in Blade.
- Duplicate feature values are not filtered in display groups.

Recommended future action:

- Prepare grouped features in backend and add focused order/empty/duplicate tests.

## 21. Itinerary Findings

Confirmed behavior:

- Product Detail loads `itineraries`.
- Model relation orders by `sort_order` then `start_time`.
- Section renders only when itineraries exist.
- Time falls back to `-`; title and description are escaped.

Risk:

- No backend conversion from `start_time` to display time was found; view displays the raw `time` field.
- Timeline uses `div` structure rather than a semantic list.

Recommended future action:

- Keep current data model, but prepare itinerary display rows in backend and consider semantic list markup in a future UI step.

## 22. Notes Findings

Confirmed behavior:

- Product Detail loads `notes`.
- Model relation orders by `sort_order`.
- Section renders only when notes exist.
- Note title is optional; description is escaped.

Risk:

- No duplicate/long-note handling is tested.

Recommended future action:

- Add focused tests for note ordering and empty wrapper behavior.

## 23. FAQ Findings

Confirmed behavior:

- Product Detail loads `faqs`.
- Model relation orders by `sort_order`.
- Section renders only when FAQs exist.
- FAQ uses native `<details>`/`<summary>`, which is keyboard-capable without custom JS.
- FAQ text is escaped.

Potential risk:

- No visible FAQ schema is emitted by Product Detail yet.
- Summary icon/state styling is minimal and browser keyboard behavior was not manually verified.

Recommended future action:

- Keep FAQ schema deferred until FRONTEND-21; add keyboard QA and FAQ ordering tests first.

## 24. Related Product Findings

Confirmed unavailable behavior:

- No related products section, query, eager loading, card reuse, or empty state was found in `ProductController@show` or `show.blade.php`.

Risk:

- Internal linking from Product Detail is thinner than Product Listing, but this is unavailable by current design rather than a broken section.

Recommended future action:

- Do not implement automatically. If approved, define criteria first: same Category, same Destination, public-visible only, exclude current Product, deterministic order, limit, eager loading, and Product Card reuse.

## 25. Breadcrumb Findings

Confirmed issue:

- Product Detail has BreadcrumbList JSON-LD support through `StructuredDataBuilder`.
- No visible breadcrumb `<nav>` was found in `resources/views/frontend/products/show.blade.php`.

Concrete risk:

- Users lack an obvious path back to Home/Product Listing.
- Visible content does not match the structured-data breadcrumb, reducing accessibility and SEO clarity.

Recommended future action:

- Add a visible, semantic breadcrumb in a future layout step.

Classification: confirmed issue, Medium.

## 26. Responsive Findings

| Viewport | Gallery | Main content | CTA | Optional sections | Result |
| -------- | ------- | ------------ | --- | ----------------- | ------ |
| 320px | CSS static review only; main image aspect frame exists | Single-column expected | Booking sidebar stacks after content | Panels stack | Unverified in browser |
| 375px | CSS static review only | Single-column expected | Full-width buttons | Panels stack | Unverified in browser |
| 768px | CSS static review only | Single-column/two-column components based on Tailwind classes | Full-width buttons | Feature groups can become two columns at `md` | Unverified in browser |
| 1024px | Gallery/summary hero grid becomes two columns | Main content + 360px sticky sidebar | Sidebar sticky | Panels in main column | Unverified in browser |
| 1280px | Same desktop contract | Max-width 7xl | Sidebar sticky | Stable expected | Unverified in browser |
| 1440px | Same desktop contract | Max-width 7xl | Sidebar sticky | Stable expected | Unverified in browser |

Browser QA was not performed in this audit. Current responsive findings are from CSS and markup inspection only.

## 27. Image and Media Findings

Confirmed behavior:

- Gallery frame uses stable CSS aspect ratio.
- Thumbnails use lazy loading and async decoding.
- Main gallery images use async decoding but not explicit width/height/loading.
- No video, lightbox, modal, swipe handling, or external gallery library is present.

Risk:

- Multiple gallery images may load at once.
- Broken stored paths are not recovered at render time.
- Main gallery image lacks no-JS resilience.

Recommended future action:

- Fix first-image SSR and image attributes before considering richer gallery interaction.

## 28. Empty and Edge-State Findings

| Section | Data source | Empty behavior | Rendering risk |
| ------- | ----------- | -------------- | -------------- |
| Gallery | thumbnail, images, default media | Fallback image or `No Image` | Main image JS-dependent |
| Price | prices relation | `Price on request` | Zero amount policy undecided |
| Category | category relation | Badge omitted | Parent visibility gap |
| Destination | destination relation | Badge omitted | Parent visibility gap |
| Duration | product field | `-` | Weak content |
| Meeting point | product field | `-` | Weak content |
| Description | product field | Empty Overview panel | Confirmed issue |
| Highlights | highlights relation | Section omitted | Low risk |
| Features | features relation | Section omitted | Low risk |
| Itinerary | itineraries relation | Section omitted | Low risk |
| Notes | notes relation | Section omitted | Low risk |
| FAQ | faqs relation | Section omitted | Low risk |
| WhatsApp number | product/global/contact settings | CTA still renders | High conversion risk |
| Related products | none | Not rendered | Unavailable by design |

## 29. Accessibility Findings

Confirmed strengths:

- One H1 is present.
- CTAs are anchors.
- Gallery controls and thumbnail buttons have `aria-label`.
- FAQ uses native details/summary.
- Form date input, stepper buttons, and add-on checkboxes are keyboard-native.

Confirmed/potential issues:

- No visible breadcrumb nav.
- Main gallery active thumbnail has visual class only; no `aria-current`/`aria-pressed` state was found.
- Gallery count is visual `x-text` and not announced with `aria-live`.
- Price detail/booking contexts lack listing-style screen-reader price context.
- Browser keyboard, zoom, and screen-reader QA were not performed.

Recommended future action:

- Add semantic breadcrumb, gallery active-state semantics, and keyboard QA in FRONTEND-21.

## 30. SEO Rendering Findings

Confirmed behavior:

- Controller prepares `seoTitle`, `seoDescription`, `seoKeywords`, `canonicalUrl`, `seoImage`, and `socialShareType = product`.
- Layout partial renders title, description, keywords, robots, canonical, Open Graph, and Twitter tags.
- `StructuredDataBuilder` can emit Product schema and BreadcrumbList.
- Product schema uses product name, description, image, URL, category, brand, Offer price/currency when present, duration, destination, and meeting point.

Issues/risks:

- Product Detail robots defaults to global `index, follow`; inactive-parent Product Detail can therefore be indexable.
- Visible breadcrumb is absent even though BreadcrumbList JSON-LD exists.
- FAQ schema is not emitted; deferred by scope.
- Product schema uses first available IDR/SGD price but does not include booking-specific availability beyond `InStock`.

Recommended future action:

- Fix visibility first, then refine visible breadcrumb and schema readiness in FRONTEND-21.

## 31. AI Discovery Findings

Confirmed server-rendered content:

- Product name, category, destination, short description, full description, duration, pickup, price state, features, itinerary, notes, FAQs, and CTA labels are present in Blade output when data exists.

Risk:

- Main gallery image depends on Alpine template rendering.
- Missing visible breadcrumb and empty Overview panel reduce content clarity.
- Meeting point is present in sidebar, but not in top summary.

Recommended future action:

- Keep critical Product facts in server-rendered HTML and avoid moving content behind JavaScript-only widgets.

## 32. Performance Findings

Confirmed behavior:

- Product Detail eager loads all currently rendered product relations in one `load()` call.
- Global settings are shared through the existing composer/global settings service.
- No related Product query is present.

Potential risks:

- Exact Product Detail query count was not measured.
- Main gallery can render/request multiple full-size images.
- Main gallery image lacks explicit dimensions.
- Alpine owns gallery and booking interactivity.

Recommended future action:

- Add Product Detail query-count measurement and gallery resource review in FRONTEND-22.

## 33. CMS Scalability Findings

Product-owned content:

- name, descriptions, prices, thumbnail/gallery, highlights, features, itinerary, notes, FAQs, meeting point, duration, pickup, and product-specific WhatsApp number.

Application-owned behavior:

- route, visibility policy, relation eager loading, section order, gallery behavior, metadata policy, CTA logic, and responsive layout.

Global/CMS-owned content:

- global booking CTA labels/templates, global/contact WhatsApp fallback, default product media, tracking settings, SEO defaults, structured-data toggles.

Finding:

- `PageSectionRegistry` registers `products.show.*` section metadata, but Product Detail does not load Page Section content. This is acceptable for current entity-owned Product content, but should be documented as mapping/registry rather than page-builder behavior.

## 34. Testing Gaps

Future tests needed:

1. Published Product Detail returns 200.
2. Draft Product Detail returns 404.
3. Product under inactive Category returns 404 or approved policy behavior.
4. Product under archived Category returns 404 or approved policy behavior.
5. Product under inactive Destination returns 404 or approved policy behavior.
6. Product under archived Destination returns 404 or approved policy behavior.
7. Invalid slug returns 404.
8. IDR + SGD detail/booking display.
9. IDR-only detail/booking display.
10. SGD-only detail/booking display.
11. Missing price renders `Price on request`, never `Rp 0`.
12. Missing thumbnail uses default media fallback.
13. Empty gallery safe.
14. Gallery order and duplicate URL behavior.
15. Feature group order.
16. Itinerary order by `sort_order` and `start_time`.
17. Notes order.
18. FAQ order.
19. Empty optional sections do not render wrappers.
20. Empty description does not render empty Overview after implementation.
21. WhatsApp URL with product number.
22. WhatsApp URL with global fallback.
23. Missing WhatsApp number safe behavior.
24. Product detail metadata/canonical.
25. Exactly one H1.
26. Visible breadcrumb.
27. Product schema readiness.
28. FAQ accordion keyboard QA.
29. No database query in Blade.
30. No N+1/query budget.
31. Responsive manual QA.
32. Keyboard QA.
33. Full regression after implementation.

## 35. Critical Issues

No critical issue was confirmed in this read-only audit.

Draft Products are blocked by the current Product Detail controller. No route-main breakage or unsafe raw Product description HTML was found.

## 36. High-Priority Issues

### F15-H01: Product Detail does not enforce active/non-archived parent visibility

- Evidence path: `app/Http/Controllers/Frontend/ProductController.php`
- Class/method: `ProductController@show`
- Current behavior: only non-`published` status is blocked; Category/Destination active/archive state is not checked.
- Concrete risk: products hidden from public listing by parent visibility can remain indexable and accessible by direct slug.
- Recommended future action: align Product Detail with `Product::publiclyVisible()` or approve a documented exception, then add visibility tests.
- Classification: confirmed issue.

### F15-H02: Main gallery image is JavaScript-dependent and lacks core image attributes

- Evidence path: `resources/views/frontend/products/show.blade.php`
- Component/section: `products.show.gallery`
- Current behavior: main gallery images are rendered inside Alpine `<template x-for>` and lack `width`, `height`, and `loading`.
- Concrete risk: broken/no main image without JavaScript, weaker image SEO, possible LCP/bandwidth cost from multiple full gallery images.
- Recommended future action: render the first image server-side, enhance with Alpine, add dimensions/loading policy.
- Classification: confirmed issue.

### F15-H03: WhatsApp CTA can render without a recipient number

- Evidence path: `resources/views/frontend/products/show.blade.php`; `app/Support/BookingCtaSettings.php`
- Component/method: summary WhatsApp CTA and `bookingWhatsappUrl()`
- Current behavior: CTAs render even when `$waNumber` is empty.
- Concrete risk: primary booking action can open a malformed/no-recipient WhatsApp URL.
- Recommended future action: prepare CTA validity and render a safe fallback when no Product/global number exists.
- Classification: confirmed issue.

## 37. Medium-Priority Issues

### F15-M01: Display preparation is too concentrated in Blade

- Evidence path: `resources/views/frontend/products/show.blade.php`
- Relevant section: top-level `@php` block and feature grouping loop.
- Current behavior: gallery, CTA, add-ons, messages, and grouped feature data are prepared in Blade.
- Concrete risk: lower testability and duplicated business/display logic.
- Recommended future action: move preparation into controller/support layer.
- Classification: confirmed issue.

### F15-M02: Visible breadcrumb UI is absent

- Evidence path: `resources/views/frontend/products/show.blade.php`; `app/Support/StructuredDataBuilder.php`
- Relevant section: Product Detail view and breadcrumb schema builder.
- Current behavior: JSON-LD BreadcrumbList can render, but no visible breadcrumb nav is present.
- Concrete risk: weaker navigation, accessibility, and visible/schema alignment.
- Recommended future action: add semantic breadcrumb UI in a layout step.
- Classification: confirmed issue.

### F15-M03: Overview panel renders when description is empty

- Evidence path: `resources/views/frontend/products/show.blade.php`
- Component/section: `products.show.overview`
- Current behavior: Overview section is unconditional.
- Concrete risk: empty content panel and thin page presentation.
- Recommended future action: conditionally render Overview or provide an approved fallback.
- Classification: confirmed issue.

### F15-M04: Product Detail browser QA and query-count evidence are unavailable

- Evidence path: this audit method; FRONTEND-14B notes browser tooling limitations for listing.
- Relevant section: responsive/performance verification.
- Current behavior: CSS/static review only for Product Detail responsive behavior; no Lighthouse or query count.
- Concrete risk: layout, keyboard, zoom, and performance issues may remain unseen.
- Recommended future action: run browser and query diagnostics in FRONTEND-22.
- Classification: unavailable/unverified behavior.

## 38. Low-Priority Issues

- Related Products section is unavailable by current design; do not implement without approved criteria.
- FAQ schema readiness is deferred and should not be added in this audit step.
- Gallery active thumbnail state lacks explicit ARIA state.
- Price detail/booking contexts could match listing screen-reader context.
- Product Detail Page Section registry is mapping-only; clarify this in future docs if stakeholders expect CMS-managed content.

## 39. Recommended Product Detail Improvement Roadmap

FRONTEND-16: Product Detail Backend Data Preparation & Visibility Plan  
Focus: approval-first plan for parent visibility, route binding tests, backend view payload, CTA validity, and query budget approach.

FRONTEND-17: Product Detail Backend Data Preparation & Visibility Implementation  
Focus: align visibility, prepare display-ready payloads, add focused tests.

FRONTEND-18: Product Detail Layout & Section Hierarchy  
Focus: visible breadcrumb, overview empty behavior, meeting-point placement, section order only after approval.

FRONTEND-19: Product Detail Gallery & Media UX  
Focus: SSR first image, image dimensions/loading, fallback behavior, active state semantics.

FRONTEND-20: Product Detail Booking & WhatsApp CTA Flow  
Focus: CTA validity, global fallback behavior, message centralization, missing-number state.

FRONTEND-21: Product Detail Optional Sections, Accessibility, SEO & Schema  
Focus: optional wrappers, keyboard behavior, visible/schema breadcrumb alignment, Product/FAQ schema decisions.

FRONTEND-22: Product Detail Final Performance, Regression & Release Verification  
Focus: query count, browser screenshots, keyboard/zoom QA, focused tests, full tests, build, and release decision.

## 40. Files Inspected

- `AGENTS.md`
- `routes/frontend.php`
- `routes/web.php`
- `bootstrap/app.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Models/Product.php`
- `app/Models/Category.php`
- `app/Models/Destination.php`
- `app/Models/ProductPrice.php`
- `app/Models/ProductImage.php`
- `app/Models/ProductFeature.php`
- `app/Models/ProductItinerary.php`
- `app/Models/ProductNote.php`
- `app/Models/ProductFaq.php`
- `app/Providers/AppServiceProvider.php`
- `app/Support/BookingCtaSettings.php`
- `app/Support/ContactInformationSettings.php`
- `app/Support/DefaultMediaAssets.php`
- `app/Support/PageSectionRegistry.php`
- `app/Support/StructuredDataBuilder.php`
- `resources/views/layouts/frontend.blade.php`
- `resources/views/partials/site-social-share-meta.blade.php`
- `resources/views/partials/site-structured-data.blade.php`
- `resources/views/frontend/products/show.blade.php`
- `resources/views/frontend/components/product-price.blade.php`
- `resources/css/frontend-products.css`
- `tests/Feature/Frontend/ProductDetailBookingFormTest.php`
- `tests/Feature/Frontend/ProductPageSectionKeyTest.php`
- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `tests/Feature/Admin/GlobalBookingCtaSettingsTest.php`
- `tests/Feature/Admin/GlobalSeoDefaultSettingsTest.php`
- `tests/Feature/Admin/GlobalStructuredDataSettingsTest.php`
- `tests/Feature/Admin/GlobalDefaultMediaAssetsTest.php`
- `database/factories/ProductFactory.php`
- relevant Product/database migrations
- `docs/modules/products.md`
- `docs/architecture/frontend-backend-sync.md`
- `docs/Product/Product_Detail_UI/product-detail-ui-refresh.md`
- `docs/Page_Sections/page-sections-product-page-sync.md`
- `docs/database/relationships.md`
- `docs/database/data-integrity.md`
- relevant global SEO/booking/default-media docs
- relevant frontend/database reports

## 41. Files Recommended for Future Changes

Potential future files, pending approval:

- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Support/ProductDetailContent.php` or equivalent new support class
- `app/Models/Product.php`
- `resources/views/frontend/products/show.blade.php`
- `resources/views/frontend/components/product-price.blade.php`
- `resources/css/frontend-products.css`
- `tests/Feature/Frontend/ProductDetailBookingFormTest.php`
- new Product Detail visibility/data-flow test file if preferred
- `docs/modules/products.md`
- `docs/architecture/frontend-backend-sync.md`
- future implementation reports under `ai/reports/frontend/`

Do not change schema, migrations, route names, Product fields, or Page Builder behavior without explicit approval.

## 42. Risk and Rollback Notes

This audit changed only the report file. Rollback for FRONTEND-15 is to remove:

- `ai/reports/frontend/frontend-15-public-product-detail-uiux-data-flow-audit.md`

Do not revert existing Product Listing implementation/report files in the current worktree unless separately requested.

## 43. Approval Points

Approval required before implementation decisions:

- Whether Product Detail must exactly match `Product::publiclyVisible()` for inactive/archived parents.
- Whether parent-hidden Product Detail should 404 or use another policy.
- Whether to introduce a support/view-model class for Product Detail data preparation.
- WhatsApp missing-number fallback behavior.
- WhatsApp message content and whether price/currency context should be included.
- Visible breadcrumb placement and labels.
- Gallery SSR/enhancement strategy.
- Lightbox/modal or swipe behavior, if any.
- Sticky mobile CTA, if any.
- Product Detail section order changes.
- Related Products criteria, if related products are desired.
- FAQ/Product/Breadcrumb schema implementation details.
- Rich-text sanitization policy if Product description ever allows trusted HTML.
- Future normalized duration field or map integration.
- CMS-managed generic CTA copy versus product-owned CTA copy.

## 44. Recommended Next Step

Recommended next step:

```text
FRONTEND-16:
Product Detail Backend Data Preparation & Visibility Plan
```

FRONTEND-16 should remain planning/read-only first because Product Detail has multiple architecture and approval decisions: parent visibility policy, CTA fallback policy, gallery SSR/enhancement strategy, and backend-to-Blade data ownership.

## Required Scores

| Area | Score | Reason |
| ---- | ----: | ------ |
| Product Detail Architecture | 74/100 | Route/view exist and are coherent, but parent visibility and Blade data preparation need work |
| Backend Data Flow | 68/100 | Relations are loaded, but display-ready payloads are mostly assembled in Blade |
| Visibility Integrity | 58/100 | Drafts are blocked, but inactive/archived parents are not |
| Query Efficiency | 80/100 | Eager loading is present and no related query exists, but query count was not measured |
| Relation Loading | 82/100 | Required rendered relations are loaded; images/features are still ordered/grouped in Blade |
| Information Hierarchy | 78/100 | Core facts and CTA are visible, but breadcrumb absent and meeting point is late |
| Gallery/Media Readiness | 60/100 | Fallbacks exist, but main gallery is JS-dependent and missing dimensions/loading |
| Price Rendering | 86/100 | IDR/SGD/missing-price behavior is reused and tested; zero-price policy remains undecided |
| CTA/WhatsApp Flow | 62/100 | Business flow is correct, but missing-number CTA can break conversion |
| Optional Section Handling | 78/100 | Features/itinerary/notes/FAQ omit empty wrappers; Overview does not |
| Responsive Readiness | 72/100 | CSS contract is present, but no Product Detail browser QA was done |
| Accessibility Readiness | 70/100 | Native controls and one H1 exist; breadcrumb/gallery state/keyboard QA gaps remain |
| SEO Rendering Readiness | 76/100 | Metadata and Product schema exist; visibility gap can expose indexable stale pages |
| AI Discovery Readiness | 76/100 | Product facts are server-rendered, but gallery and breadcrumb gaps reduce clarity |
| Performance Readiness | 70/100 | Eager loading helps; gallery resource behavior and query count need measurement |
| CMS Scalability | 78/100 | Product-owned content is clear; Page Section mapping is registry-only and should stay that way |
| Testing Readiness | 56/100 | Current tests cover booking/price/section keys, but not visibility, gallery order, edge states, or query count |
| Overall Product Detail Readiness | 69/100 | Functional foundation exists, but high-priority visibility, gallery, and CTA issues block release parity |

## Verification

Final required verification commands for this read-only step:

```bash
git diff --check
git status --short
```

Full `php artisan test` was not run because no runtime code changed and this step intentionally changed only the audit report. Existing focused Product Detail tests were inspected, not modified.
