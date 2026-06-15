# Products Module

Last updated: 2026-06-15

## Category and Destination Integrity

Products require both a Category and a Destination.

Rules:

- `category_id` and `destination_id` are required.
- Product parent references are protected by restricted delete foreign keys.
- A Category or Destination archive action does not delete products.
- A Category or Destination cannot be permanently deleted while products reference it.
- Product-owned child data remains governed by Product-level relationships and constraints.

## Status Policy

Products use string publication status values:

- `draft`
- `published`

`ProductFactory` defaults to `published` and provides explicit `draft()` and `published()` states for focused tests and seed/demo data.

## Public Product Visibility Policy

The public Product Listing and public Product Detail use `Product::publiclyVisible()`.

Rules:

- Product status must be `published`.
- The related Category must be active and not archived.
- The related Destination must be active and not archived.
- Archiving or deactivating a Category/Destination hides related Products from the public listing and direct public Product Detail URLs without deleting Product records.
- Admin Product queries are not changed by this opt-in public scope.

The Product Listing card query uses `Product::frontendListingReady()` to eager load only listing-card relations: Category, Destination, Prices, and Images.

The Product Detail query resolves the route slug through `Product::publiclyVisible()`, eager loads only rendered Product Detail relations, and returns 404 for draft Products, invalid status values, inactive/archived parents, and invalid slugs.

## Public Product Detail Display State

`ProductController::show()` prepares Product Detail display state through `App\Support\ProductDetailDisplayState` before rendering Blade.

Prepared state:

- Price state uses loaded `prices`, treats only positive numeric values as displayable, prefers IDR when present, shows SGD when IDR is missing, and falls back to `Price on request` without rendering `Rp 0`.
- Media state uses Product thumbnail first, then ordered Product gallery images, then `default_media.product`; duplicate media paths are collapsed before rendering.
- Duration, meeting point, pickup, overview description, highlights, feature groups, itinerary items, notes, FAQs, add-ons, and optional section visibility are prepared before Blade.
- Product Detail optional rows are trimmed in display state: empty highlight titles, feature values, itinerary rows, notes, FAQ questions, CTA copy, duration, and meeting point values are omitted before Blade renders. Description-only itinerary/note rows remain valid, while impossible clock-shaped itinerary times render without a time badge.
- WhatsApp state uses Product WhatsApp number first, then configured global/contact number; malformed or missing numbers are treated as unavailable.
- Product Detail WhatsApp state prepares chat and booking labels, accessibility labels, messages, notes, and final `wa.me` URLs before Blade renders.
- When no usable number exists, Product Detail WhatsApp CTAs are hidden instead of rendering a broken `wa.me` URL.
- Metadata state prepares Product title, description, canonical URL, robots value, social share type, and image fallback from Product SEO fields and prepared media state.
- Breadcrumb state is prepared for Product Detail and rendered as visible breadcrumb UI above the Product summary.
- Product Detail gallery media is server-first: the prepared primary image renders as normal HTML, then Alpine enhances the same image element for thumbnail navigation and previous/next controls.
- Gallery thumbnails render only when more than one prepared media item exists, use button controls with accessible labels and active state, and keep non-primary media lazy-loaded.

Blade behavior:

- `resources/views/frontend/products/show.blade.php` renders prepared display state and does not call Product queries, relation methods, global setting helpers, or media fallback helpers directly.
- Product Detail section visibility is display-state driven; empty optional overview, feature, itinerary, note, FAQ, add-on, CTA note, booking helper note, duration, meeting point, and WhatsApp CTA wrappers do not render.
- Product Detail layout renders one Product H1, visible breadcrumb, a responsive media-plus-summary area, prepared summary metadata, price state, WhatsApp CTA when available, and content sections in this order: Description, Features, Itinerary, Notes, FAQs.
- Product Detail booking CTAs are normal server-rendered anchors and do not require JavaScript to determine the phone number, message, or URL.
- This does not change public visibility policy, Product Detail route shape, admin Product behavior, related products, schema markup, or listing behavior.

## Public Listing CMS Content

The public Product Listing uses Page Sections for editable intro/catalog content while keeping listing behavior in application code.

Page Section keys:

- `products.index.hero`
- `products.index.catalog`
- `products.index.filter_modal`
- `products.index.sort_modal`

Runtime behavior:

- `products.index.hero` can provide hero label, title, subtitle, description, and optional legacy image.
- `products.index.catalog` can provide catalog heading, optional supporting paragraph, and optional CTA when both button text and a safe URL are present.
- Missing, inactive, or empty sections use code fallbacks.
- Page Sections do not control Product query, visibility, filters, sorting, pagination, currency, duration, Product cards, or route names.

## Public Listing Price and Sorting Policy

The public Product Listing keeps the existing multi-currency display while using an explicit IDR price context for price filtering and price sorting.

Display rules:

- Products with IDR and SGD prices show IDR as the primary listing price and SGD as the secondary price.
- Products with IDR only show IDR.
- Products with SGD only show SGD and remain visible in the general listing.
- Products without price rows show `Price on request`.
- Missing price is not treated as zero.

Filter/sort rules:

- `min_price` and `max_price` check only `IDR` price rows.
- Products without an IDR price do not match IDR price range filters.
- `price_low` sorts by IDR price ascending.
- `price_high` sorts by IDR price descending.
- Products without an IDR price are placed after products with IDR prices for both price sorts.
- Duration remains display/filter text only; duration sorting is disabled until normalized duration data exists.

## Public Listing Filter, Search, and Pagination UX

The public Product Listing uses shareable GET query parameters for existing backend-supported controls.

Supported listing parameters:

- `category[]`
- `destination[]`
- `duration[]`
- `vehicle_type[]`
- `min_price`
- `max_price`
- `sort`
- `page`

Current search behavior:

- The homepage discovery form routes users to `/products` with `category[]` and `destination[]`.
- Public Product Listing does not currently support a free-text `search` query parameter.
- FRONTEND-11 did not add a new free-text search parameter because listing query semantics were intentionally preserved.

UX behavior:

- Filter and sort forms use `method="GET"`.
- Filter application uses explicit Apply buttons.
- Active filter labels are prepared by `ProductController@index` from normalized state.
- Invalid or unavailable query values are not displayed as active filters.
- Clear-all reset returns to the base Product Listing route.
- Result count uses the paginator total.
- Pagination uses 9 Products per page on the public Product Listing so the desktop three-column grid can render full 3-by-3 pages when enough Products exist.
- Pagination keeps normalized valid parameters and drops unsupported query values.

## Public Listing Responsive, Image, and Empty-State Contract

The Product Listing grid is mobile-first and does not use horizontal card scrolling.

Responsive grid:

- 320px through 639px: one column.
- 640px through 1023px: two columns.
- 1024px and wider: three columns with wider gaps on large desktop.
- A full public listing page can render 9 Products as three rows of three cards on desktop.

Image behavior:

- Product thumbnail remains the preferred source.
- `default_media.product` is used when the thumbnail is missing.
- If no product thumbnail or fallback asset exists, the existing text placeholder renders.
- Listing product images use a stable 4:5 card frame, explicit `width` and `height`, native `loading="lazy"`, and async decoding.
- Fallback product image alt text is based on the Product name, with Destination context when available, rather than placeholder asset copy.

Empty-state behavior:

- Global empty listing shows the no-public-products message.
- Filtered empty listing keeps the FRONTEND-11 clear-filters recovery.
- Invalid filter state keeps the clear-filters recovery and explanatory notice.
- A page number above the available paginator range renders a page-empty state with a first-page recovery URL.
- No free-text Product Listing search state exists yet because no public `search` parameter is implemented.

## Public Listing Accessibility and SEO Rendering

The public Product Listing prepares listing SEO and accessibility state in `ProductController@index` and renders it through the existing frontend layout/meta partials.

Accessibility behavior:

- The listing has one logical H1 from the CMS-backed hero title or code fallback.
- Product Listing cards use H3 product headings under the catalog section heading.
- Filter and sort controls remain GET forms and use visible labels, fieldsets, legends, and accessible dialog names.
- Active filter chips include remove links with descriptive `aria-label` values.
- Product detail links are standard crawlable anchors; listing card Details CTAs include product-specific accessible names.
- Listing prices include screen-reader context such as `Price starts from` while preserving FRONTEND-08 IDR/SGD display rules.
- Empty states are labeled sections with clear recovery actions.
- Product Listing pagination uses a listing-scoped pagination view with `aria-label="Product listing pagination"`, `aria-current="page"`, disabled non-links, and crawlable page anchors.

SEO rendering behavior:

- Base `/products` uses listing-specific title, meta description, canonical URL, and `index, follow`.
- Plain pagination pages with valid results self-canonicalize to their page URL and remain `index, follow`.
- Filter, sort, price-range, invalid, unsupported, and high-page URLs render `noindex, follow` and canonicalize to the clean Product Listing URL.
- Canonical URLs are built from normalized route state, not raw request query strings.
- Unknown query parameters are not preserved in paginator links and are treated as duplicate-content risk for robots policy.
- The listing reuses the existing structured-data builder for BreadcrumbList and a minimal ItemList containing only public listed product names, positions, and Product Detail URLs.
- ItemList does not include fake ratings, reviews, availability, or offer data.

## Product Query Index

The Products module has a non-unique composite index:

- `products_status_created_at_index` on `products(status, created_at)`.

This supports common product queries that filter by publication status and read newest products first. It does not change product visibility, sorting behavior, pagination, or draft/published policy.

## Product Price Integrity

The Products module stores prices in `product_prices`.

Supported currencies:

- `IDR`
- `SGD`

Rules:

- A product can store IDR and SGD prices together.
- A product cannot store duplicate rows for the same currency.
- Admin product create/update uses `ProductPriceService::sync()` to update existing currency rows instead of inserting duplicates.
- Price input must be numeric and non-negative.

Current guardrails:

- Database unique index: `product_prices_product_id_currency_unique`.
- Form Request validation for product price amount fields.
- ProductPrice service allowlist for supported currencies.

## Remaining Product Risks

- Product hard delete still cascades product-owned child rows.
- Product price range index `product_prices(currency, price)` remains deferred.
- Public duration sorting requires a future approved normalized duration field or controlled duration taxonomy.
