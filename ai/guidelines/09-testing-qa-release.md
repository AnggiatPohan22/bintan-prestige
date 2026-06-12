# Testing, QA & Release Guideline

## Main Goal
Verify that changes are safe, documented, and ready for launch without breaking existing CMS behavior.

## Required References
- Master rule: `AGENTS.md`
- Related skill: `testing-qa-skill.md`
- Related guidelines: `06-security-hardening.md`, `07-seo-ai-discovery.md`, `08-performance-optimization.md`

## QA Coverage
- Database structure and relationships
- CRUD flow
- Validation
- Authorization
- Upload handling
- Frontend rendering
- Responsive layout
- SEO meta and schema
- Sitemap and robots rules
- Performance risks
- Security risks
- Documentation completeness

## Test Rules
- Run relevant automated tests when available.
- If tests cannot be run, report the reason and recommend exact tests.
- For UI changes, check desktop and mobile behavior.
- For security changes, include a focused security checklist.
- For SEO changes, verify metadata, schema, internal links, and crawlability.

## Final QA Score
Major launch QA should score Database, Backend, Frontend, Security, SEO, Performance, Documentation, and Launch Readiness out of 100.
