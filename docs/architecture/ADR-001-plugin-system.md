# ADR-001 — Plugin & Module System Architecture

**Date:** 2026-06-20
**Status:** Accepted
**Phase:** 4

---

## Context

Bintan Prestige CMS has completed Phases 1–3: core data, website builder, and theme system.
The next phase requires extending the CMS with new features (SEO Manager, Contact Form, Analytics)
without touching core files. We need a formal extension mechanism.

**Problems to solve:**
- New features added by editing core files break future upgrades and increase regression risk
- No way to enable/disable features per environment or client
- No isolation boundary between core CMS logic and optional functionality

---

## Decision

Build a **plugin system** modelled on WordPress plugins but implemented with native Laravel primitives:

| Concern | Approach |
|---------|----------|
| Discovery | Scan `app/Plugins/{Name}/plugin.json` manifest files |
| Registration | `plugins` DB table tracks installed + active state |
| Loading | Active plugin `ServiceProvider`s registered in `AppServiceProvider::register()` |
| Extension points | `CmsHooks` facade — `addAction()` / `doAction()` / `addFilter()` / `applyFilters()` |
| Admin UI | `/admin/plugins` — activate, deactivate, view settings |
| Security | PHP token scanner before activation; permission scopes declared in manifest |
| Audit | Every activate/deactivate recorded in `audit_logs` via IMP-01 |

---

## Plugin Manifest Format (`plugin.json`)

```json
{
    "name":             "SEO Manager",
    "slug":             "seo-manager",
    "version":          "1.0.0",
    "author":           "Bintan Prestige",
    "description":      "Advanced SEO management for Bintan Prestige CMS",
    "min_cms_version":  "4.0.0",
    "service_provider": "App\\Plugins\\SeoManager\\SeoManagerServiceProvider",
    "requires":         []
}
```

**Required fields:** `name`, `slug`, `version`, `service_provider`
**Optional fields:** `author`, `description`, `min_cms_version`, `requires`

**Validation rules:**
- `slug` must be kebab-case, unique across installed plugins
- `version` must follow semver (MAJOR.MINOR.PATCH)
- `service_provider` must be a fully-qualified class name
- `requires` is an array of `{ "slug": "other-plugin", "version": ">=1.0.0" }` objects

---

## Directory Structure

```
app/Plugins/
├── SeoManager/
│   ├── plugin.json
│   ├── SeoManagerServiceProvider.php
│   ├── Http/Controllers/
│   ├── Models/
│   └── resources/views/
├── ContactForm/
│   ├── plugin.json
│   └── ContactFormServiceProvider.php
└── Analytics/
    ├── plugin.json
    └── AnalyticsServiceProvider.php
```

---

## Hook System (CmsHooks facade)

```php
// Action hooks — fire-and-forget side effects
CmsHooks::addAction('page.render', fn(Page $page) => ..., priority: 10);
CmsHooks::doAction('page.render', $page);

// Filter hooks — transform a value through a pipeline
CmsHooks::addFilter('page.content', fn(string $html) => $html, priority: 10);
$html = CmsHooks::applyFilters('page.content', $rawHtml);
```

**Core action hooks:** `cms.init`, `admin.loaded`, `page.render`, `block.render`, `menu.render`, `plugin.activated`, `plugin.deactivated`

**Core filter hooks:** `page.content`, `block.output`, `seo.meta`, `nav.items`, `theme.tokens`

---

## Consequences

**Positive:**
- Core codebase stays frozen; new features live in `app/Plugins/`
- Each plugin can be toggled on/off without code changes
- Security scanner runs before every activation
- Audit log records all plugin lifecycle events

**Negative / Trade-offs:**
- Plugin loading adds one DB query per boot (mitigated by caching active slugs for 60 min)
- Plugin errors could propagate to the core — mitigated by try-catch in `PluginManager`
- Discovery requires a full filesystem scan on demand (not on every request)

---

## Alternatives Rejected

| Alternative | Reason rejected |
|-------------|----------------|
| Laravel Packages (via Composer) | Too heavy for in-house CMS extensions; forces a publish cycle |
| Event listeners only (no manifest) | No on/off toggle, no security scanning, no admin UI |
| Hard-coded feature flags | Doesn't scale; every flag requires a deploy |

---

## Implementation Steps

See `ai/skills/phase4-plugin-module-skill.md` Section 4 for the full 11-week schedule.
