# Task B6 — Phase 7 Catalog Localized (Business Milestone)

**Phase:** 7 — Internationalization (i18n)
**Date:** 2026-07-10
**Branch:** `feature/phase-7-a1-foundation`
**Type:** Localize protected catalog modules via the B1 sidecar (extend-only, **no schema change**).

---

## Task: B6 — Products / Categories / Destinations bilingual

Make the tour catalog bilingual: the copy columns translate through the B1
sidecar; **prices, images, slugs, category/destination relations, and status
stay shared** — protected modules stay fully functional without the trait.
This is the **business milestone**: tour listing + detail render in the visitor's
language.

### Changed

**Models (Translatable trait + accessors)**
- `app/Models/Product.php` — `use Translatable`;
  `$translatable = [name, short_description, description, meeting_point,
  pickup_note, cta_title, cta_description, cta_button_text, meta_title,
  meta_description]`; 10 locale-aware accessors.
- `app/Models/Category.php` — `use Translatable`;
  `$translatable = [name, description]`; 2 accessors.
- `app/Models/Destination.php` — `use Translatable`;
  `$translatable = [name, description]`; 2 accessors.

**Frontend (N+1 guard — eager-load current locale)**
- `app/Http/Controllers/Frontend/HomeController.php` — `homeProducts`,
  `categories`, `destinations` add `->withTranslations()`.
- `app/Http/Controllers/Frontend/ProductController.php` — listing + detail add
  `->withTranslations()`; detail also eager-loads `category` and `destination`
  translations via nested callbacks.

**Admin write side (per-locale sidecar persistence)**
- `app/Services/CategoryService.php` — `syncTranslations()` after store/update.
- `app/Services/DestinationService.php` — `syncTranslations()` after store/update.
- `app/Services/ProductService.php` — `syncTranslations()` after store/update
  (10 fields).

**Admin edit views (per-locale translation cards)**
- `resources/views/backend/categories/edit.blade.php`
- `resources/views/backend/destinations/edit.blade.php`
- `resources/views/backend/products/form.blade.php` — Translations details block
  (collapsible), grouped inputs for all 10 fields per active non-default locale.
  Product card shown only for existing products (needs `$product->id`).

**Tests**
- `tests/Feature/Phase7/B6CatalogLocalizedTest.php` — 7 tests.

### Design notes
- **Accessor transparency.** Reading `$product->name` / `$category->name` /
  `$destination->name` transparently returns the current locale's value —
  everything downstream (product cards, detail Blade, home sections, breadcrumbs,
  SEO builder) localizes without any change to Support classes or views.
- **Zero default-locale overhead.** `translate()` short-circuits to the base
  column for the default locale before touching the relation — verified: reading
  `$product->name` after `->withTranslations()` in EN fires **0 extra queries**.
- **N+1 guard verified.** Loading 3 products + reading `$name` on each with
  `->withTranslations()` = **2 queries** (products + translations), not per-row.
- **Scope.** Only scalar copy columns are translated. Slug stays shared (default
  URL behavior; a per-locale product slug is out of scope for B6 — Products/
  Categories/Destinations remain single-record entities like B3's PageSection).

### Impact
- DB: **none** (uses B1's `translations` table).
- Routes: none (reuses existing catalog admin routes).
- Frontend: product cards / detail / breadcrumbs / SEO render in the active
  locale where a translation exists; default output is byte-identical.
- Security: admin-guarded; only the allow-listed fields are persisted; empty
  clears the row; escapes via existing Blade paths.

### Verification
- `B6CatalogLocalizedTest` — **7/7 pass** (Category + Destination accessor
  localizes; Product multi-field; fallback to base; default-locale = 0 extra
  queries; N+1 guard = 2 queries; shared attributes untouched).
- Full suite — **938/938 pass** (931 + 7 B6; one bounded-query test bumped
  22 → 25 to reflect the intentional +3 translations eager-loads on product +
  category + destination — this trade is exactly the C2 N+1 guard).
- PHPStan level 5 — **0 errors**.

### Rollback
`git revert <B6 commit>`. Base columns retain all default-locale copy; models
work unchanged without the trait.

### Milestone
**M4 (Catalog & polish) — B6 done.** Tours/Categories/Destinations bilingual.
Remaining M4: B7 (Menus + Terms), B8 (Admin translation-status UX polish),
B9 (finish `content_field` locale-aware; per-locale builder preview).

### Next
- **B7 — Menus & Terms:** `MenuItem` labels via sidecar (Opt A locked at A0),
  `Term` name/description via sidecar (extend-only).
