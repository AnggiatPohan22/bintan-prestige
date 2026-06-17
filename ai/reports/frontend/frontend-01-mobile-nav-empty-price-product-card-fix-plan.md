# STEP FRONTEND-01 - Mobile Navigation, Empty Price State, and Product Card Fix Plan

Date: 2026-06-13
Scope: Planning/read-only audit for mobile navigation, empty product price state, product card media/action consistency, and product card component consolidation.

## 1. Executive Summary

The public frontend already has a solid Laravel MVC direction: routes are small, frontend controllers prepare product data, product prices are backed by database integrity rules, and Blade templates render prepared data without direct database queries.

The next frontend implementation should stay small and focus on three fixes:

1. Add a mobile navigation menu that mirrors the desktop navigation/settings contract.
2. Replace misleading `Rp 0` display when a product has no IDR price row.
3. Consolidate duplicated product card logic while preserving separate visual variants for homepage and listing contexts.

This step is planning only. No runtime UI/code/database changes were made.

## 2. Current Mobile Navigation Findings

Current desktop navigation:

- Rendered in `resources/views/frontend/partials/header.blade.php`.
- Uses `$navigationSettings['items']`.
- Supports child menu items as dropdowns.
- Uses `NavigationSettings::resolveUrl()` and `NavigationSettings::isActiveUrl()`.
- CTA uses global Booking CTA if enabled.
- Header scroll behavior is handled by `resources/js/frontend.js`.

Current mobile navigation:

- `.frontend-nav` is hidden below `md` via `@apply hidden ... md:flex`.
- No mobile drawer, menu button, menu panel, or mobile submenu markup was found.
- Header actions remain visible as CTA/icon, but these are not a replacement for full navigation.
- No `aria-expanded`, `aria-controls`, Escape-close, outside-click-close, or body scroll lock exists for mobile menu because there is no mobile menu yet.

Desktop menu coverage:

- Default menu items are Home, Packages, Destinations, Contact.
- Admin global navigation supports children/dropdowns.
- Package submenu items such as Taxi, Hotel, Activity, and Tour Package can be configured as children, but defaults do not include them.
- About and FAQs are present in footer defaults, not header defaults.

## 3. Mobile Navigation Fix Plan

Recommended FRONTEND-02 approach:

1. Add a mobile menu button inside `frontend.partials.header`.
2. Keep existing desktop navigation unchanged.
3. Render the same `$navigationItems` in a mobile panel/drawer.
4. Preserve all child links under their parent item.
5. Use native anchors for no-JS fallback where possible.
6. Use a lightweight JS initializer in `resources/js/frontend.js` only for open/close behavior.
7. Add `aria-expanded`, `aria-controls`, and an accessible button label.
8. Close menu on:
   - close button click;
   - link click;
   - backdrop/outside click;
   - Escape key.
9. Lock body scroll only while menu is open.
10. Keep active state from existing `NavigationSettings::isActiveUrl()` logic.

Recommended mobile menu behavior:

- Primary trigger: visible below `md`.
- Desktop nav remains visible from `md` upward.
- Mobile panel can be a right-side drawer or full-width top sheet, using existing black/gold/white theme.
- Child menu items can render expanded by default in mobile for better no-JS and touch behavior.
- Do not create new routes or hardcode Taxi/Hotel/Activity pages in this step.

## 4. Current Price Rendering Findings

Current price rendering locations:

- `resources/views/frontend/components/product-card.blade.php`
- `resources/views/frontend/products/partials/card.blade.php`
- `resources/views/frontend/products/show.blade.php`

Current pattern:

- IDR price uses `number_format($product->idr_price ?? 0, 0, ',', '.')`.
- SGD price only renders when `$product->sgd_price` is truthy.

Root cause of misleading `Rp 0`:

- `Product::getIdrPriceAttribute()` returns `null` when no IDR price row exists.
- Blade uses `?? 0`, so `null` becomes zero.
- A real zero price row and a missing price row become visually identical.

Current backend contract:

- Product prices support `IDR` and `SGD`.
- `ProductPriceService` enforces supported currency and non-negative numeric value.
- Database unique index prevents duplicate `product_id + currency`.
- Admin validation rejects negative prices.
- `0` is currently a valid non-negative value unless business rules are changed later.

## 5. Empty Price State Decision

Recommended decision for FRONTEND-02:

- Treat missing IDR and missing SGD as "price unavailable", not zero.
- Keep valid numeric `0` as a real value if a row exists and business rules allow it.
- Use frontend label: `Price on request`.

Why `Price on request`:

- Matches current English-heavy public frontend.
- Sounds premium and travel/service appropriate.
- Avoids inventing a fake price.
- Keeps WhatsApp CTA flow natural.

