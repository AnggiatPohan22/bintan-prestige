# Documentation Map

STEP DOC-02 maps old documentation to the new canonical documentation structure without deleting, overwriting, or moving existing docs.

Date: 2026-06-11
Scope: `docs/*`

## Rules Applied
- No Laravel code was audited or changed.
- Existing docs remain in their current locations.
- Old docs are mapped to canonical destinations before any future move.
- Outdated or historical docs are marked, not deleted.
- Duplicate candidates are recorded for future consolidation.

## Canonical Structure
- `docs/architecture/`
- `docs/database/`
- `docs/modules/`
- `docs/frontend/`
- `docs/admin/`
- `docs/security/`
- `docs/performance/`
- `docs/seo/`
- `docs/qa/`
- `docs/changelog/`
- `docs/_legacy/`

## Files Scanned
- `docs/README.md`
- `docs/_legacy/README.md`
- `docs/admin/README.md`
- `docs/architecture/README.md`
- `docs/changelog/README.md`
- `docs/database/README.md`
- `docs/frontend/README.md`
- `docs/modules/README.md`
- `docs/performance/README.md`
- `docs/qa/README.md`
- `docs/security/README.md`
- `docs/seo/README.md`
- `docs/global-booking-cta-settings.md`
- `docs/global-brand-colors-settings.md`
- `docs/global-business-identity-settings.md`
- `docs/global-contact-information-settings.md`
- `docs/global-default-media-placeholder-assets.md`
- `docs/global-favicon-settings.md`
- `docs/global-footer-settings.md`
- `docs/global-navigation-settings.md`
- `docs/global-seo-default-settings.md`
- `docs/global-site-logo-settings.md`
- `docs/global-social-media-links-settings.md`
- `docs/global-social-share-image-settings.md`
- `docs/global-structured-data-business-schema.md`
- `docs/global-tracking-integrations-settings.md`
- `docs/Admin_Dashboard/STEP_11_Index_UI_Upgrade/README.md`
- `docs/Admin_Dashboard/STEP_11_Index_UI_Upgrade/11A-categories-index-ui-upgrade.md`
- `docs/Admin_Dashboard/STEP_11_Index_UI_Upgrade/11B-destinations-index-ui-upgrade.md`
- `docs/Admin_Dashboard/STEP_11_Index_UI_Upgrade/11C-faq-index-ui-upgrade.md`
- `docs/Admin_Dashboard/STEP_11_Index_UI_Upgrade/11D-page-sections-index-ui-upgrade.md`
- `docs/Admin_Dashboard/STEP_11_Index_UI_Upgrade/11E-products-index-ui-upgrade.md`
- `docs/Page_Sections/page-sections-image-sync-audit.md`
- `docs/Page_Sections/page-sections-page-management-step-1.md`
- `docs/Page_Sections/page-sections-product-page-sync.md`
- `docs/Product/Product_Page_UI/product-page-ui-refresh.md`
- `docs/Product/Product_Detail_UI/product-detail-ui-refresh.md`
- `docs/ai-ui-reference/admin-dashboard-inspiration/README.md`
- `docs/ai-ui-reference/design-systems/README.md`
- `docs/ai-ui-reference/frontend-design/README.md`
- `docs/ai-ui-reference/landing-page-inspiration/README.md`

## Active Docs
These documents are still relevant and should be referenced until canonical docs are written.

