# i18n Skill (Phase 7)

> **Read this before adding ANY translatable field, localized route, or SEO
> surface.** Phase 7 is extend-only and rests on two very specific patterns —
> mixing them or skipping the eager-load rules will break protected modules or
> introduce a per-attribute N+1 that C2 fences catch.

---

## ⭐ CANONICAL i18n STANDARD (MANDATORY)

### Rule 1 — Two translation shapes, do not mix them

A model is **either** a document **or** an attribute owner:

| Shape | Use when | How to translate | Fallback |
|---|---|---|---|
| **Document** = row-per-locale | Pages, ContentEntries — each locale has its own slug / SEO / builder tree / publish state. | Add `locale` + `translation_group_id` + locale-aware unique index. Each locale is a full row linked by ULID. | **Hide** (404 in that locale only). |
| **Attribute** = polymorphic sidecar | Everything else — Products, Categories, Destinations, PageSections, MenuItems, Terms, SiteSettings, … | `use Translatable`; declare `$translatable = [...]`; add locale-aware accessors. | **Fall back** to the base column. |

Rule of thumb: if losing the ability to publish per locale would surprise the
owner, it's a document. Otherwise it's an attribute.

### Rule 2 — Base column always holds the default-locale value

The sidecar stores non-default locales **only**. Writing the default locale
via `setTranslation` writes the base column. Protected modules keep working
even if Phase 7 is reverted. Never invert this.

### Rule 3 — Eager-load or C2 breaks

Any listing that renders translated attributes MUST scope-load translations:

```php
Product::query()->withTranslations()->get();          // current locale
Product::query()->withAllTranslations()->get();       // admin, all locales
```

Never call `$model->translate(...)` in a loop without prior eager-load. The
C2 fence tests explicitly assert translation query counts stay **flat** as
catalog size grows.

### Rule 4 — Documents publish per locale, attributes fall back

`content_query` filters `WHERE locale = current`. `content_field` referencing
`entry_id` resolves the sibling in the current locale. `hreflang` and the
switcher only list locales with a **published** translation.

### Rule 5 — Public URLs are locale-prefixed for non-default; default stays bare

Adding a new locale means one entry in `config/locales.php`. Route names for
non-default locales are `{code}.<name>` (e.g. `id.pages.show`). Default locale
route names are unprefixed and byte-identical to pre-Phase 7.

### Rule 6 — Route-model binding runs before SetLocale

