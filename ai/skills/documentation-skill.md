# Documentation Skill

## Main Goal
Keep project documentation updated after every meaningful change.

## Rules
- Every feature change must update docs.
- Every database change must update database docs.
- Every UI change must update frontend/admin docs.
- Every security change must update security docs.
- Every SEO change must update SEO docs.
- Every performance change must update performance docs.

## Recommended Docs Structure

docs/
- README.md
- architecture/
  - system-overview.md
  - mvc-flow.md
  - cms-flow.md
  - frontend-backend-sync.md
  - deployment-flow.md
- database/
  - schema-overview.md
  - relationships.md
  - table-map.md
- modules/
  - products.md
  - categories.md
  - destinations.md
  - page-sections.md
  - seo.md
- frontend/
  - design-system.md
  - components.md
  - pages.md
- admin/
  - dashboard.md
  - product-management.md
  - page-section-management.md
- security/
  - checklist.md
  - audit-log.md
- performance/
  - checklist.md
  - audit-report.md
- qa/
  - launch-checklist.md
  - final-release-report.md
- changelog/
  - CHANGELOG.md

## Documentation Must Include
- What changed
- Why changed
- Files changed
- Database impact
- Route impact
- Frontend impact
- Backend impact
- Security impact
- Testing result
- Rollback note