Presentation rule:

- If IDR exists, render `Rp {formatted}`.
- If SGD exists, render `SGD {formatted}`.
- If neither exists, render `Price on request`.
- If only SGD exists, render SGD as primary or show `Price on request` plus SGD as secondary only if design needs primary IDR continuity.

Recommended implementation pattern:

- Prefer a small Blade partial/presenter for price rendering to avoid duplicated conditionals.
- Avoid changing database schema or product price service in FRONTEND-02.
- Add tests for missing price, IDR-only, SGD-only, and dual-currency rendering.

## 6. Current Product Card Variants

Current card variants:

1. Homepage card:
   - File: `resources/views/frontend/components/product-card.blade.php`
   - Style: white card, compact, image on top, short description, meta, detail CTA.
   - CSS namespace: `.bp-product-card`.
   - Used by: `resources/views/frontend/sections/popular-products.blade.php`.

2. Product listing card:
   - File: `resources/views/frontend/products/partials/card.blade.php`
   - Style: image-led overlay card, large price, title link, metadata, media icon buttons.
   - CSS namespace: `.product-card`.
   - Used by: `resources/views/frontend/products/index.blade.php`.

No dedicated category listing, destination listing, or related product card usage was found because those public pages/sections do not exist yet.

## 7. Product Card Duplication Map

Duplicated logic:

- Product image source resolution.
- Default product placeholder resolution.
- Placeholder object-fit handling.
- Product image alt text.
- IDR price formatting.
- SGD price formatting.
- Category label rendering.
- Destination and duration meta rendering.
- Detail link rendering.

Variant-specific logic:

- Homepage card has short description and detail button.
- Listing card has overlay layout and media action buttons.
- Listing card builds product media item arrays for the media modal.

Duplication risk:

- Empty price fix must be applied in multiple places unless centralized.
- Media fallback behavior can diverge between home/listing cards.
- Future destination/category cards may copy the old pattern again.

## 8. Recommended Component Structure

Recommended FRONTEND-02 component structure:

- Create or convert toward one reusable product card partial/component with explicit variants:
  - `home`
  - `listing`
  - optional future `compact`

Conservative option:

- Keep existing files for now.
- Extract shared mini partials:
  - product price rendering partial;
  - product image data preparation helper/partial;
  - product meta rendering partial.

Preferred small implementation:

- Use `resources/views/frontend/components/product-card.blade.php` as the canonical product card entry point.
- Add a `$variant` parameter:
  - default/home variant keeps `.bp-product-card`.
  - listing variant renders existing `.product-card` markup.
- Update listing include to call the canonical component with `variant => 'listing'`.
- Keep old listing partial temporarily as a wrapper only if needed for rollback/readability.

Avoid:

- Over-abstracting with too many props.
- Changing controller queries.
- Changing product routes.
- Rebuilding product listing layout.
- Moving heavy formatting into JavaScript.

## 9. Product Card Media Plan

Current media behavior:

- Homepage card uses thumbnail or default product placeholder.
- Listing card uses thumbnail or default product placeholder plus extra product images for modal.
- Listing card opens image modal with prepared media items.
- Listing card always renders video action with empty media items.
- Images lazy-load and have alt text.
- Most product card images do not include explicit `width`/`height`.

Recommended FRONTEND-02 media changes:

1. Keep thumbnail source contract unchanged.
2. Keep default product placeholder fallback unchanged.
3. Hide the video icon unless real video data exists.
4. Keep image modal action only when image/media items exist.
5. Preserve image alt text:
   - product thumbnail: product name;
   - placeholder: placeholder alt or `Product placeholder image`.
6. Keep existing aspect ratio/height classes to avoid layout disruption.
7. Add width/height planning as a later image optimization step, unless it is safe and local to card markup.

Do not change:

- Upload pipeline.
- Storage paths.
- Product image database structure.
- Product detail gallery behavior.

## 10. Product Card Action Plan

Current actions:

- Homepage card:
  - Image link to product detail.
  - Title link to product detail.
  - `Details` button to product detail.

- Product listing card:
  - Title link to product detail.
  - Image modal icon.
  - Video modal icon, even when no video exists.

Recommended action hierarchy:

- Primary action: view product detail.
- Secondary action: view image/media when media exists.
- WhatsApp action should remain on product detail page for now, not added to every card unless approved later.

Implementation plan:

- Avoid making the whole card clickable if it would conflict with icon buttons.
- Keep title/detail CTA as normal links.
- Ensure icon-only buttons have accessible labels.
- Ensure mobile touch targets remain at least 44px.
- Do not change WhatsApp business flow.

## 11. Backend Data Contract Impact

Expected backend impact for FRONTEND-02:

