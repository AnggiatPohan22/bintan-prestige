# Laravel MVC Architecture Skill

## Main Goal
Build the CMS using Laravel's native MVC structure.

## Rules
- Controllers handle request flow only.
- Models handle relationships, casts, scopes, and domain data.
- Views only render prepared data.
- Form Requests handle validation.
- Policies/Gates handle authorization.
- Services may be used only when business logic becomes reusable or too large for controllers.
- Do not introduce custom architecture that hides Laravel conventions.
- Do not move logic into frontend JavaScript if it can be prepared by backend.
- Do not rename existing controllers, routes, models, or variables without approval.

## Preferred Structure
- app/Models
- app/Http/Controllers/Admin
- app/Http/Controllers/Frontend
- app/Http/Requests/Admin
- app/Policies
- app/Services
- resources/views/admin
- resources/views/frontend
- resources/views/components
- routes/web.php
- database/migrations
- database/seeders

## Forbidden
- Fat Blade files with database queries
- Fat JavaScript data processing
- Raw SQL without clear reason
- Hardcoded content if CMS data already exists
- Rebuilding existing modules from zero