# STEP SECURITY-06A - Granular Authorization Policy/Gate Audit

Date: 2026-06-12
Status: Audit only. No code changes.

## Executive Summary

The admin surface is currently protected by a strong baseline gate: every route in `routes/admin.php` is grouped behind `web`, `auth`, and `admin`, and `AdminMiddleware` checks `User::isAdmin()`.

For the current single-admin phase, `is_admin` is enough as the outer access-control layer. It prevents guests and non-admin authenticated users from entering the CMS. However, the project does not yet have granular Laravel policies or gates for individual admin actions. This means all admin users currently have equal ability to view, create, update, delete, upload, publish, manage tracking scripts, and change global settings.

The recommended next implementation should be minimal and Laravel-native: keep `AdminMiddleware` as the outer gate, add policies/gates that still resolve to `is_admin` for now, and place explicit authorization calls on high-risk controller actions. Do not introduce a role/permission package yet.

## Current Authorization Status

Current baseline:

- Public registration is disabled.
- Admin access is protected by `auth` and `admin`.
- `admin` middleware resolves through `User::isAdmin()`.
- `users.is_admin` exists and is cast as boolean in `User`.
- First admin provisioning is CLI-only through `php artisan admin:provision-first`.
- All 75 admin routes shown by `php artisan route:list --path=admin -v` include `web`, `auth`, and `admin`.

Missing granular layer:

- No `app/Policies` directory exists.
- No admin controller uses `$this->authorize(...)`.
- No admin controller uses `Gate::authorize(...)`.
- Admin Form Requests currently return `authorize(): true`.
- No per-action separation exists between read, create, update, delete, restore, media, tracking, SEO, and settings capabilities.

Conclusion:

- `is_admin` is sufficient for SECURITY-06A current baseline.
- Policies/gates are needed before multiple admin levels, staff accounts, or high-risk settings delegation.

## Admin Route Map

All routes below are currently protected by `web`, `auth`, and `admin`.

### Dashboard

- `GET admin/dashboard` -> `DashboardController@index`

### Products

- `GET admin/products` -> `ProductController@index`
- `GET admin/products/create` -> `ProductController@create`
- `POST admin/products` -> `ProductController@store`
- `GET admin/products/{product}/edit` -> `ProductController@edit`
- `PUT|PATCH admin/products/{product}` -> `ProductController@update`
- `DELETE admin/products/{product}` -> `ProductController@destroy`
- `PATCH admin/products/{product}/toggle-featured` -> `ProductController@toggleFeatured`
- `PATCH admin/products/{product}/toggle-status` -> `ProductController@toggleStatus`
- `PUT admin/products/{product}/search-booking` -> `ProductController@updateSearchBooking`
- `PATCH admin/product-images/{image}/thumbnail` -> `ProductController@setThumbnailFromImage`
- `DELETE admin/product-images/{image}` -> `ProductController@destroyImage`
- `DELETE admin/products/{product}/thumbnail` -> `ProductController@destroyThumbnail`

### Product Submodules

- Product highlights: store, update, destroy.
- Product features: store, update, destroy.
- Product FAQs: store, update, destroy.
- Product itineraries: store, update, destroy.
- Product notes: store, update, destroy.

### Categories

- `GET admin/categories`
- `GET admin/categories/create`
- `POST admin/categories`
- `GET admin/categories/{category}/edit`
- `PUT|PATCH admin/categories/{category}`
- `DELETE admin/categories/{category}`
- `PATCH admin/categories/{category}/restore`
- `DELETE admin/categories/{category}/force-delete`

### Destinations

- `GET admin/destinations`
- `GET admin/destinations/create`
- `POST admin/destinations`
- `GET admin/destinations/{destination}/edit`
- `PUT|PATCH admin/destinations/{destination}`
- `DELETE admin/destinations/{destination}`
- `PATCH admin/destinations/{destination}/restore`
- `DELETE admin/destinations/{destination}/force-delete`

### FAQs

- `GET admin/faqs`
- `GET admin/faqs/create`
- `POST admin/faqs`
- `GET admin/faqs/{faq}/edit`
- `PUT|PATCH admin/faqs/{faq}`
- `DELETE admin/faqs/{faq}`

### Page Sections

- `GET admin/page-sections`
- `GET admin/page-sections/sections`
- `GET admin/page-sections/{pageSection}/edit`
- `PUT admin/page-sections/{pageSection}`
- `DELETE admin/page-section-media/{media}`

