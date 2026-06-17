# Performance Checklist

Last updated: 2026-06-13

## Global Settings Cache

- [x] Public global settings are loaded through `App\Services\GlobalSettingsService`.
- [x] Public site assets are loaded through `App\Services\GlobalSettingsService`.
- [x] Global settings cache key is versioned: `global_settings.public.v1`.
- [x] Global assets cache key is versioned: `global_assets.public.v1`.
- [x] Cache TTL is 30 minutes.
- [x] Cache invalidation is scoped and does not use `Cache::flush()`.
- [x] `SiteSetting` save/delete clears only the public settings cache.
- [x] `SiteAsset` save/delete clears only the public assets cache.
- [x] Cached settings/assets payloads are stored as primitive arrays, not serialized Laravel/Eloquent objects.
- [x] Invalid or legacy cache payloads are discarded and rebuilt without breaking dashboard rendering.
- [x] Admin settings edit screen can continue reading fresh database rows.
- [x] Blade templates remain query-free for global settings/assets.

## Still Pending

- Product query indexes remain deferred to a later DB step.
- Query-count coverage is focused on the service layer, not total page query counts.
- Global settings cache should be revisited if future private credential settings are added.
- If cache key format changes again later, bump the cache key version or keep a compatibility normalizer.
