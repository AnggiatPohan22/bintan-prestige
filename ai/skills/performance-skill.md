# Performance Skill

## Main Goal
Keep frontend and backend fast, stable, and smooth.

## Frontend Performance
- Optimize images to WebP.
- Use responsive image sizes.
- Lazy load below-the-fold images.
- Preload hero image if needed.
- Avoid layout shift.
- Avoid oversized JavaScript.
- Avoid unnecessary carousel libraries.
- Keep animations transform/opacity based.
- Avoid animation that triggers layout recalculation.
- Keep DOM clean.

## Backend Performance
- Use eager loading.
- Avoid N+1 queries.
- Use pagination.
- Cache stable content.
- Cache config/routes/views in production.
- Use indexes for filter/search columns.
- Avoid loading all records for admin tables.
- Optimize image upload process.
- Avoid repeated query in Blade.

## Performance Targets
- Public homepage Lighthouse Performance: 90+
- Product detail page: 90+
- Mobile usability: good
- LCP: under 2.5s
- INP: under 200ms
- CLS: under 0.1
- Admin index pages load quickly with pagination

## AI Must Report
- Slow queries
- Large images
- Heavy scripts
- Unused components
- N+1 risk
- Missing cache opportunity
- Layout shift risk