### Global Assets and Settings

- Site logo update/delete.
- Favicon update/delete.
- Brand colors update.
- Social share image update/delete.
- Business identity update.
- Contact information update.
- Social media links update.
- Header navigation update.
- Footer settings update.
- SEO default update and OG image delete.
- Tracking integrations update.
- Booking CTA update.
- Default media update/delete.
- Structured data update.

## Controller Action Map

### DashboardController

| Action | Current protection | Future ability |
| --- | --- | --- |
| `index` | `auth` + `admin` | `viewAdminDashboard` |

### ProductController

| Action | Current protection | Future ability |
| --- | --- | --- |
| `index` | `auth` + `admin` | `viewAny` |
| `create` | `auth` + `admin` | `create` |
| `store` | `auth` + `admin`, FormRequest authorize true | `create` |
| `edit` | `auth` + `admin` | `update` or `view` |
| `update` | `auth` + `admin`, FormRequest authorize true | `update` |
| `destroy` | `auth` + `admin` | `delete` |
| `toggleFeatured` | `auth` + `admin` | `manageFeatured` |
| `toggleStatus` | `auth` + `admin` | `publish` or `manageStatus` |
| `updateSearchBooking` | `auth` + `admin` | `manageSearchBooking` |
| `destroyImage` | `auth` + `admin` | `manageImages` |
| `setThumbnailFromImage` | `auth` + `admin` | `manageImages` |
| `destroyThumbnail` | `auth` + `admin` | `manageImages` |

Product prices are managed through product store/update via `ProductService` and `ProductPriceService`, so the future policy should include `managePrices` under `ProductPolicy`.

### CategoryController

| Action | Current protection | Future ability |
| --- | --- | --- |
| `index` | `auth` + `admin` | `viewAny` |
| `create` / `store` | `auth` + `admin`, FormRequest authorize true | `create` |
| `edit` / `update` | `auth` + `admin`, FormRequest authorize true | `update` |
| `destroy` | `auth` + `admin` | `delete` |
| `restore` | `auth` + `admin` | `restore` |
| `forceDelete` | `auth` + `admin` | `forceDelete` |

### DestinationController

| Action | Current protection | Future ability |
| --- | --- | --- |
| `index` | `auth` + `admin` | `viewAny` |
| `create` / `store` | `auth` + `admin`, FormRequest authorize true | `create` |
| `edit` / `update` | `auth` + `admin`, FormRequest authorize true | `update` |
| `destroy` | `auth` + `admin` | `delete` |
| `restore` | `auth` + `admin` | `restore` |
| `forceDelete` | `auth` + `admin` | `forceDelete` |

### FaqController

| Action | Current protection | Future ability |
| --- | --- | --- |
| `index` | `auth` + `admin` | `viewAny` |
| `create` / `store` | `auth` + `admin` | `create` |
| `edit` / `update` | `auth` + `admin` | `update` |
| `destroy` | `auth` + `admin` | `delete` |

### PageSectionController

| Action | Current protection | Future ability |
| --- | --- | --- |
| `index` | `auth` + `admin` | `viewAny` |
| `sections` | `auth` + `admin` | `viewAny` |
| `edit` | `auth` + `admin` | `view` or `update` |
| `update` | `auth` + `admin` | `update` |
| `destroyMedia` | `auth` + `admin` | `deleteMedia` |

### Product Submodule Controllers

| Module | Actions | Current protection | Future ability |
| --- | --- | --- | --- |
| Product highlights | store, update, destroy | `auth` + `admin` | `manageHighlights` |
| Product features | store, update, destroy | `auth` + `admin` | `manageFeatures` |
| Product FAQs | store, update, destroy | `auth` + `admin` | `manageFaqs` |
| Product itineraries | store, update, destroy | `auth` + `admin` | `manageItineraries` |
| Product notes | store, update, destroy | `auth` + `admin` | `manageNotes` |

These can initially be covered by `ProductPolicy` because they all mutate product-owned content.

### SiteSettingController