| Existing doc | Current purpose | Canonical mapping |
| --- | --- | --- |
| `docs/README.md` | Main docs index | `docs/README.md` |
| `docs/DOCS-MAP.md` | Documentation mapping index | `docs/DOCS-MAP.md` |
| `docs/global-booking-cta-settings.md` | Booking CTA global settings implementation notes | `docs/modules/global-assets.md`, `docs/admin/global-assets.md` |
| `docs/global-brand-colors-settings.md` | Global brand color settings notes | `docs/modules/global-assets.md`, `docs/frontend/design-system.md` |
| `docs/global-business-identity-settings.md` | Business identity settings notes | `docs/modules/global-assets.md`, `docs/seo/business-identity.md` |
| `docs/global-contact-information-settings.md` | Contact information settings notes | `docs/modules/global-assets.md`, `docs/admin/global-assets.md` |
| `docs/global-default-media-placeholder-assets.md` | Default media placeholder asset notes | `docs/modules/global-assets.md`, `docs/frontend/media-assets.md` |
| `docs/global-favicon-settings.md` | Favicon settings notes | `docs/modules/global-assets.md`, `docs/frontend/site-assets.md` |
| `docs/global-footer-settings.md` | Footer settings notes | `docs/modules/global-assets.md`, `docs/frontend/footer.md` |
| `docs/global-navigation-settings.md` | Header navigation settings notes | `docs/modules/global-assets.md`, `docs/frontend/navigation.md` |
| `docs/global-seo-default-settings.md` | SEO default settings notes | `docs/seo/defaults.md`, `docs/modules/global-assets.md` |
| `docs/global-site-logo-settings.md` | Site logo settings notes | `docs/modules/global-assets.md`, `docs/frontend/site-assets.md` |
| `docs/global-social-media-links-settings.md` | Social media link settings notes | `docs/modules/global-assets.md`, `docs/frontend/footer.md` |
| `docs/global-social-share-image-settings.md` | Default social share image notes | `docs/seo/social-share.md`, `docs/modules/global-assets.md` |
| `docs/global-structured-data-business-schema.md` | Structured data and JSON-LD notes | `docs/seo/structured-data.md`, `docs/modules/global-assets.md` |
| `docs/global-tracking-integrations-settings.md` | Tracking integrations notes | `docs/modules/global-assets.md`, `docs/performance/tracking.md`, `docs/security/tracking-safety.md` |
| `docs/Page_Sections/page-sections-image-sync-audit.md` | Page Sections image sync audit and implementation notes | `docs/modules/page-sections.md`, `docs/frontend/page-sections.md` |
| `docs/Page_Sections/page-sections-page-management-step-1.md` | Page Sections page management step notes | `docs/modules/page-sections.md`, `docs/admin/page-sections.md` |
| `docs/Page_Sections/page-sections-product-page-sync.md` | Product page and Page Sections sync notes | `docs/modules/page-sections.md`, `docs/modules/products.md` |
| `docs/Product/Product_Page_UI/product-page-ui-refresh.md` | Product listing UI refresh notes | `docs/frontend/product-pages.md`, `docs/modules/products.md` |
| `docs/Product/Product_Detail_UI/product-detail-ui-refresh.md` | Product detail UI refresh notes | `docs/frontend/product-pages.md`, `docs/modules/products.md` |
| `docs/Admin_Dashboard/STEP_11_Index_UI_Upgrade/README.md` | Admin index UI upgrade overview | `docs/admin/index-ui-upgrades.md` |
| `docs/Admin_Dashboard/STEP_11_Index_UI_Upgrade/11A-categories-index-ui-upgrade.md` | Categories index UI notes | `docs/admin/index-ui-upgrades.md`, `docs/modules/categories.md` |
| `docs/Admin_Dashboard/STEP_11_Index_UI_Upgrade/11B-destinations-index-ui-upgrade.md` | Destinations index UI notes | `docs/admin/index-ui-upgrades.md`, `docs/modules/destinations.md` |
| `docs/Admin_Dashboard/STEP_11_Index_UI_Upgrade/11C-faq-index-ui-upgrade.md` | FAQ index UI notes | `docs/admin/index-ui-upgrades.md`, `docs/modules/faqs.md` |
| `docs/Admin_Dashboard/STEP_11_Index_UI_Upgrade/11D-page-sections-index-ui-upgrade.md` | Page Sections index UI notes | `docs/admin/index-ui-upgrades.md`, `docs/modules/page-sections.md` |
| `docs/Admin_Dashboard/STEP_11_Index_UI_Upgrade/11E-products-index-ui-upgrade.md` | Products index UI notes | `docs/admin/index-ui-upgrades.md`, `docs/modules/products.md` |

## Legacy Docs
No existing file has been moved to `docs/_legacy/`.

Historical reference candidates:

| Existing doc | Reason | Suggested future location |
| --- | --- | --- |
| `docs/ai-ui-reference/admin-dashboard-inspiration/README.md` | Inspiration-only notes; may conflict with current AI foundation and admin style rules | `docs/_legacy/ui-reference/admin-dashboard-inspiration.md` |
| `docs/ai-ui-reference/design-systems/README.md` | Inspiration-only notes, not implementation source of truth | `docs/_legacy/ui-reference/design-systems.md` |
| `docs/ai-ui-reference/frontend-design/README.md` | Short design notes now superseded by AI foundation frontend/UI skills | `docs/_legacy/ui-reference/frontend-design.md` |
| `docs/ai-ui-reference/landing-page-inspiration/README.md` | Inspiration-only notes, not canonical frontend documentation | `docs/_legacy/ui-reference/landing-page-inspiration.md` |

## Duplicate Docs
No exact duplicate files were found. These are duplicate candidates or overlapping documentation clusters:

| Cluster | Docs | Canonical target |
| --- | --- | --- |
| Global Assets settings | All root `global-*.md` docs | `docs/modules/global-assets.md`, with admin details in `docs/admin/global-assets.md` |
| SEO and schema | `global-seo-default-settings.md`, `global-social-share-image-settings.md`, `global-structured-data-business-schema.md` | `docs/seo/defaults.md`, `docs/seo/social-share.md`, `docs/seo/structured-data.md` |
| CTA and conversion tracking | `global-booking-cta-settings.md`, `global-tracking-integrations-settings.md`, `global-contact-information-settings.md` | `docs/modules/global-assets.md`, `docs/seo/conversion-tracking.md` |
| Brand identity and site assets | `global-brand-colors-settings.md`, `global-business-identity-settings.md`, `global-site-logo-settings.md`, `global-favicon-settings.md` | `docs/frontend/design-system.md`, `docs/modules/global-assets.md` |
| Page Sections | `page-sections-page-management-step-1.md`, `page-sections-product-page-sync.md`, `page-sections-image-sync-audit.md` | `docs/modules/page-sections.md` |
| Product UI and Page Sections mapping | Product UI docs plus `page-sections-product-page-sync.md` | `docs/frontend/product-pages.md`, `docs/modules/products.md` |
| Admin index UI | STEP 11 README and 11A-11E sub-step docs | `docs/admin/index-ui-upgrades.md` |

## Outdated Docs
These are not declared obsolete, but they should be treated carefully until reviewed.

| Existing doc | Outdated risk | Action |
| --- | --- | --- |
| `docs/ai-ui-reference/admin-dashboard-inspiration/README.md` | Mentions style directions that may not match current admin dashboard rules | Mark as historical/inspiration-only |
| `docs/ai-ui-reference/frontend-design/README.md` | Short older UI notes now covered by AI foundation and frontend skills | Mark as historical/inspiration-only |
| Branch-scoped implementation docs | Some may describe branch-specific state instead of current canonical system state | Keep active as references until canonical docs validate current behavior |

## Need Review Docs
These docs are useful but need a future canonical pass.

| Existing doc or area | Review reason | Suggested target |
| --- | --- | --- |
| `docs/global-*.md` | Root-level feature reports should become a consolidated Global Assets module doc | `docs/modules/global-assets.md` |
| `docs/Page_Sections/*.md` | Multiple docs describe related Page Sections behavior | `docs/modules/page-sections.md` |
| `docs/Product/*/*.md` | Product UI docs should be linked with product module behavior | `docs/modules/products.md`, `docs/frontend/product-pages.md` |
| `docs/Admin_Dashboard/STEP_11_Index_UI_Upgrade/*.md` | STEP docs should become a canonical admin UI index doc | `docs/admin/index-ui-upgrades.md` |
| `docs/ai-ui-reference/*` | Inspiration docs may conflict with newer rules | `docs/_legacy/ui-reference/` after approval |
| `docs/ai-ui-reference/agent-skills/` | Empty folder | Decide whether to remove after approval or document purpose |

## Missing Docs
These canonical docs do not exist yet and should be created in future documentation steps.

### Architecture
- `docs/architecture/system-overview.md`
- `docs/architecture/mvc-flow.md`
- `docs/architecture/cms-flow.md`
- `docs/architecture/frontend-backend-sync.md`
- `docs/architecture/deployment-flow.md`

### Database
- `docs/database/schema-overview.md`
- `docs/database/relationships.md`
- `docs/database/table-map.md`

### Modules
- `docs/modules/global-assets.md`
- `docs/modules/products.md`
- `docs/modules/categories.md`
- `docs/modules/destinations.md`
- `docs/modules/page-sections.md`
- `docs/modules/faqs.md`
- `docs/modules/seo.md`

### Frontend
- `docs/frontend/design-system.md`
- `docs/frontend/components.md`
- `docs/frontend/pages.md`
- `docs/frontend/product-pages.md`
- `docs/frontend/media-assets.md`
- `docs/frontend/navigation.md`
- `docs/frontend/footer.md`

### Admin
- `docs/admin/dashboard.md`
- `docs/admin/global-assets.md`
- `docs/admin/product-management.md`
- `docs/admin/page-section-management.md`
- `docs/admin/index-ui-upgrades.md`

### Security
- `docs/security/checklist.md`
- `docs/security/audit-log.md`
- `docs/security/upload-safety.md`
- `docs/security/tracking-safety.md`

### Performance
- `docs/performance/checklist.md`
- `docs/performance/audit-report.md`
- `docs/performance/image-optimization.md`
- `docs/performance/tracking.md`

### SEO
- `docs/seo/defaults.md`
- `docs/seo/social-share.md`
- `docs/seo/structured-data.md`
- `docs/seo/ai-discovery.md`
- `docs/seo/conversion-tracking.md`

