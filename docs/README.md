# Bintan Prestige CMS Documentation Index

This index was created during STEP DOC-01 to map existing documentation without deleting, overwriting, or moving old files.

For the detailed STEP DOC-02 mapping from old docs to the canonical structure, see `docs/DOCS-MAP.md`.

## Documentation Rules
- `AGENTS.md` remains the master rule.
- Existing docs are preserved in their current locations.
- Legacy candidates are listed here first before any move to `docs/_legacy/`.
- Future documentation should prefer the canonical folder structure below.
- If an old document is moved later, this index must keep a reference to its new location and reason.

## Canonical Folder Structure
- `docs/_legacy/` - old docs moved only after explicit approval.
- `docs/architecture/` - system architecture, MVC flow, CMS flow, deployment flow.
- `docs/database/` - schema overview, relationships, table map.
- `docs/modules/` - module-level docs for products, page sections, global assets, SEO, and related CMS areas.
- `docs/frontend/` - public frontend UI, components, pages, and design system.
- `docs/admin/` - admin dashboard and CMS operation docs.
- `docs/security/` - security checklist and audit notes.
- `docs/performance/` - performance checklist and audit notes.
- `docs/seo/` - SEO and AI discovery docs.
- `docs/qa/` - QA and release readiness reports.
- `docs/changelog/` - chronological change notes.

## Active Docs

### Global Assets / Admin Settings
These docs are active implementation references for global settings currently stored at the docs root:
- `global-booking-cta-settings.md`
- `global-brand-colors-settings.md`
- `global-business-identity-settings.md`
- `global-contact-information-settings.md`
- `global-default-media-placeholder-assets.md`
- `global-favicon-settings.md`
- `global-footer-settings.md`
- `global-navigation-settings.md`
- `global-seo-default-settings.md`
- `global-site-logo-settings.md`
- `global-social-media-links-settings.md`
- `global-social-share-image-settings.md`
- `global-structured-data-business-schema.md`
- `global-tracking-integrations-settings.md`

Recommended future canonical home: `docs/modules/global-assets.md` or `docs/admin/global-assets.md`.

### Page Sections
These docs are active references for Page Sections and frontend/admin sync:
- `Page_Sections/page-sections-page-management-step-1.md`
- `Page_Sections/page-sections-product-page-sync.md`
- `Page_Sections/page-sections-image-sync-audit.md`

Recommended future canonical home: `docs/modules/page-sections.md`.

### Product UI
These docs are active references for product listing/detail UI and Page Sections mapping:
- `Product/Product_Page_UI/product-page-ui-refresh.md`
- `Product/Product_Detail_UI/product-detail-ui-refresh.md`

Recommended future canonical home: `docs/frontend/product-pages.md` and `docs/modules/products.md`.

### Admin Dashboard
These docs are active references for STEP 11 admin index UI improvements:
- `Admin_Dashboard/STEP_11_Index_UI_Upgrade/README.md`
- `Admin_Dashboard/STEP_11_Index_UI_Upgrade/11A-categories-index-ui-upgrade.md`
- `Admin_Dashboard/STEP_11_Index_UI_Upgrade/11B-destinations-index-ui-upgrade.md`
- `Admin_Dashboard/STEP_11_Index_UI_Upgrade/11C-faq-index-ui-upgrade.md`
- `Admin_Dashboard/STEP_11_Index_UI_Upgrade/11D-page-sections-index-ui-upgrade.md`
- `Admin_Dashboard/STEP_11_Index_UI_Upgrade/11E-products-index-ui-upgrade.md`

Recommended future canonical home: `docs/admin/index-ui-upgrades.md`.

### UI References
These are supporting inspiration/reference docs, not implementation source of truth:
- `ai-ui-reference/admin-dashboard-inspiration/README.md`
- `ai-ui-reference/design-systems/README.md`
- `ai-ui-reference/frontend-design/README.md`
- `ai-ui-reference/landing-page-inspiration/README.md`

Recommended future canonical home: keep as reference or move to `docs/_legacy/ui-reference/` after approval if the design direction is superseded.

## Legacy Mapping
No existing docs were moved to `docs/_legacy/` during STEP DOC-01.

Legacy candidates:
- `ai-ui-reference/*` because these are inspiration notes and may conflict with newer AI foundation/design rules.
- Branch-scoped implementation reports after canonical module docs are created.

## Duplicate / Overlap Mapping
These docs are not exact duplicates, but they overlap and should be cross-linked when canonical docs are created:
- SEO overlap: `global-seo-default-settings.md`, `global-social-share-image-settings.md`, `global-structured-data-business-schema.md`.
- CTA/tracking overlap: `global-booking-cta-settings.md`, `global-tracking-integrations-settings.md`, `global-contact-information-settings.md`.
- Global brand identity overlap: `global-brand-colors-settings.md`, `global-business-identity-settings.md`, `global-site-logo-settings.md`, `global-favicon-settings.md`.
- Page section overlap: `Page_Sections/page-sections-product-page-sync.md`, `Page_Sections/page-sections-image-sync-audit.md`.
- Product page mapping overlap: `Product/Product_Page_UI/product-page-ui-refresh.md`, `Product/Product_Detail_UI/product-detail-ui-refresh.md`, `Page_Sections/page-sections-product-page-sync.md`.
- Admin UI overlap: `Admin_Dashboard/STEP_11_Index_UI_Upgrade/README.md` summarizes the 11A-11E sub-step docs.

## Docs That Need Cleanup
- Root-level `global-*` docs should eventually be grouped under a canonical module/admin index.
- Folder naming is mixed: `Admin_Dashboard`, `Page_Sections`, `Product`, and lowercase folders all coexist.
- Several docs are branch reports, not canonical module documentation.
- `ai-ui-reference/admin-dashboard-inspiration/README.md` mentions rounded `2xl` cards and indigo/purple direction; this should be reviewed against current admin/dashboard and AI foundation rules.
- `docs/ai-ui-reference/agent-skills/` exists as an empty folder.
- Canonical docs for architecture, database, security, performance, SEO, QA, and changelog still need real content.

## Safe Restructure Result
This step created canonical folders with placeholder README files only. Existing documentation remains untouched in place.

## Recommended Next Step
Create canonical module docs one area at a time, starting with Global Assets and Page Sections. After each canonical doc exists, decide whether the old branch-scoped docs should stay as references or move to `docs/_legacy/` with explicit approval.