- No database schema changes.
- No route changes.
- No controller query changes required for this scope.
- No product price service changes required.
- No model relationship changes required.

Potential optional backend helper:

- Add lightweight model/helper accessors only if Blade duplication becomes too high, for example:
  - `hasIdrPrice()`
  - `hasSgdPrice()`
  - `formatted_price` style helper

Recommendation:

- Prefer presentation-level helper/partial first.
- Do not alter `ProductPriceService` because it correctly owns write integrity, not display state.

## 12. Blade Impact

Expected FRONTEND-02 Blade files:

- `resources/views/frontend/partials/header.blade.php`
- `resources/views/frontend/components/product-card.blade.php`
- `resources/views/frontend/products/partials/card.blade.php`
- `resources/views/frontend/products/show.blade.php`
- possibly a new product price partial/component under `resources/views/frontend/components/`

Blade principles:

- No database queries in Blade.
- Keep existing route names.
- Keep existing frontend sections.
- Keep output escaped.
- Use `@js` for data passed to Alpine.
- Preserve current CSS class contracts where possible.

## 13. CSS/Tailwind Impact

Expected FRONTEND-02 CSS files:

- `resources/css/frontend-theme.css`
- `resources/css/frontend-home.css` only if home card state needs styling.
- `resources/css/frontend-products.css` only if listing price/action state needs styling.

CSS plan:

- Add mobile nav/drawer classes near existing header styles.
- Reuse existing black/gold/white tokens.
- Add empty price state styles that keep card height stable.
- Avoid large visual redesign.
- Add `prefers-reduced-motion` support if adding drawer transitions or modifying product hover.

Do not:

- Introduce a new design system.
- Use external CSS libraries.
- Move admin styling into frontend CSS.

## 14. JavaScript Impact

Expected FRONTEND-02 JS file:

- `resources/js/frontend.js`

Recommended JS:

- Add `initMobileNavigation()` to the existing frontend bootstrap.
- Use data attributes such as:
  - `data-mobile-nav`
  - `data-mobile-nav-toggle`
  - `data-mobile-nav-panel`
  - `data-mobile-nav-close`
- Avoid duplicate event listeners.
- Close on Escape.
- Close on outside/backdrop click.
- Close on link click.
- Toggle body scroll lock via a class.

No new package is needed.

## 15. Accessibility Impact

Expected improvements:

- Mobile menu becomes keyboard reachable.
- Trigger exposes `aria-expanded`.
- Trigger points to panel through `aria-controls`.
- Panel uses semantic navigation.
- Close button has accessible label.
- Icon buttons remain labelled.
- Empty price state is text, not only visual styling.

Risks to guard:

- Focus may escape behind drawer if no focus management is added.
- Body scroll lock must not trap desktop users after resize.
- Hidden mobile menu must not duplicate confusing landmarks for screen readers.

Recommended minimum:

- Use `hidden`/class state carefully.
- Move focus to close button or first link when menu opens.
- Return focus to trigger when menu closes.
- Preserve no-JS anchor access by keeping desktop/header CTA available.

## 16. Performance Impact

Expected impact:

- Minimal if mobile nav JS is small and initialized once.
- No backend query changes.
- Hiding empty video action reduces useless modal interactions.
- Price display conditional has negligible cost.

Performance rules:

- No new carousel/menu library.
- Use transform/opacity for drawer animation.
- Respect `prefers-reduced-motion`.
- Do not load extra product data into frontend.
- Keep product listing pagination unchanged.

## 17. Responsive QA Plan

Manual responsive QA targets:

- 320px
- 375px
- 768px
- 1024px
- 1280px
- 1440px

Checklist:

- Header brand, CTA/icon, and mobile menu button fit at 320px.
- Mobile menu opens without horizontal overflow.
- All configured navigation items render in mobile menu.
- Child menu items are visible/tappable on mobile.
- Menu closes on close button, link click, outside click, and Escape.
- Product cards keep stable height with IDR, SGD, dual price, and missing price.
- Listing card actions do not overlap title/meta.
- Touch targets feel comfortable.
- No text overlaps on cards.

## 18. Automated Testing Plan

Recommended focused tests:

1. Frontend header renders mobile menu trigger and panel.
2. Frontend header renders the same navigation items in desktop and mobile areas.
3. Frontend header renders child menu items in mobile navigation.
4. Product index with IDR price renders formatted IDR.
5. Product index with SGD price renders formatted SGD.
6. Product index with no price does not render `Rp 0`.
7. Homepage product card with no price does not render `Rp 0`.
8. Product detail with no price does not render `Rp 0`.
9. Product listing image action renders when image exists.
10. Product listing video action does not render when no video data exists.
11. Draft product remains hidden from public listing/detail according to current policy.