### QA
- `docs/qa/launch-checklist.md`
- `docs/qa/final-release-report.md`
- `docs/qa/regression-checklist.md`

### Changelog
- `docs/changelog/CHANGELOG.md`

## Mapping By Canonical Folder

### `docs/architecture/`
- Source now: none canonical yet.
- Map from old docs:
  - `docs/Page_Sections/page-sections-page-management-step-1.md`
  - `docs/Page_Sections/page-sections-product-page-sync.md`
  - `docs/global-structured-data-business-schema.md`
- Missing: system overview, MVC flow, CMS flow, frontend/backend sync.

### `docs/database/`
- Source now: database notes are embedded inside feature docs.
- Map from old docs:
  - `docs/global-*.md`
  - `docs/Page_Sections/*.md`
  - `docs/Product/*/*.md`
- Missing: schema overview, relationships, table map.

### `docs/modules/`
- Source now: module details are spread across root `global-*`, Page Sections, Product, and Admin Dashboard docs.
- Map from old docs:
  - Global Assets: all root `global-*.md`
  - Page Sections: `docs/Page_Sections/*.md`
  - Products: `docs/Product/*/*.md`, `11E-products-index-ui-upgrade.md`
  - Categories: `11A-categories-index-ui-upgrade.md`
  - Destinations: `11B-destinations-index-ui-upgrade.md`
  - FAQs: `11C-faq-index-ui-upgrade.md`

### `docs/frontend/`
- Source now: product UI docs, frontend-related global asset docs, Page Sections image sync docs.
- Map from old docs:
  - `docs/Product/Product_Page_UI/product-page-ui-refresh.md`
  - `docs/Product/Product_Detail_UI/product-detail-ui-refresh.md`
  - `docs/Page_Sections/page-sections-image-sync-audit.md`
  - `docs/global-navigation-settings.md`
  - `docs/global-footer-settings.md`
  - `docs/global-brand-colors-settings.md`
  - `docs/global-default-media-placeholder-assets.md`
  - `docs/global-site-logo-settings.md`
  - `docs/global-favicon-settings.md`

### `docs/admin/`
- Source now: STEP 11 admin docs and admin-side global setting docs.
- Map from old docs:
  - `docs/Admin_Dashboard/STEP_11_Index_UI_Upgrade/*.md`
  - all root `global-*.md` docs with admin setting forms/routes
  - `docs/Page_Sections/page-sections-page-management-step-1.md`

### `docs/security/`
- Source now: security notes are embedded in feature docs.
- Map from old docs:
  - `docs/global-tracking-integrations-settings.md`
  - `docs/global-default-media-placeholder-assets.md`
  - upload-related notes in `docs/Page_Sections/page-sections-image-sync-audit.md`
- Missing: checklist, audit log, upload safety.

### `docs/performance/`
- Source now: performance notes are embedded in feature docs.
- Map from old docs:
  - `docs/global-default-media-placeholder-assets.md`
  - `docs/global-tracking-integrations-settings.md`
  - product UI docs with build/test notes
- Missing: performance checklist, audit report, image optimization guidance.

### `docs/seo/`
- Source now: SEO-related global setting docs.
- Map from old docs:
  - `docs/global-seo-default-settings.md`
  - `docs/global-social-share-image-settings.md`
  - `docs/global-structured-data-business-schema.md`
  - `docs/global-business-identity-settings.md`
  - `docs/global-social-media-links-settings.md`

### `docs/qa/`
- Source now: test results are embedded in feature docs.
- Map from old docs:
  - Test sections in root `global-*.md`
  - Test sections in `docs/Page_Sections/*.md`
  - Test sections in `docs/Product/*/*.md`
  - STEP 11 admin risk/audit notes
- Missing: launch checklist, final release report, regression checklist.

### `docs/changelog/`
- Source now: branch names and implementation notes are embedded in individual feature docs.
- Map from old docs:
  - Branch references in all branch-scoped docs.
- Missing: canonical `CHANGELOG.md`.

## Report

### Files Changed
- `docs/README.md`

### Files Created
- `docs/DOCS-MAP.md`

### Files Deleted
None.

### Files Moved
None.

### Database Impact
None.

### Route Impact
None.

### Frontend Impact
No runtime frontend files changed.

### Backend Impact
No Laravel backend files changed.

### Security Impact
Documentation-only.

### Testing Performed
- Scanned all files under `docs`.
- Checked for empty directories under `docs`.
- Confirmed `docs/DOCS-MAP.md` did not exist before this step.

## Recommended Next Step
Create `docs/modules/global-assets.md` as the first canonical module doc, cross-linking all root `global-*.md` files as historical implementation references.