| Setting area | Actions | Current protection | Future ability |
| --- | --- | --- | --- |
| Global asset read | edit | `auth` + `admin` | `viewGlobalAssets` |
| Logos | update, destroyLogo | `auth` + `admin` | `manageBrandAssets` |
| Favicon | updateFavicon, destroyFavicon | `auth` + `admin` | `manageBrandAssets` |
| Brand colors | updateBrandColors | `auth` + `admin` | `manageBrandSettings` |
| Social share image | updateSocialShareImage, destroySocialShareImage | `auth` + `admin` | `manageSeoDefaults` |
| Business/contact/social/nav/footer | update* | `auth` + `admin` | `manageSiteSettings` |
| SEO defaults | updateSeoDefaultSettings, destroySeoDefaultOgImage | `auth` + `admin` | `manageSeoDefaults` |
| Tracking integrations | updateTrackingIntegrations | `auth` + `admin` | `manageTrackingIntegrations` |
| Booking CTA | updateBookingCtaSettings | `auth` + `admin` | `manageBookingSettings` |
| Default media | updateDefaultMediaAssets, destroyDefaultMediaAsset | `auth` + `admin` | `manageDefaultMedia` |
| Structured data | updateStructuredDataSettings | `auth` + `admin` | `manageStructuredData` |

## Destructive Action Risk

### Critical

- `SiteSettingController@updateTrackingIntegrations`
  - Can affect frontend tracking scripts, privacy posture, analytics, and possible script injection risk.
- `SiteSettingController@updateStructuredDataSettings`
  - Can alter site-wide schema output and AI/SEO discovery signals.
- `CategoryController@forceDelete`
  - Permanently deletes archived categories after product-count check.
- `DestinationController@forceDelete`
  - Permanently deletes archived destinations and deletes associated image assets.

### High

- `ProductController@destroy`
  - Deletes product from CMS.
- `ProductController@toggleStatus`
  - Publishes/unpublishes public-facing product content.
- `ProductController@destroyImage`
  - Deletes product gallery assets and can alter thumbnails.
- `ProductController@destroyThumbnail`
  - Removes or replaces public-facing product thumbnails.
- `PageSectionController@update`
  - Updates public page-section content and media.
- `PageSectionController@destroyMedia`
  - Deletes page-section media used by public frontend.
- `SiteSettingController@destroyLogo`, `destroyFavicon`, `destroySocialShareImage`, `destroySeoDefaultOgImage`, `destroyDefaultMediaAsset`
  - Deletes global public-facing assets.

### Medium

- Product submodule deletes for highlights, features, FAQs, itineraries, and notes.
- FAQ delete.
- Category/destination soft delete and restore.
- Product price updates through product store/update.
- Business identity, contact, social links, navigation, footer, booking CTA, and SEO default updates.

### Lower

- Read-only list/edit screens such as dashboard, index, create form, edit form.
- Still should use `view` or `viewAny` policies eventually for consistency.

## Recommended Gate/Policy Map

### Phase 1 Minimal Map

Keep all abilities backed by `User::isAdmin()` for now.

- `viewAdminDashboard` gate.
- `ProductPolicy`
  - `viewAny`
  - `create`
  - `update`
  - `delete`
  - `publish`
  - `manageFeatured`
  - `manageImages`
  - `managePrices`
  - `manageSearchBooking`
  - `manageHighlights`
  - `manageFeatures`
  - `manageFaqs`
  - `manageItineraries`
  - `manageNotes`
- `CategoryPolicy`
  - `viewAny`
  - `create`
  - `update`
  - `delete`
  - `restore`
  - `forceDelete`
- `DestinationPolicy`
  - `viewAny`
  - `create`
  - `update`
  - `delete`
  - `restore`
  - `forceDelete`
- `FaqPolicy`
  - `viewAny`
  - `create`
  - `update`
  - `delete`
- `PageSectionPolicy`
  - `viewAny`
  - `view`
  - `update`
  - `manageMedia`
  - `deleteMedia`
- Site settings gates
  - `viewGlobalAssets`
  - `manageBrandAssets`
  - `manageBrandSettings`
  - `manageSiteSettings`
  - `manageSeoDefaults`
  - `manageTrackingIntegrations`
  - `manageBookingSettings`
  - `manageDefaultMedia`
  - `manageStructuredData`

### Why Policies/Gates Still Matter If All Return is_admin

- They document intended access boundaries.
- They make future role/capability splits safer.
- They protect direct URL mutation routes with explicit action-level checks.
- They make tests more precise.
- They reduce risk when future staff/admin levels are introduced.

## Urgency Level

Overall urgency: Medium-High.

Reasoning:

- Current `is_admin` gate closes the critical public-registration and ordinary-user admin access risk.
- There is no evidence of public admin route bypass after SECURITY-03.
- The remaining risk is concentrated in high-impact actions once an account is admin.
- If only one trusted admin exists, implementation can be staged.
- If multiple admin users or staff accounts are planned, granular policies should be implemented before adding them.

Action urgency:

- Critical next: tracking integrations, global settings, force delete actions, product publish/delete, page-section media.
- High next: product image management, product prices, SEO defaults, structured data.
- Medium next: product submodules, FAQs, categories/destinations soft delete/restore.
- Lower next: read-only screens.

## Implementation Priority

### SECURITY-06B Priority 1

Implement gates/policies for highest-risk actions:

1. `viewAdminDashboard`
2. `manageTrackingIntegrations`
3. `manageStructuredData`
4. `manageSeoDefaults`
5. `ProductPolicy@delete`
6. `ProductPolicy@publish`
7. `ProductPolicy@manageImages`
8. `CategoryPolicy@forceDelete`
9. `DestinationPolicy@forceDelete`
10. `PageSectionPolicy@update`
11. `PageSectionPolicy@deleteMedia`

### SECURITY-06C Priority 2

Expand coverage:

1. Product create/update.
2. Product prices and search booking.
3. Product submodules.
4. Category and destination create/update/delete/restore.
5. FAQ create/update/delete.
6. Brand assets, default media, favicon, logos, social image.

### SECURITY-06D Priority 3

Test hardening:

1. Non-admin direct URL tests for POST, PUT, PATCH, DELETE admin routes.
2. Admin allow tests for representative actions.
3. Regression tests for public frontend unaffected.
4. Tests confirming public registration remains disabled.

## Files Inspected

- `AGENTS.md`
- `ai/skills/security-skill.md`
- `routes/admin.php`
- `bootstrap/app.php`
- `app/Http/Middleware/AdminMiddleware.php`
- `app/Models/User.php`
- `app/Http/Controllers/Admin/DashboardController.php`
- `app/Http/Controllers/Admin/ProductController.php`
- `app/Http/Controllers/Admin/CategoryController.php`
- `app/Http/Controllers/Admin/DestinationController.php`
- `app/Http/Controllers/Admin/FaqController.php`
- `app/Http/Controllers/Admin/PageSectionController.php`
- `app/Http/Controllers/Admin/ProductHighlightController.php`
- `app/Http/Controllers/Admin/ProductFeatureController.php`
- `app/Http/Controllers/Admin/ProductFaqController.php`
- `app/Http/Controllers/Admin/ProductItineraryController.php`
- `app/Http/Controllers/Admin/ProductNoteController.php`
- `app/Http/Controllers/Admin/SiteSettingController.php`
- `app/Http/Requests/StoreProductRequest.php`
- `app/Http/Requests/UpdateProductRequest.php`
- `app/Http/Requests/StoreCategoryRequest.php`
- `app/Http/Requests/UpdateCategoryRequest.php`
- `app/Http/Requests/StoreDestinationRequest.php`
- `app/Http/Requests/UpdateDestinationRequest.php`
- `app/Services/ProductService.php`
- `app/Services/ProductPriceService.php`
- `app/Models`
- `app/Policies` path check
- `php artisan route:list --path=admin -v`

## Recommended Next Step

Proceed with STEP SECURITY-06B: Minimal Granular Authorization Implementation.

Recommended implementation boundaries:

- Keep `AdminMiddleware` as the outer gate.
- Add Laravel-native policies/gates only.
- Keep every initial policy decision as `return $user->isAdmin();`.
- Start with critical and high-risk actions.
- Do not add role/permission packages.
- Do not change route names.
- Do not change public frontend behavior.
- Add focused tests for non-admin direct URL access and admin allowed access.

Suggested files for implementation:

- `app/Policies/ProductPolicy.php`
- `app/Policies/CategoryPolicy.php`
- `app/Policies/DestinationPolicy.php`
- `app/Policies/FaqPolicy.php`
- `app/Policies/PageSectionPolicy.php`
- `app/Providers/AppServiceProvider.php` or a dedicated auth provider/gate registration path if the project adopts one.
- Selected admin controllers.
- Focused security tests under `tests/Feature/Security/`.

This SECURITY-06A step is complete as audit-only. No policy/gate was implemented.