`resolveRouteBinding` for `{model:slug}` on a row-per-locale model must read
locale from the URL via `Locales::localeFromRequest()`, not from
`app()->getLocale()` (which SetLocale hasn't set yet).

### Rule 7 — Reserved-prefix guard

A locale code (`id`, `en`, `zh`, …) can never be a content-type `route_base`.
Use `ContentType::reservedPrefixes()`, not the constant.

### Rule 8 — Preview must set the record's locale explicitly

Admin preview URLs are unprefixed (`/admin/pages/{id}/preview`), so SetLocale
does NOT set the target locale. `PageController::renderPage(preview: true)`
and `ContentEntryBuilderController::previewPayload()` call
`app()->setLocale($record->locale)` before rendering.

### Rule 9 — SEO surfaces localize together

Any new page adds all of these at once:

- `hreflang` alternates (`partials/site-hreflang.blade.php` handles it — pass
  `$localeAlternates` from the controller when documents are involved).
- Canonical URL uses the record's own locale-prefixed path.
- `<html lang>` = `Locales::current()`.
- JSON-LD builders emit `inLanguage`.

### Rule 10 — Zero new packages

`spatie/laravel-translatable` (JSON approach) conflicts with Rule 1. Same for
`astrotomic/laravel-translatable` (table-per-model). Own the pattern — it is
one trait, one middleware, one config, one handful of Blade partials.

---

## 1. Config catalogue

`config/locales.php`:

```php
return [
    'default'  => env('APP_LOCALE', 'en'),
    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),
    'locales'  => [
        'en' => [..., 'og_locale' => 'en_US', 'is_active' => true],
        'id' => [..., 'og_locale' => 'id_ID', 'is_active' => true],
    ],
];
```

Adding a locale = one entry (+ typed translations). Never rename an existing
key.

---

## 2. Locales helper (`app/Support/Locales.php`)

| Method | Purpose |
|---|---|
| `default()` | The unprefixed locale. |
| `current()` | `app()->getLocale()` (post-SetLocale reads). |
| `localeFromRequest()` | URL-segment locale (safe pre-SetLocale). |
| `active()` / `activeCodes()` | Config-order catalogue of active locales. |
| `nonDefaultActive()` | Codes that get a prefix (drives write loops + form loops). |
| `isActive($code)` / `isSupported($code)` | Guards. |
| `ogLocale($code)` | Config-driven `en_US`/`id_ID` (fallback `{code}_{UPPER(code)}`). |
| `localizedUrl($target, $path=null)` | Prefix swap for the switcher on chrome routes. |

---

## 3. Adding a translatable attribute field

**Example: adding `product.summary` as translatable.**

1. Model — add to `$translatable` array and a locale-aware accessor:
   ```php
   protected array $translatable = [..., 'summary'];
   public function getSummaryAttribute(): ?string { return $this->translate('summary'); }
   ```
2. Controller / Service — accept `translations.<locale>.summary` and call
   `$product->setTranslation('summary', $locale, $value)` for each
   `Locales::nonDefaultActive()`.
3. FormRequest — allow-list `translations.*.summary` with the same rules as the
   base field.
4. Admin edit view — extend the existing per-locale Translations card.
5. Frontend — nothing to change. `$product->summary` transparently localizes.
6. Eager-load — verify list queries already have `->withTranslations()`; add if
   not.

**Do NOT** create a per-field admin route or a separate translation form. The
existing per-locale card is the canonical pattern.

---

## 4. Adding a translatable document type

If a new content shape truly is a document (per-locale block tree / SEO /
publish state), it follows the same pattern as Pages / ContentEntries:

1. Add `locale string(10) default '{default}'` + `translation_group_id char(26)
   indexed` to its table. **⚠️ mysqldump before the ALTER.**
2. Change unique index to include `locale`.
3. Model:
   - Fillable includes `locale`, `translation_group_id`.
   - `booted()` assigns `locale = default` and a fresh `Str::ulid()` when both
     are blank.
   - `translationSiblings()` HasMany self-join on `translation_group_id`.
   - `translationIn($locale)` sibling lookup.
   - `resolveRouteBinding()` for slug binding filters by
     `Locales::localeFromRequest()`.
   - `publicUrl()` returns a locale-prefixed URL (default locale unprefixed).
4. Frontend controller: filter by `Locales::current()`, expose
   `$localeAlternates` from published siblings for the switcher/hreflang.
5. Admin: a **Translate to {LOCALE}** action + Translations panel on the edit
   view (copy blocks + related data into the same group as a draft).
6. FormRequest slug: `unique(table.slug)->where('locale', $locale)`.

---

## 5. Adding a locale (e.g. `zh`)

1. `config/locales.php` — add the entry with `is_active => true`,
   `og_locale => 'zh_CN'`.
2. Translate `lang/zh/frontend.php` (mirror `lang/id/frontend.php`).
3. Backfill translations from the admin. No migration, no code change.
4. That's it. Every switcher, sitemap, hreflang, canonical, OG surface picks it
   up automatically.

---

## 6. SEO surfaces (never miss one)

New locale-observable render → check off:

- [ ] `<html lang>` uses `Locales::current()`.
- [ ] `partials/site-hreflang.blade.php` is included in the head.
- [ ] Canonical URL is built with the record's own locale (unprefixed for
      default; `{code}.<name>` route for non-default).
- [ ] OG `og:locale` uses `Locales::ogLocale(Locales::current())`.
- [ ] JSON-LD emits `inLanguage`.
- [ ] Sitemap URL emitted per locale sibling; `xhtml:link` alternates per group.

---

## 7. Escape audit (C1 rule)

Translated sidecar values reach Blade through model accessors. All existing
`{!! !!}` locations either sanitize inline (`InlineContentSanitizer::richtext`)
or hex-escape (`JSON_HEX_*`). **Never** raw-echo `$model->translate(...)` or
`$model->rawTranslation(...)`. `C1EscapeAuditTest` is the regression fence.

---

## 8. Reuse ledger

Don't fork these — they already work:

- `Route::fallback()` (Phase 6 B11) — keep lowest per prefix.
- `content_entry_index` (Phase 6 B6) — archive queries add locale filter.
- Phase 4 revisions / scheduling / audit / SEO — per-record, unchanged.
- `BuilderTreeSanitizer` / `PageBlockService` / block registry — unchanged.
- Theme tokens + templates (Phase 3/5).
- `ContentQueryResolver` / `ContentFieldResolver` (Phase 6 B13/B14).

---

## 9. Testing

Every task in Phase 7 shipped with its own fence test. When extending a
translatable model, mirror `B6CatalogLocalizedTest`:

- Accessor localizes + falls back.
- Default locale = 0 extra queries.
- `withTranslations()` scales flat.
- Shared attributes not touched.

The C2 audit test set also acts as a global fence — any new listing that
introduces a per-attribute lazy load will trip
`test_products_index_translation_queries_do_not_grow_with_product_count`.

---

## 10. Related

- Module doc: `docs/modules/internationalization.md`.
- Grand plan: `ai/reports/phase-7/phase-7-grand-plan.md`.
- Living handoff: `ai/reports/phase-7/phase-7-progress-handoff.md`.
- Step reports: `ai/reports/phase-7/{a0..c4}-*.md`.
