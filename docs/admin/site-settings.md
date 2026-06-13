# Site Settings

Last updated: 2026-06-13

## Current Behavior

Global site settings and site assets are managed from the admin Global Assets/Site Settings screen. Public registration remains disabled, and access to the admin area remains controlled by the existing admin authorization rules.

Public-facing settings are read through `App\Services\GlobalSettingsService` and shared to frontend/admin layouts by `App\Providers\AppServiceProvider`.

## Cached Public Data

The cache layer is limited to public site display data:

- Brand colors
- Business identity
- Contact information
- Social media links
- Header navigation
- Footer settings
- SEO defaults
- Tracking integration display settings
- Booking CTA settings
- Default media settings
- Structured data settings
- Active site assets

Future private credentials, API secrets, or tokens must not be added to the public global settings cache.

## Admin Updates

When a `SiteSetting` record is saved or deleted, the public settings cache is forgotten. When a `SiteAsset` record is saved or deleted, the public assets cache is forgotten. This keeps the next frontend request aligned with admin changes without clearing unrelated application cache.

## Rollback

To rollback DB-06, revert the GlobalSettingsService/provider/model/controller changes and run:

```bash
php artisan cache:clear
```
