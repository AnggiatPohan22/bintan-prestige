# Backend Data Processing Skill

## Main Goal
Prepare all display-ready data in backend before rendering frontend.

## Rules
- Frontend Blade receives clean, ready-to-render data.
- No database query inside Blade.
- No heavy filtering/sorting/calculation in JavaScript.
- Use Eloquent relationships and eager loading.
- Use pagination for large data.
- Use DTO/ViewModel style arrays if needed.
- Use cache for stable public content.
- Use backend image optimization before storing or displaying.

## Frontend Receives
- Published products only
- Active categories only
- Active destinations only
- Prepared prices
- Prepared SEO meta
- Prepared image URLs
- Prepared schema JSON-LD
- Prepared page sections

## Forbidden
- Fetching all products then filtering in frontend
- Passing raw unstructured collections to complex Blade
- Repeating query logic across controllers
- Sorting/filtering large datasets in browser