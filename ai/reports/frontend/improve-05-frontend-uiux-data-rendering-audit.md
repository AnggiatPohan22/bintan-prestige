# STEP IMPROVE-05 - Frontend UI/UX & Data Rendering Audit

Date: 2026-06-13
Scope: Read-only audit of public frontend UI, UX, Blade rendering, frontend data flow, SEO rendering, accessibility, performance, and CMS sync.

## 1. Executive Summary

The public frontend is functional and already follows a clear Laravel MVC direction: public routes are small, controllers prepare most data before rendering, Blade files do not contain direct database queries, and frontend interactions are lightweight through Alpine plus a small custom `resources/js/frontend.js` module.

The strongest areas are product data rendering, global settings integration, SEO meta/JSON-LD foundations, and the luxury visual direction. The main gaps are frontend route coverage, mixed CMS-managed and hardcoded content, duplicated product card rendering, limited mobile navigation, missing reduced-motion handling, incomplete dedicated docs for frontend/SEO, and several image/performance/accessibility risks.

No runtime code was changed in this audit.

## 2. Current Frontend Architecture

Current public frontend entry points:

- `routes/frontend.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `resources/views/layouts/frontend.blade.php`
- `resources/views/frontend/home.blade.php`
- `resources/views/frontend/products/index.blade.php`
- `resources/views/frontend/products/show.blade.php`
- `resources/css/frontend.css`
- `resources/js/app.js`
- `resources/js/frontend.js`

The architecture is compact and CMS-aware, but it is still concentrated around Home and Products. Several public concepts exist in navigation labels, homepage sections, filters, or docs, but do not yet have dedicated frontend routes.

## 3. Public Route and Page Map

Active public routes:

- `GET /` -> `Frontend\HomeController@index` -> `home`
- `GET /products` -> `Frontend\ProductController@index` -> `products.index`
- `GET /products/{product:slug}` -> `Frontend\ProductController@show` -> `products.show`

Not currently present as dedicated public routes:

- Destination listing page
- Destination detail page
- Category detail page
- Taxi page
- Hotel page
- Activity page
- Tour package category page
- About page
- FAQ page
- Contact page
- Blog/news page
- Sitemap route

Default navigation points `Destinations` to `/#destinations` and `Contact` to `/#whatsapp-cta`, so the current frontend intentionally uses homepage anchors for some content.

## 4. Backend-to-Frontend Data Flow

Positive findings:

- Home data is prepared in `HomeController` before reaching Blade.
- Product listing filters, sorting, pagination, and price constraints are handled in `ProductController`.
- Product detail loads relationships before rendering.
- Global frontend settings are injected via `AppServiceProvider` and `GlobalSettingsService`.
- SEO defaults, social meta, structured data, navigation, contact, booking CTA, tracking, and assets are shared via settings helpers/composer data.

Risks:

- `Product::frontendReady()` eager-loads many relationships for every context, which is safe for N+1 prevention but broad for listing/home pages.
- Product visibility checks only enforce `products.status = published`; category/destination active state is not enforced in frontend product queries.
- Product listing filter option sources for durations, vehicle types, and price range are derived from published products/prices but do not scope through active category/destination visibility.
- Product detail uses route model binding and only checks product status, not parent category/destination visibility.

## 5. CMS-Managed vs Hardcoded Content Map

CMS-managed or settings-driven:

- Home page sections through `PageSection` for major homepage blocks.
- Header navigation and CTA through global navigation/booking settings.
- Footer columns, contact, social, maps, and CTA through global settings and Page Sections.
- Products, categories, destinations, product prices, images, features, FAQs, itineraries, notes.
- Default media placeholders.
- SEO defaults, social share image, structured data, tracking integrations.

Hardcoded or partially hardcoded:

- Homepage "Why Choose Us" cards.
- Journey feature cards.
- Testimonials names, roles, copy, ratings, and initials.
- Product index hero title and subtitle.
- Some CTA labels/fallback labels across frontend sections.
- Footer copyright year.
- Newsletter form uses `action="#"`.
- Placeholder labels such as `No Image` and `LOGO HERE`.

Recommended direction: keep the current fixed-layout CMS pattern, then move high-value hardcoded content into Page Sections or a future controlled module. Do not build a page builder automatically.

## 6. Blade Structure Findings

Positive findings:

- No direct database query calls were found in frontend Blade templates.
- Blade uses escaped output for normal content.
- JSON passed into Alpine uses `@js`.
- Product detail long text uses `e()` before `nl2br`.
- Page/section keys exist for products index/detail, helping future CMS mapping and tests.

Risks:

- Many Blade templates contain `@php` blocks for formatting, media selection, fallback resolution, and collection transformations.
- `resources/views/frontend/frontend.blade.php` is byte-identical to `resources/views/layouts/frontend.blade.php` and appears unused by active frontend pages.
- Product card media item preparation happens inside `resources/views/frontend/products/partials/card.blade.php`.
- Product detail feature grouping is performed in Blade from already-loaded collections.
- Tracking partials intentionally render configured custom scripts with raw output; this is a security governance risk even if expected for analytics.

## 7. Component Reusability Findings

Positive findings:

- Shared layout and header/footer partials exist.
- Home sections are split into section partials.
- Product page has partial card usage.
- `resources/views/frontend/components/section-media-slider.blade.php` provides reusable section media behavior.

Duplication:

- `resources/views/frontend/components/product-card.blade.php` and `resources/views/frontend/products/partials/card.blade.php` both render product cards with overlapping price, image, destination, duration, and CTA logic.
- Header/footer use settings helpers directly in Blade; this is workable but could become repetitive as frontend grows.

Recommended direction: create a single reusable product card view/component later with mode variants for home/listing, while preserving current routes and data contracts.

## 8. Design System Findings

Positive findings:

- The frontend theme has centralized CSS tokens in `frontend-theme.css`.
- Palette follows the black/gold/white/soft neutral luxury travel direction.
- Button, title, section, card, header, footer, and product patterns are established.
- `frontend.css` cleanly imports theme, home, and product CSS.

Gaps:

- AGENTS/skills mention Cinzel and Montserrat, while runtime loads Google Font `Forum` only; CSS references Montserrat but the layout does not load it from Google Fonts.
- CSS contains many bespoke section classes, which is expected for a custom premium site but needs documentation to avoid drift.
- Some placeholder UI labels can feel unfinished if fallback assets/content are missing.

## 9. Responsive Findings

Positive findings:

- CSS uses mobile-first Tailwind utilities and responsive breakpoints.
- Product listing uses horizontal mobile cards and desktop grid behavior.
- Product detail uses responsive gallery/content/sidebar layout.
- Many fixed visual frames use aspect ratios or stable heights.

Risks:

- Public header navigation is hidden at `md` and below, but no actual mobile menu was found; mobile users may only see brand plus CTA/icon.
- Header dropdowns rely on hover/focus-within and do not expose an explicit mobile-friendly dropdown control.
- Product filter/sort/media modals do not show clear focus trapping or initial focus management.
- This audit did not run browser viewport screenshots, so layout overflow and text fit remain static-inspection risks.

## 10. Header and Navigation Findings

Positive findings:

- Navigation is CMS/settings-driven.
- Active state helper exists.
- Header CTA can use global booking CTA and WhatsApp tracking.
- Header scroll behavior is throttled through `requestAnimationFrame`.

Gaps:

- No mobile navigation drawer/menu was found.
- Dropdown trigger is an anchor, not a button with `aria-expanded`.
- Dropdowns are hover/focus driven and may be difficult on touch devices.
- Header logo images do not include width/height attributes.

## 11. Homepage Findings

Positive findings:

- Homepage uses Page Sections, products, categories, destinations, FAQ preview, default media placeholders, and global settings.
- Hero and section media are CMS-aware.
- Product sections use backend-prepared products.
- FAQ preview uses dynamic FAQ data with fallback.
- Empty product state exists.

Gaps:

- Several homepage sections still carry hardcoded copy/features/testimonials.
- Hero/background media is partly CSS variable/JS driven, which can reduce image SEO/LCP clarity compared with an explicit optimized image element.
- Product tabs are client-side filtering over rendered products; acceptable at current scale, but backend tabs/filter routes may be needed later.
- Search form only filters destination/category, not free-text search.
- Some fallback content is generic and should be made brand/content-ready before launch.

## 12. Product Listing Findings

Positive findings:

- Product listing uses backend pagination.
- Filters are backend-driven through query parameters.
- Sort options exist for newest, price, and duration.
- Empty state exists.
- Breadcrumb markup exists.
- Product cards lazy-load images.

Gaps:

- No free-text search input.
- Product listing SEO uses global defaults; it does not set page-specific `seoTitle`/`seoDescription`.
- Price range is based on all IDR prices, not scoped to published products with active parents.
- Price display falls back to `Rp 0` when IDR price is missing, which can mislead visitors.
- Video media button is rendered even when product video items are empty.
- Filter/sort modals lack robust focus management.

## 13. Product Detail Findings

Positive findings:

- Product detail has title, image gallery, price, destination/category, overview, highlights, features, itinerary, notes, FAQs, booking form, WhatsApp CTA, and SEO variables.
- Product SEO title, description, keywords, canonical, and image are prepared in controller.
- JSON-LD product schema can render from the structured data builder.
- Booking form keeps public flow on WhatsApp and does not store visitor input.

Gaps:

- Main gallery uses Alpine `<template>` rendering; the primary large image is JS-dependent, although thumbnails/fallback content remain server-rendered.
- Breadcrumb UI is not visibly rendered on product detail, even though JSON-LD breadcrumb support exists.
- Related products are not shown.
- Missing IDR price can show `Rp 0`.
- Product detail does not enforce active/non-archived category or destination parent visibility.
- Main product gallery images lack width/height attributes.

## 14. Destination Findings

Current state:

- Destinations are used as product filters and homepage data.
- Navigation default points to `/#destinations`.
- Destination module documentation exists.

Gaps:

- No dedicated destination listing/detail public route.
- Homepage category/destination visual mapping uses generic placeholder behavior.
- Destination-specific SEO, copy, internal linking, and schema are not yet implemented as public pages.

## 15. FAQ/About/Contact Findings

FAQ:

- Home FAQ preview uses active FAQ records and fallback copy.
- Product detail FAQ uses product-specific FAQs.
- No dedicated FAQ public route exists.

About:

- About/Journey homepage section is partially CMS-driven.
- Feature cards remain hardcoded.
- No dedicated About public route exists.

Contact:

- Contact information and WhatsApp CTA are global-setting driven.
- Footer and header use WhatsApp/contact settings.
- No dedicated Contact public route exists.
- Newsletter form is placeholder-only with `action="#"`.

## 16. Image and Media Findings

Positive findings:

- Default media placeholder system is used broadly.
- Many below-the-fold images use `loading="lazy"` and `decoding="async"`.
- Product images have alt text based on product name or placeholder alt.
- Explore banner supports responsive picture sources.

Risks:

- Many images do not include explicit `width` and `height`, increasing CLS risk.
- Header/footer logos lack explicit dimensions.
- Hero and product listing hero backgrounds are CSS/JS background based.
- No WebP/AVIF enforcement was verified.
- Fallback placeholder text such as `No Image` and `LOGO HERE` can appear publicly when assets are missing.

## 17. Animation and JavaScript Findings

Positive findings:

- `resources/js/frontend.js` is focused and lightweight.
- Header scroll listener is throttled.
- Carousel logic uses native scroll and lightweight arrow state.
- WhatsApp tracking does not block navigation.

Risks:

- No `prefers-reduced-motion` handling was found in scanned CSS.
- Section sliders use `setInterval` continuously when multiple slides exist.
- Product gallery large image depends on Alpine template rendering.
- Product listing modals require Alpine for full interaction.
- Custom tracking scripts are rendered from settings and require strict admin governance.

## 18. Accessibility Findings

Positive findings:

- Major sections use `aria-labelledby`.
- Header nav has `aria-label`.
- Product media buttons use `aria-label`.
- Product booking steppers use explicit labels.
- FAQ uses semantic `details` blocks.
- Decorative icons often use `aria-hidden`.

Gaps:

- Mobile navigation is incomplete.
- Header dropdown does not use `aria-expanded` or button semantics.
- Product modals lack visible focus trapping and initial focus control.
- Some `role="tablist"` behavior uses buttons with `aria-pressed` rather than full tab semantics.
- Images in `aria-hidden` visual wrappers still carry alt text in some areas.
- No automated accessibility tests were found for public frontend pages.

## 19. SEO and AI Discovery Findings

Positive findings:

- Layout renders title, meta description, canonical, robots, Open Graph, Twitter/X card, favicon, and structured data partials.
- Product detail passes product-specific SEO values.
- Structured data builder supports Organization/Business, WebSite, BreadcrumbList, and Product schema.
- Important product content is mostly server-rendered HTML, not hidden behind API-only JavaScript.
- `robots.txt` exists and currently allows crawling.

Gaps:

- No sitemap file or route was found.
- Product listing lacks page-specific SEO variables.
- Destination/category/about/FAQ/contact pages are not available as dedicated crawlable pages.
- Product detail breadcrumb UI is absent even though schema support exists.
- Some credibility content is hardcoded testimonials rather than managed/provable CMS content.
- Image dimensions and optimized media metadata need improvement for search/social quality.

## 20. Performance Findings

Positive findings:

- Frontend CSS/JS is bundled through Vite.
- Public JS is modest and interaction-specific.
- Product index uses pagination.
- Controllers use eager loading to prevent obvious N+1 query issues.
- Global settings caching has been implemented in previous performance work.