Full verification after implementation:

- `php artisan test`
- `php artisan route:list`
- `git diff --check`

Manual browser verification should be added for mobile menu behavior because PHP feature tests cannot fully validate Escape/outside-click/body-scroll behavior.

## 19. Files Inspected

Reports/docs:

- `ai/reports/frontend/improve-05-frontend-uiux-data-rendering-audit.md`
- `docs/frontend/README.md`
- `docs/modules/products.md`
- `docs/architecture/frontend-backend-sync.md`

Skills:

- `ai/skills/frontend-skill.md`
- `ai/skills/uiux-skill.md`
- `ai/skills/design-system-skill.md`
- `ai/skills/component-library-skill.md`
- `ai/skills/backend-skill.md`
- `ai/skills/performance-skill.md`
- `ai/skills/testing-qa-skill.md`

Frontend/header/card files:

- `resources/views/frontend/partials/header.blade.php`
- `resources/views/frontend/sections/popular-products.blade.php`
- `resources/views/frontend/components/product-card.blade.php`
- `resources/views/frontend/products/partials/card.blade.php`
- `resources/views/frontend/products/index.blade.php`
- `resources/views/frontend/products/show.blade.php`
- `resources/css/frontend-theme.css`
- `resources/css/frontend-home.css`
- `resources/css/frontend-products.css`
- `resources/js/frontend.js`

Backend/data contract files:

- `routes/frontend.php`
- `app/Http/Controllers/Frontend/HomeController.php`
- `app/Http/Controllers/Frontend/ProductController.php`
- `app/Models/Product.php`
- `app/Models/ProductPrice.php`
- `app/Services/ProductPriceService.php`
- `app/Support/NavigationSettings.php`

Tests:

- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `tests/Feature/Frontend/ProductDetailBookingFormTest.php`
- `tests/Feature/Frontend/ProductPageSectionKeyTest.php`
- `tests/Feature/Database/ProductPriceIntegrityTest.php`
- `tests/Feature/Admin/GlobalNavigationSettingsTest.php`

## 20. Files Recommended for FRONTEND-02 Changes

Likely changed:

- `resources/views/frontend/partials/header.blade.php`
- `resources/js/frontend.js`
- `resources/css/frontend-theme.css`
- `resources/views/frontend/components/product-card.blade.php`
- `resources/views/frontend/products/partials/card.blade.php`
- `resources/views/frontend/products/show.blade.php`
- `tests/Feature/Frontend/ProductIndexUiTest.php`
- `tests/Feature/Frontend/ProductDetailBookingFormTest.php`
- `tests/Feature/Admin/GlobalNavigationSettingsTest.php`

Possible new file:

- `resources/views/frontend/components/product-price.blade.php`

Optional docs/report after implementation:

- `ai/reports/frontend/frontend-02-mobile-nav-empty-price-product-card-implementation-report.md`
- `docs/frontend/components.md`
- `docs/changelog/CHANGELOG.md`

## 21. Risks

Main risks:

- Mobile drawer could accidentally hide or duplicate navigation for screen readers.
- Body scroll lock could remain active after menu close or viewport resize.
- Consolidating cards too aggressively could alter homepage/listing visual design.
- Empty price state could conflict with current admin requirement that prices are required.
- Existing tests may need updates because some current factory-created products have no prices and currently render as `Rp 0`.

Risk controls:

- Keep desktop header unchanged.
- Keep product routes unchanged.
- Add focused tests before broad UI changes.
- Use a small variant model rather than a total card redesign.
- Treat price display as presentation fix, not business rule change.

## 22. Rollback Plan

If FRONTEND-02 causes problems:

1. Revert the header Blade mobile drawer additions.
2. Revert `initMobileNavigation()` from `resources/js/frontend.js`.
3. Revert added mobile nav CSS classes.
4. Restore original product card includes/partials.
5. Restore original price display conditionals.
6. Run `php artisan test` and `git diff --check`.

No database rollback should be needed because FRONTEND-02 should not change schema or data.

## 23. Recommended Next Step

Proceed to `STEP FRONTEND-02: Mobile Navigation, Empty Price State, and Product Card Consistency Implementation`.

Recommended FRONTEND-02 scope:

1. Implement mobile nav in the existing header only.
2. Add safe missing price presentation.
3. Hide empty video/media actions.
4. Start product card consolidation with limited variants or shared price partial.
5. Add focused tests.
6. Run full `php artisan test`, `php artisan route:list`, and `git diff --check`.

## Verification

Commands run after report creation:

- `git diff --check` -> passed, no whitespace errors reported.
- `git status --short` -> only `ai/reports/frontend/frontend-01-mobile-nav-empty-price-product-card-fix-plan.md` is untracked from this step.
