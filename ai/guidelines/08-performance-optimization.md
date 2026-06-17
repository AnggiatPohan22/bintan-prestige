# Performance Optimization Guideline

## Main Goal
Keep the public site fast on mobile and keep admin pages responsive as CMS data grows.

## Required References
- Master rule: `AGENTS.md`
- Related skill: `performance-skill.md`
- Related guidelines: `03-backend-data-processing.md`, `04-frontend-uiux-standard.md`, `09-testing-qa-release.md`

## Frontend Rules
- Optimize images and prefer WebP where practical.
- Use responsive image dimensions and lazy loading below the fold.
- Avoid layout shift by reserving image and component dimensions.
- Avoid oversized JavaScript and unnecessary libraries.
- Use lightweight animations only.
- Keep DOM structure clean and purposeful.

## Backend Rules
- Use eager loading to avoid N+1 queries.
- Use pagination for large admin/public lists.
- Cache stable public content when appropriate.
- Avoid repeated queries inside Blade.
- Use indexes only with clear reason and approval when schema changes are needed.
- Document production optimization commands when relevant.

## Reporting
Performance reports should mention image risks, heavy scripts, N+1 risks, missing pagination, cache opportunities, and layout shift risks.
