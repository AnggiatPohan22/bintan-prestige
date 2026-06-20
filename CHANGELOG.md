# Changelog

All notable changes to Bintan Prestige CMS are documented in this file.

## [Unreleased]

### Phase 4 — Plugin & Module System

- Added plugin discovery, registry, lifecycle management, dependency ordering, and admin controls.
- Added the `CmsHooks` action and filter system for extension points without replacing core behavior.
- Added plugin activation security scanning, declared permission scopes, exception isolation, and lifecycle audit logging.
- Added the SEO Manager features: XML sitemap, storage-backed robots.txt, redirect management, and per-page robots metadata.
- Added the Contact Form Builder with dynamic validation, honeypot protection, submission storage, and mail notifications.
- Added server-side page analytics, daily aggregation, dashboard chart data, top-page reporting, and CSV export.
- Completed Phase 4 improvements IMP-01 through IMP-07: audit logs, revision history, content scheduling, page duplication, theme ZIP import/export, extended design tokens, and Google Fonts integration.
- Added STEP 9 Q1–Q7 release-gate coverage for XML validity, global redirect middleware registration, lifecycle audit uniqueness and metadata, contact-form persistence, and analytics chart integrity.
- Fixed duplicate plugin lifecycle audit entries emitted by the admin controller and `PluginManager` for the same action.

### Release status

- Phase 4 remains unreleased: PHPStan level 5 is unavailable in the repository, and local HTTP performance measurements exceed the 300 ms target.
- No `v4.0.0` tag has been created.
