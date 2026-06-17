# Frontend Backend Sync

Last updated: 2026-06-17

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
- Product Detail display data is prepared by `ProductDetailDisplayState` before Blade renders: price, primary image/gallery/fallback, duration, meeting point, pickup, overview, highlights, feature groups, itinerary, notes, FAQs, add-ons, CTA copy, WhatsApp CTA state, breadcrumb state, metadata state, and optional section flags.
- Product Detail optional display state trims empty values before Blade renders: empty relation rows are omitted, partial itinerary/note/FAQ content can still render safely, impossible clock-shaped itinerary times are suppressed from the time badge, and sidebar duration/meeting rows render only when usable values exist.
- Product Detail WhatsApp CTAs are display-state guarded: Product number is preferred, global/contact number is the fallback, malformed short/non-numeric numbers are treated as unavailable, and CTA buttons are omitted when no usable number exists.
- Product Detail display state prepares both chat and booking WhatsApp URLs, labels, accessibility labels, messages, and helper notes; Blade renders normal anchors and does not construct `wa.me` URLs with JavaScript.
- Product Detail Blade renders prepared state only; it must not call Product query builders, relation methods, `DefaultMediaAssets`, or `BookingCtaSettings` directly.
- Product Detail metadata and social tags use the shared frontend layout metadata partials fed by `ProductDetailDisplayState`; no Product Detail Blade metadata system is duplicated.
- Product Detail JSON-LD is rendered through `StructuredDataBuilder` from prepared page context: Product schema uses actual Product content, Offer entries use only positive actual IDR/SGD prices, BreadcrumbList follows the visible breadcrumb state, and FAQPage includes only visible FAQs with non-empty answers.
- Product Detail schema excludes fake availability, ratings, reviews, SKU, GTIN, stock, zero-price offers, and currency conversion.
- Product Detail renders the prepared breadcrumb state as visible breadcrumb UI above the media-plus-summary area.
- Product Detail layout keeps one Product H1, a responsive primary media plus summary hierarchy, prepared summary facts, price/CTA placement, and optional content sections ordered as Description, Features, Itinerary, Notes, and FAQs.
- Product Detail gallery renders the backend-prepared primary media server-side and uses Alpine only as progressive enhancement for thumbnail, counter, and previous/next active-image updates.
- Product Detail gallery thumbnails are controls, not links, and render only for multi-image media state; fallback-only or single-image states keep the primary frame without gallery controls.
- Product Detail layout work does not add related products, gallery lightbox behavior, sticky CTA changes, sitemap changes, or robots.txt changes.
- Category/Destination display-state preparation lives in `CategoryDestinationDisplayState` for future clean entity surfaces. It prepares active entity lookup, public-visible Product query context, product count from the paginator total, filter/reset parameters, breadcrumbs, empty-state type, Category text-first media strategy, Destination media/fallback state, and metadata-ready values without adding routes, final SEO, schema, or Blade layout.
- Product Listing renders a prepared Category/Destination context layout for single valid Category or Destination filters, reusing the existing Product grid, Product Card, filters, sorting, pagination, and empty-state surfaces without adding clean entity routes or final SEO/schema behavior.

## Homepage CMS Flow

The homepage keeps a fixed frontend layout while receiving prepared backend data.

Flow:

1. `HomeController@index` loads active registered `home.*` Page Sections with media in one query.
2. `HomepageSectionData` normalizes PageSection copy, CTA fields, `extra_data`, and registered fallback defaults.
3. `HomepageContent` prepares supported homepage content arrays, Destination cards, and FAQ item fallbacks.
4. Blade section partials render the prepared values and keep layout-specific markup in code.

Homepage data sources:

- Page Sections: section copy, CTA fields, section media, controlled `extra_data`.
- Products: public-visible Product module records and reusable product cards.
- Categories: active Category module records for search/filter controls and product tabs.
- Destinations: active Destination module records for search/filter controls and homepage destination cards with public-visible Product counts.
- FAQs: active FAQ module records with PageSection fallback items when empty.
- Reviews/Testimonials: static fallback source until a dedicated module is approved.
- Global Settings: site assets, default media, business/contact data, footer settings, and WhatsApp CTA fallback.

Rules:

- Blade must not query the database.
- Missing or inactive homepage Page Sections fall back to registered defaults.
- No page-builder behavior is introduced by Page Sections.
- Product Listing, Product Detail, header, and footer layout flows remain separate from homepage renderer consolidation.