Risks:

- `Product::frontendReady()` loads many relations for listing/home contexts.
- Home page loads featured products and latest products separately with overlapping relationships.
- Product index filter metadata runs additional queries for durations, vehicle types, and price range.
- Many images lack width/height.
- Hero/background images may not be optimized for LCP.
- Continuous section slider interval may keep running off-screen.

## 21. Empty/Error State Findings

Existing:

- Homepage product empty state exists.
- Category section empty state exists.
- Product listing empty state exists.
- Image placeholders exist.

Gaps:

- Product detail missing-price state is not visitor-friendly because price can display as zero.
- Product listing video button opens empty media.
- Newsletter has no success/error state.
- No dedicated public 404/empty destination/category state was found.
- Contact/booking WhatsApp fallback behavior should be tested when no number is configured.

## 22. Testing Gaps

Existing frontend tests:

- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `tests/Feature/Frontend/ProductDetailBookingFormTest.php`
- `tests/Feature/Frontend/ProductPageSectionKeyTest.php`

Current coverage strengths:

- Product index page renders and section keys exist.
- Product detail booking form renders.
- Product page section keys exist.

Gaps:

- No tests for homepage rendering.
- No tests for header/footer/global setting rendering on real frontend routes.
- No tests for product listing filters/sorting/pagination output.
- No tests for hidden/draft products on frontend listing/detail.
- No tests for inactive/archived category/destination frontend visibility.
- No tests for missing price display behavior.
- No accessibility, responsive, or browser smoke tests.
- No sitemap/robots route tests beyond static `robots.txt` presence.

## 23. Critical Issues

No critical frontend runtime issue was confirmed during static audit.

Critical candidate to avoid before launch:

- If production depends on mobile navigation, the absence of a mobile menu can block navigation for mobile visitors.

## 24. High Priority Issues

1. Mobile navigation is incomplete or absent.
2. Product detail parent visibility does not check active/non-archived category/destination.
3. Product listing/home queries do not filter by active/non-archived category/destination.
4. Missing sitemap reduces SEO and AI discovery readiness.
5. Missing price can render as `Rp 0`.
6. Product card implementations are duplicated.
7. Tracking custom scripts are raw rendered and need strict admin-only governance and documentation.

## 25. Medium Priority Issues

1. Several homepage sections remain hardcoded.
2. Product index lacks page-specific SEO metadata.
3. Destination/category/about/FAQ/contact dedicated public pages are missing.
4. Header dropdown accessibility needs stronger semantics.
5. Product modals need focus management.
6. Image dimensions and hero LCP handling need improvement.
7. No `prefers-reduced-motion` handling was found.
8. Testimonials contain mojibake star characters and hardcoded credibility content.

## 26. Low Priority Issues

1. `resources/views/frontend/frontend.blade.php` duplicates `resources/views/layouts/frontend.blade.php` and appears unused.
2. Placeholder labels like `No Image` and `LOGO HERE` should be polished.
3. Footer year is hardcoded.
4. Newsletter placeholder form has no backend behavior.
5. Some fallback labels mix English and Indonesian in the product listing UI.

## 27. Safe Frontend Improvement Roadmap

Recommended sequence:

1. Add mobile navigation UX without changing route names or backend contracts.
2. Fix price empty state and empty media actions.
3. Add frontend visibility scopes/tests for active category/destination decisions.
4. Add page-specific SEO for product listing.
5. Add sitemap implementation plan, then implementation.
6. Consolidate product card components carefully.
7. Move high-value hardcoded homepage content into existing Page Sections or controlled modules.
8. Add image dimension/LCP optimization pass.
9. Add reduced-motion support.
10. Plan destination/category public pages with SEO and internal linking.

## 28. Files Inspected

Project rules and skills:

- `AGENTS.md`
- `ai/skills/frontend-skill.md`
- `ai/skills/uiux-skill.md`
- `ai/skills/design-system-skill.md`
- `ai/skills/component-library-skill.md`
- `ai/skills/performance-skill.md`
- `ai/skills/seo-ai-discovery-skill.md`
- `ai/skills/cms-architect-skill.md`
- `ai/skills/testing-qa-skill.md`

Routes/controllers/models/services/providers:

- `routes/frontend.php`
- `routes/web.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Http/Controllers/Frontend/BookingController.php`
- `app/Models/Product.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/GlobalSettingsService.php`
- `app/Support/NavigationSettings.php`
- `app/Support/SeoDefaultSettings.php`
- `app/Support/StructuredDataBuilder.php`

Frontend views:

- `resources/views/layouts/frontend.blade.php`
- `resources/views/frontend/frontend.blade.php`
- `resources/views/frontend/home.blade.php`
- `resources/views/frontend/partials/header.blade.php`
- `resources/views/frontend/partials/footer.blade.php`
- `resources/views/frontend/partials/manual-ads.blade.php`
- `resources/views/frontend/components/product-card.blade.php`
- `resources/views/frontend/components/section-media-slider.blade.php`
- `resources/views/frontend/products/index.blade.php`
- `resources/views/frontend/products/show.blade.php`
- `resources/views/frontend/products/partials/card.blade.php`
- `resources/views/frontend/sections/about-journey.blade.php`
- `resources/views/frontend/sections/categories.blade.php`
- `resources/views/frontend/sections/explore-banner.blade.php`
- `resources/views/frontend/sections/popular-products.blade.php`
- `resources/views/frontend/sections/popular-tour.blade.php`
- `resources/views/frontend/sections/testimonials.blade.php`
- `resources/views/partials/site-brand-colors.blade.php`
- `resources/views/partials/site-favicon.blade.php`
- `resources/views/partials/site-social-share-meta.blade.php`
- `resources/views/partials/site-structured-data.blade.php`
- `resources/views/partials/tracking-head.blade.php`
- `resources/views/partials/tracking-body-start.blade.php`
- `resources/views/partials/tracking-body-end.blade.php`

Frontend assets:

- `resources/css/frontend.css`
- `resources/css/frontend-theme.css`
- `resources/css/frontend-home.css`
- `resources/css/frontend-products.css`
- `resources/js/app.js`
- `resources/js/frontend.js`
- `public/build/manifest.json`
- `public/robots.txt`

Docs/reports/tests:

- `docs/frontend/README.md`
- `docs/seo/README.md`
- `docs/architecture/frontend-backend-sync.md`
- `docs/performance/audit-report.md`
- `docs/performance/checklist.md`
- `docs/modules/products.md`
- `docs/modules/categories.md`
- `docs/modules/destinations.md`
- `ai/reports/backend/improve-03-backend-structure-cleanup-audit.md`
- `ai/reports/database/improve-04-database-relationship-query-audit.md`
- `ai/reports/performance/db-06-global-settings-cache-implementation-report.md`
- `ai/reports/documentation/doc-sync-db-database-improvement-report.md`
- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `tests/Feature/Frontend/ProductDetailBookingFormTest.php`
- `tests/Feature/Frontend/ProductPageSectionKeyTest.php`

## 29. Files Recommended for Future Changes

Potential future code files:

- `routes/frontend.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Models/Product.php`
- `resources/views/frontend/partials/header.blade.php`
- `resources/views/frontend/home.blade.php`
- `resources/views/frontend/products/index.blade.php`
- `resources/views/frontend/products/show.blade.php`
- `resources/views/frontend/products/partials/card.blade.php`
- `resources/views/frontend/components/product-card.blade.php`
- `resources/views/frontend/sections/testimonials.blade.php`
- `resources/css/frontend-theme.css`
- `resources/css/frontend-home.css`
- `resources/css/frontend-products.css`
- `resources/js/frontend.js`
- `tests/Feature/Frontend/*`

Potential future docs:

- `docs/frontend/design-system.md`
- `docs/frontend/components.md`
- `docs/frontend/homepage.md`
- `docs/frontend/product-pages.md`
- `docs/seo/frontend-seo-readiness.md`
- `docs/performance/frontend-performance.md`

## 30. Recommended Next Step

Proceed with a small implementation step, not a redesign:

STEP FRONTEND-01: Mobile Navigation, Empty Price State, and Product Card Media Action Fix Plan.

Recommended scope for the next planning step:

- Confirm mobile nav behavior and propose a safe header-only improvement.
- Replace misleading missing price output with a visitor-safe label.
- Hide/disable empty video media action on listing cards.
- Keep all routes, controller contracts, and public frontend layout structure stable.
- Add focused frontend tests before any visual expansion.

## Scoring

- Frontend Architecture: 76/100
- Backend Data Rendering: 82/100
- UI Consistency: 78/100
- UX Quality: 72/100
- Responsive Readiness: 68/100
- Component Reusability: 66/100
- Accessibility: 64/100
- Image Optimization Readiness: 61/100
- Animation Performance: 72/100
- SEO Rendering Readiness: 73/100
- Frontend Performance Readiness: 74/100
- CMS Sync Readiness: 70/100

## Verification

Commands run after report creation:

- `git diff --check` -> passed, no whitespace errors reported.
- `git status --short` -> only `ai/reports/frontend/` is untracked from this step.
