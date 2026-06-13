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
