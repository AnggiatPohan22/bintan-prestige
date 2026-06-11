# CMS Architect Skill

## Main Goal
Design the CMS so frontend content can be managed from the admin dashboard.

## Current Phase
CMS sync between backend and frontend.

## Future Optional Phase
Admin dashboard may become a frontend builder/page builder only when explicitly requested.

## Rules
- Do not automatically build page builder.
- Do not create new schema without approval.
- Do not replace existing page_sections logic.
- Extend existing modules safely.
- Any frontend section should be manageable from backend when possible.
- Use reusable section pattern.
- Keep fixed layout and dynamic content separated.

## CMS Module Pattern
Each module must define:
- database table
- model
- relationship
- admin CRUD
- validation
- frontend rendering
- SEO handling
- documentation
- test checklist

## Existing CMS Modules
- products
- categories
- destinations
- product prices
- product images
- product features
- product FAQs
- product itineraries
- product notes
- page sections