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

## Cache Keys and TTL

Actual public cache keys:

- `global_settings.public.v1`
- `global_assets.public.v1`

TTL:

- 30 minutes

The cache stores primitive arrays and hydrates runtime collections/models after cache reads. This avoids stale serialized object payloads causing typed property errors.

## Admin Updates

When a `SiteSetting` record is saved or deleted, the public settings cache is forgotten. When a `SiteAsset` record is saved or deleted, the public assets cache is forgotten. This keeps the next frontend request aligned with admin changes without clearing unrelated application cache.

Admin edit screens continue to read fresh database values for form display.

## Verification

DB-06 verification:

- `php artisan test tests\Feature\Performance\GlobalSettingsCacheTest.php` passed after the regression fix: 8 tests, 23 assertions.
- Full `php artisan test` passed after the regression fix: 136 tests, 603 assertions.

## Rollback

To rollback DB-06, revert the GlobalSettingsService/provider/model/controller changes and run:

```bash
php artisan cache:clear
```
