# Module: Internationalization (Phase 7)

> Developer reference for the i18n stack: locale catalogue, URL routing,
> row-per-locale documents, polymorphic attribute translations, admin
> translation UX, and SEO. Built in Phase 7 (A0 → C4). Full step reports live
> in `ai/reports/phase-7/`.

---

## 1. What it does

The whole website — chrome, pages, content entries, catalog (tours,
categories, destinations), menus, taxonomy terms — is authored per locale from
the same admin dashboard. Visitors get the site in their language at
locale-aware URLs, untranslated content is handled gracefully (documents 404
in their locale only, attributes fall back to the default language), and every
SEO surface (hreflang, canonical, OG, JSON-LD, sitemap) reflects the current
locale.

Ship configuration: `en` (default, unprefixed) + `id` (`/id/...`). Adding a
third locale is a **single config entry** — no schema change.

---

## 2. Two translation shapes (A0 §3.3 / §3.4)

Phase 7 uses **two** deliberately different translation shapes:

### 2.1 Documents = row-per-locale (Pages, ContentEntries)

Each locale is a **full record** linked by `translation_group_id` (ULID). It
has its own slug, SEO fields, and builder block tree, and publishes
independently.

```
pages
  id
  slug              # unique(slug, locale) — same slug allowed across locales
  locale            # 'en', 'id', ...
  translation_group_id (ulid, indexed) # links siblings

content_entries
  id
  content_type_id, slug   # unique(content_type_id, slug, locale)
  locale
  translation_group_id (ulid, indexed)
```

Why: a document's block builder tree, per-locale SEO, and independent publish
lifecycle are first-class needs. Trying to encode all locales in one row would
force JSON columns, block indexes, and disable per-locale drafts.

### 2.2 Attributes = polymorphic sidecar (everything else)

Products, Categories, Destinations, PageSections, MenuItems, Terms,
SiteSettings, … keep their existing row. Non-default translations live in a
single sidecar table; the **default locale stays in the base column**.

```
translations
  id
  translatable_type, translatable_id     # polymorphic owner
  locale                                  # 'id', 'zh', ... (never the default)
  field                                   # 'name', 'label', 'value', ...
  value                                   # longtext
  timestamps
  UNIQUE(translatable_type, translatable_id, locale, field)
  INDEX (translatable_type, translatable_id, locale)
```

Why: protected modules (Products, Bookings, PageSections, …) can't be
rebuilt. This is extend-only — the base column keeps the module fully
functional even if Phase 7 is reverted.

---

## 3. Data model summary

```
config/locales.php          # code catalogue + default + og_locale
translations                # polymorphic attribute sidecar
pages                       # + locale, + translation_group_id, unique(slug, locale)
content_entries             # + locale, + translation_group_id, unique(type, slug, locale)
# ~everything else~         # unchanged
```

Migrations (all additive + reversible):

- `2026_07_09_000001_create_translations_table` (new table)
- `2026_07_09_000002_add_locale_to_pages_table` (ALTER pages)
- `2026_07_09_000003_add_locale_to_content_entries_table` (ALTER entries)

The dev DB carries recovery data (Phase 6 §16), so a `mysqldump` is taken
before every ALTER; backups live in `storage/app/db-backups/pre-b*-*.sql`.

---

## 4. Config

```php
// config/locales.php
return [
    'default'  => env('APP_LOCALE', 'en'),
    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),
    'locales'  => [
        'en' => ['native' => 'English', 'label' => 'English',
                 'flag' => '🇬🇧', 'og_locale' => 'en_US', 'is_active' => true],
        'id' => ['native' => 'Bahasa Indonesia', 'label' => 'Indonesian',
                 'flag' => '🇮🇩', 'og_locale' => 'id_ID', 'is_active' => true],
    ],
];
```

Set `is_active => false` to build a translation quietly before exposing it.

---

## 5. Routing

- Frontend routes are registered per active locale in `bootstrap/app.php`.
- **Non-default locales register FIRST** with a `/{code}` prefix + `{code}.`
  route-name prefix + `SetLocale:{code}` middleware. This is deliberate: each
  routes file ends with a `Route::fallback()` (Phase 6 B11); registering the
  prefixed groups first ensures the prefixed fallback matches `/{locale}/...`
  before the bare fallback swallows it.
- The default group registers last, bare, with canonical route names.

```
/                → home (unchanged)
/products        → products.index (unchanged)
/pages/{slug}    → pages.show (unchanged) — resolveRouteBinding filters
                                             by Locales::localeFromRequest()
/id              → id.home
/id/products     → id.products.index
/id/pages/{slug} → id.pages.show
/{route_base}[/…]         → content-entry fallback (default)
/id/{route_base}[/…]      → content-entry fallback (id)
```

**Reserved-prefix guard.** `ContentType::reservedPrefixes()` merges
`RESERVED_PREFIXES` with the config locale codes so a content-type
`route_base` can never collide with a locale prefix.

**Route-model binding gotcha.** `SubstituteBindings` runs BEFORE
`SetLocale`, so route-model binding **must not** rely on
`app()->getLocale()`. `Page::resolveRouteBinding()` reads the URL segment via
`Locales::localeFromRequest()` instead.

