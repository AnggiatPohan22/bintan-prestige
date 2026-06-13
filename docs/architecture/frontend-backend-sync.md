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
