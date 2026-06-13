# Frontend Backend Sync

Last updated: 2026-06-13

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

- Public Product listing/detail behavior is unchanged by DB-09.
- A future frontend/backend sync step should decide whether published Products under archived/inactive parents should remain visible or be hidden by a stricter public visibility scope.

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