---

## 6. Reading translated values

### Documents (row-per-locale)
Read the sibling in the current locale directly — each locale is a full row.
Frontend controllers filter by `Locales::current()` and the switcher passes
`localeAlternates` to the head partial (only published siblings).

### Attributes (Translatable trait)
```php
use App\Models\Concerns\Translatable;

class PageSection extends Model
{
    use Translatable;

    /** @var list<string> */
    protected array $translatable = ['label', 'title', 'subtitle',
                                     'description', 'button_text'];

    // Locale-aware accessors → downstream code stays unchanged.
    public function getTitleAttribute(): ?string { return $this->translate('title'); }
    // …
}
```

`$section->title` returns the current locale's value with fallback to the base
column. Default locale short-circuits to the base column (zero query overhead).

### Eager loading (mandatory for lists)
```php
Product::query()->withTranslations()->get();          // current locale only
Product::query()->withAllTranslations()->get();        // for admin edit screens
```

`withTranslations()` is the **C2 hard gate**: without it a list can degrade to
N+1. The C2 fence tests explicitly verify that translation query counts stay
flat as the catalog grows.

---

## 7. Writing translations

`setTranslation($field, $locale, $value)`:
- Default locale → writes the base column.
- Non-default + non-empty → upsert sidecar row.
- Non-default + empty → deletes the sidecar row (fallback resumes).

Every write path guards on `Locales::nonDefaultActive()` and validates
`translations.*.<field>` in the FormRequest.

---

## 8. Admin translation UX

- **Global chrome (SiteSettings):** dedicated per-locale translation panel at
  `admin/settings/global-assets/translations` (allow-list in
  `App\Support\TranslatableSettings`).
- **Documents (Pages, Entries):** an edit-screen Translations card lists every
  active non-default locale with **Edit {LOCALE}** or **+ Translate to {LOCALE}**
  actions. The action creates a draft sibling in the same group and copies the
  builder block tree.
- **Attributes (Product, Category, Destination, PageSection, Term):** per-locale
  translation cards on the edit form.
- **Menus:** drawer form for a menu item has per-locale label inputs (structure
  is shared).
- **List views (Pages, Entries):** per-locale status pill (● published, ● draft,
  ○ missing) + a locale filter dropdown. Rendered from an eager-loaded
  `translationSiblings` collection — no N+1.

---

## 9. Fallback semantics (A0 §3.5)

| Missing thing | Resolution |
|---|---|
| Document translation (Page / ContentEntry in a locale) | **404 in that locale**; hreflang skips it; switcher hides it |
| Attribute translation (Product name, Category description, …) | Falls back to the base column (default-locale value) |
| `content_field(entry_id=X)` block on a page whose current locale ≠ X's | Resolves the group sibling in current locale; if none, serves the referenced row when published; hides otherwise |
| `content_query` results | Filter `WHERE locale = current` — never shows documents from the wrong locale |

Rationale: attributes fall back so protected modules keep rendering; documents
don't fall back so we never publish half-translated pages under the wrong
locale.

---

## 10. SEO surfaces

Every locale-observable surface localizes:

- `<html lang>` — `Locales::current()`.
- `hreflang` alternates + `x-default` — `partials/site-hreflang.blade.php`.
  Frontend controllers pass `$localeAlternates` (only published siblings) for
  document routes; chrome routes fall back to a generic path swap.
- Canonical URL — locale-prefixed for non-default locales.
- OG `og:locale` — `Locales::ogLocale()` (config-driven `en_US`, `id_ID`).
- JSON-LD `WebPage` / `WebSite` — `inLanguage` on both. Site-wide JSON-LD is
  hex-escape hardened (`JSON_HEX_*`).
- Sitemap — one `<url>` per row, grouped by `translation_group_id`, each
  entry emits `xhtml:link hreflang="..."` alternates + `x-default`.
- 404 view — `resources/views/errors/404.blade.php` sets locale from URL because
  the exception handler bypasses route-group middleware.

---

## 11. Preview

The admin builder preview runs on unprefixed URLs (no `/{locale}` prefix).
Both `PageController::renderPage(preview: true)` and
`ContentEntryBuilderController::previewPayload()` explicitly call
`app()->setLocale($record->locale)` so the preview renders in the record's
own locale.

---

## 12. Rollback safety

- All migrations are additive + reversible; every schema-touching task is
  gated on explicit owner approval + `mysqldump`.
- Base columns hold the default-locale value, so reverting Phase 7 leaves the
  site functional (just monolingual).
- No new packages; the whole engine is one trait, one middleware, one config,
  a few Blade partials, and a set of eager-load calls.

---

## 13. Related

- Standard: `ai/skills/i18n-skill.md` (the canonical i18n rules).
- Grand plan: `ai/reports/phase-7/phase-7-grand-plan.md`.
- Living handoff: `ai/reports/phase-7/phase-7-progress-handoff.md`.
- Step reports: `ai/reports/phase-7/{a0..c4}-*.md`.
