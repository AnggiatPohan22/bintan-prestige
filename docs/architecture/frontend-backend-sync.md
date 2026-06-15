# Frontend Backend Sync

Last updated: 2026-06-14

## Global Settings Flow

The CMS keeps global frontend display data in backend-managed tables:

- `site_settings`
- `site_assets`

The public frontend receives prepared data from Laravel, not from Blade database queries. DB-06 centralizes global settings/assets reads in `App\Services\GlobalSettingsService`.

Flow:

1. Admin updates a setting or asset.
2. Eloquent model events clear the related public cache key.
3. The next frontend request resolves `GlobalSettingsService`.
4. The service rebuilds cached settings/assets from the database if needed.
5. `AppServiceProvider` shares the same variable contract already used by Blade.

## Blade Contract

The existing variable contract is preserved, including:

- `siteAssets`
- `brandColors`
- `businessIdentity`
- `contactInformation`
- `contactWhatsappUrl`
- `socialMediaLinks`
- `activeSocialMediaLinks`
- `navigationSettings`
- `footerSettings`
- `seoDefaultSettings`
- `trackingIntegrationSettings`
- `bookingCtaSettings`
- `defaultMediaSettings`
- `structuredDataSettings`

Blade templates should keep rendering prepared data and must not query the database directly.

## Product Data Integrity Flow

The backend prepares Product data before it reaches Blade.

Current database-backed rules:

- Products use string statuses: `draft` and `published`.
- Product prices are written by `ProductPriceService` and are unique by `product_id` and `currency`.
- Supported product price currencies are `IDR` and `SGD`.
- Category and Destination archive flows use soft delete and do not delete Products.
- Category/Destination hard deletes are restricted by database FK rules while Products reference them.

Frontend implication:

- Public Product Listing uses `Product::publiclyVisible()` so listed Products must be published and attached to active, non-archived Category/Destination records.
- Public Product Listing query parameters are normalized before filtering to avoid array-shaped or unsupported values widening the result set.
- Product Listing eager loads only listing-card relations: Category, Destination, Prices, and Images.
- Product Listing displays IDR/SGD prices from loaded `prices` data, but price range filtering and price sorting use an explicit IDR context.
- Product Listing places missing-IDR Products after IDR-priced Products when sorting by price and keeps missing-price display as `Price on request`.
- Product Listing treats duration as display/filter text only; deprecated duration sort query values fall back to newest.
- Product Listing content is prepared by `ProductListingContent` from active `products.index.*` Page Sections with code fallbacks.
- Product Listing Page Sections control intro/catalog copy, optional hero image, and optional catalog CTA only; filters, sorting, pagination, product query, and Product cards remain application-owned.
- Product Listing filter UX uses normalized backend state for active filter labels, result count, empty-state copy, reset URLs, and pagination query persistence.
- Product Listing paginates public catalog results at 9 Products per page; admin Product pagination and homepage Product counts stay separate.
- Public Product Listing does not currently expose a free-text search parameter; homepage discovery still maps to existing `destination[]` and `category[]` filters.
- Product Listing high-page empty recovery is prepared by the controller so Blade renders a first-page URL without changing pagination semantics.
- Product Listing card image rendering keeps Product thumbnail first, then `default_media.product`, with Product-context alt text and a stable 4:5 frontend frame.
- Product Listing responsive layout is CSS-owned: one column on mobile, two on tablet, and three on desktop/large desktop without horizontal card scrolling.
- Product Listing metadata is prepared by `ProductController@index` and rendered by the existing frontend layout/meta partials: title, meta description, canonical URL, and robots policy.
- Product Listing base and valid plain pagination URLs are indexable; filter/sort/price/invalid/unsupported/high-page query URLs use `noindex, follow` with canonical back to the clean listing URL.
- Product Listing active filter summaries include backend-prepared remove URLs and accessible labels; Blade only renders the prepared state.
- Product Listing pagination uses a listing-scoped Blade pagination view so current page, previous/next, disabled states, and page anchors have Product Listing-specific accessible text.
- Product Listing structured data stays in `StructuredDataBuilder`: BreadcrumbList reuses the existing route-aware breadcrumb behavior, and ItemList contains only public listed Product names, positions, and detail URLs.
- Public Product Detail now resolves its slug through `Product::publiclyVisible()`, so direct detail URLs follow the same public visibility policy as Product Listing while keeping admin Product queries unrestricted.
- Product Detail eager loads only rendered detail relations: Category, Destination, Prices, Images, Highlights, Features, FAQs, Itineraries, and Notes. Image ordering is applied in the detail query; the other detail collections use their model relation ordering.

## Homepage CMS Flow

The homepage keeps a fixed frontend layout while receiving prepared backend data.

Flow:

1. `HomeController@index` loads active registered `home.*` Page Sections with media in one query.
2. `HomepageSectionData` normalizes PageSection copy, CTA fields, `extra_data`, and registered fallback defaults.
3. `HomepageContent` prepares supported homepage content arrays, Destination cards, and FAQ item fallbacks.
4. Blade section partials render the prepared values and keep layout-specific markup in code.

Homepage data sources:

- Page Sections: section copy, CTA fields, section media, controlled `extra_data`.
- Products: published Product module records and reusable product cards.
- Categories: active Category module records for search/filter controls and product tabs.
- Destinations: active Destination module records for search/filter controls and homepage destination cards.
- FAQs: active FAQ module records with PageSection fallback items when empty.
- Reviews/Testimonials: static fallback source until a dedicated module is approved.
- Global Settings: site assets, default media, business/contact data, footer settings, and WhatsApp CTA fallback.

Rules:

- Blade must not query the database.
- Missing or inactive homepage Page Sections fall back to registered defaults.
- No page-builder behavior is introduced by Page Sections.
- Product Listing, Product Detail, header, and footer layout flows remain separate from homepage renderer consolidation.
