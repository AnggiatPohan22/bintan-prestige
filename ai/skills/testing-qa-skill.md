# Testing & QA Skill

## Main Goal
Act as final QA before launch and generate a clear release readiness report.

## QA Coverage

### 1. Database QA
- Check migrations
- Check relationships
- Check nullable fields
- Check default values
- Check seeders
- Check duplicate data risk
- Check deleted/archived data behavior

### 2. Backend QA
- CRUD works
- Validation works
- Authorization works
- Upload works
- Delete confirmation works
- Pagination works
- Search/filter works
- Status draft/published works

### 3. Frontend QA
- Homepage renders
- Product listing renders
- Product detail renders
- Category filter works
- Destination filter works
- FAQ renders
- CTA/WhatsApp link works
- Responsive mobile/tablet/desktop

### 4. Security QA
- Admin protected
- CSRF enabled
- Sensitive routes protected
- Upload validation safe
- APP_DEBUG false for production
- .env not committed
- No suspicious backdoor function
- No exposed credentials

### 5. SEO QA
- Meta title exists
- Meta description exists
- Canonical exists
- Sitemap exists
- robots.txt exists
- Schema valid
- OG image exists
- H1 only logical
- Internal links exist

### 6. Performance QA
- No N+1 query risk
- Images optimized
- Lazy loading applied
- No heavy frontend JS
- Admin tables paginated
- Production cache commands documented

### 7. Documentation QA
- Docs updated
- Changelog updated
- Module docs updated
- Rollback note exists

## Final Score
AI must give score:
- Database: /100
- Backend: /100
- Frontend: /100
- Security: /100
- SEO: /100
- Performance: /100
- Documentation: /100
- Launch Readiness: /100

## Output Report
- Summary
- Critical issues
- High priority issues
- Medium priority issues
- Low priority issues
- Recommended fixes
- Files affected
- Safe next steps