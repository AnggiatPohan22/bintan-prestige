# A5 — Frontend Polish Baseline Plan

## Date: 2026-06-21
## Branch: feature/phase-5-stage-a-foundation
## HEAD audited: a878b6f (post-A4 commit)
## Status: AUDIT COMPLETE — awaiting owner approval before any code change

---

## 1. Scope

Read-only inspection of all 19 frontend block partials against:

- Color token consistency (Phase 3 `--frontend-*` CSS custom properties)
- Typography — font-family, heading scale, line-height
- Spacing consistency across blocks
- Responsive behavior per block
- Default "good-looking" block presets

---

## 2. Token system defined in `frontend-theme.css`

The Phase 3 theme defines the following authoritative tokens on `:root`:

| Token | Value | Purpose |
|---|---|---|
| `--frontend-black` | `#090806` | Deep black (body bg, button text) |
| `--frontend-charcoal` | `#17130c` | Warm body text color |
| `--frontend-gold` | `#c8a24a` | Primary brand accent |
| `--frontend-gold-soft` | `#f5ead0` | Light gold tint |
| `--frontend-gold-pale` | `#fbf7ed` | Page background gradient |
| `--frontend-white` | `#ffffff` | Pure white |
| `--frontend-gray` | `#6f6a60` | Warm gray / secondary text |
| `--frontend-border` | `#e7dcc2` | Warm border color |
| `--frontend-font-display` | Forum, Georgia, serif | Headings |
| `--frontend-font-body` | Montserrat, system-ui, sans-serif | Body text |

Typography is applied globally to `.frontend-body` children:

- `h1–h6`: `font-family: var(--frontend-font-display)` via `:where(h1–h6)` (zero specificity)
- `p, span, li, a, button, ...`: `font-family: var(--frontend-font-body)`
- `.frontend-body { color: var(--frontend-charcoal) }` — headings `inherit` this

---

## 3. Findings

### F1 — Heading text color overrides theme charcoal (MEDIUM)

**Affects:** `heading.blade.php`, `text.blade.php`, `stats.blade.php`,
`pricing-table.blade.php`, `tour-itinerary.blade.php`, and all other blocks
that put `text-slate-900` on their h2/h3 elements.

The `.frontend-body :where(h1–h6)` rule uses `:where()` — which has
**zero specificity**. Any Tailwind class (`text-slate-900`, etc.) on the
element itself has specificity 0,1,0 and **wins**. This means block headings
render as `#0f172a` (Tailwind slate-900, a cool blue-gray) instead of
`var(--frontend-charcoal)` (`#17130c`, the warm dark charcoal from the
luxury palette).

The visual difference is subtle but breaks brand consistency: body text is
warm, headings are slightly cooler.

**Proposed fix:** Remove explicit `text-slate-*` color classes from heading
elements in block partials and let them inherit `var(--frontend-charcoal)` from
`.frontend-body`. Where a different color is intentionally needed (white on dark
backgrounds), keep it inline.

**Files affected:**
- `resources/views/frontend/blocks/heading.blade.php`
- `resources/views/frontend/blocks/text.blade.php` (the h2 inside Text block)
- `resources/views/frontend/blocks/stats.blade.php`
- `resources/views/frontend/blocks/pricing-table.blade.php`
- `resources/views/frontend/blocks/tour-itinerary.blade.php`
- `resources/views/frontend/blocks/faq.blade.php` (button text)

---

### F2 — Gold fallback values are stale (#D4AF37 ≠ token #c8a24a) (LOW)

**Affects:** All A3 blocks that use `var(--frontend-gold,#D4AF37)`.

The fallback `#D4AF37` was copied from the original block partials (pre-Phase 3
token migration). The actual token is `--frontend-gold: #c8a24a` — a slightly
warmer, less saturated gold. The fallback will never render in practice
(`.frontend-body` always defines the token), but it creates confusion when
reading or debugging the partials.

Additionally, `#B28B22` appears in `stats.blade.php`,
`pricing-table.blade.php`, and `tour-itinerary.blade.php` as a darker gold
variant (for "Featured" labels and timeline markers). This shade is not in the
token system.

**Proposed fix:**
1. Replace `var(--frontend-gold,#D4AF37)` fallback with `var(--frontend-gold,#c8a24a)` across all block partials.
2. Add `--frontend-gold-dark: #b08735` to `frontend-theme.css` `:root` as an official darker-gold token.
3. Replace all `#B28B22` occurrences with `var(--frontend-gold-dark,#b08735)`.

**Files affected:**
- `resources/css/frontend-theme.css` (add token)
- `resources/views/frontend/blocks/button-group.blade.php`
- `resources/views/frontend/blocks/stats.blade.php`
- `resources/views/frontend/blocks/pricing-table.blade.php`
- `resources/views/frontend/blocks/tour-itinerary.blade.php`
- `resources/views/frontend/blocks/hero.blade.php`

---

### F3 — CTA gold style uses `bg-yellow-500` instead of gold token (LOW)

**File:** `resources/views/frontend/blocks/cta.blade.php` line 10.

```php
'gold'  => 'bg-yellow-500 text-black',
```

Tailwind `yellow-500` (`#eab308`) is brighter and more saturated than
`var(--frontend-gold)` (`#c8a24a`). The CTA block's "gold" style does not
actually use the brand gold.

**Proposed fix:** Replace `bg-yellow-500` with
`bg-[var(--frontend-gold,#c8a24a)]` and update the hover class accordingly.

**File affected:** `resources/views/frontend/blocks/cta.blade.php`

---

### F4 — Contact form focus ring uses `focus:ring-indigo-500` (LOW)

**File:** `resources/views/frontend/blocks/contact-form.blade.php` lines 61, 69, 107.

Blue (`indigo-500`) focus rings on a luxury black/gold theme are visually
inconsistent. The hero CTA and button-group already use
`focus:ring-[var(--frontend-gold)]`.

**Proposed fix:** Replace `focus:border-indigo-500 focus:ring-indigo-500`
with `focus:border-[var(--frontend-gold)] focus:ring-[var(--frontend-gold)]`.

**File affected:** `resources/views/frontend/blocks/contact-form.blade.php`

---

### F5 — Button group secondary variant uses plain `border-slate-900` (LOW)

**File:** `resources/views/frontend/blocks/button-group.blade.php` line 24.

```php
'secondary' => 'border border-slate-900 bg-transparent text-slate-900 ...',
```

The secondary button border and text use cool slate-900. For consistency with
the warm luxury palette it should use the charcoal token.

**Proposed fix:** Replace `border-slate-900 text-slate-900` with
`border-[var(--frontend-charcoal)] text-[var(--frontend-charcoal)]`.

**File affected:** `resources/views/frontend/blocks/button-group.blade.php`

---

## 4. What is already correct

| Area | Status |
|---|---|
| Font families | Correctly applied via `.frontend-body` global rule — no per-block overrides needed ✓ |
| Gold token usage in A3 blocks (stats, pricing, itinerary, hero, button) | Correctly references `var(--frontend-gold,...)` ✓ |
| Image lazy loading | All `<img>` and `<iframe>` tags have `loading="lazy"` ✓ |
| Responsive layout for all 19 blocks | All blocks tested with responsive Tailwind classes — stacks correctly on mobile ✓ |
| Spacing rhythm | Most content blocks use `py-12 sm:py-16` or `py-16` — consistent ✓ |
| Empty-block suppression | All 19 blocks render nothing when content is empty ✓ |
| Background image / color overlay system | Consistent `$bgStyle` pattern across all blocks ✓ |
| Products grid carousel | Native scroll-snap swipe + prev/next nav — mobile-friendly ✓ |
| FAQ accordion | Alpine `x-show` + aria attributes — keyboard accessible ✓ |
| Gallery lightbox | Alpine-powered, keyboard-focusable prev/next — accessible ✓ |
| Tour itinerary timeline | Gold left-border + dot markers — visually distinctive ✓ |
| Pricing table featured ring | Uses `var(--frontend-gold)` correctly ✓ |
| Stats value color | Uses `var(--frontend-gold)` correctly ✓ |
| Video embed | Privacy-mode iframe, `loading="lazy"`, responsive `aspect-video` ✓ |

---

## 5. Default block presets assessment

When a new block is added from the admin, defaults come from
`PageBlockService::defaultDataFor()`. Assessment per key block:

| Block | Current default quality | Note |
|---|---|---|
| Hero | title='', image='', dark bg | Good — shows empty state when unfilled |
| Heading | text='', level='h2' | Correct |
| Text | heading='', body_html='' | Correct |
| Stats | heading='', 3 empty items | Good starting scaffold |
| Pricing Table | heading='', 2 empty plans | Good starting scaffold |
| Tour Itinerary | heading='', 3 empty items | Good starting scaffold |
| Button Group | 1 empty button, primary style | Correct |
| CTA | dark style | Correct |
| FAQ | inline source, 2 empty items | Correct |
| Products Grid | limit=6, no filter | Correct — shows latest 6 products immediately |
| Gallery | empty array, grid mode | Correct |
| Testimonials | 2 empty items | Correct |
| Video/Embed | empty url | Correct — block hidden until URL entered |
| Group | contained width, md spacing | Correct |
| Columns | 2 columns, gap=md, stack_mobile=true | Correct |

All defaults produce correct empty-state suppression (no public wrapper rendered
when key content fields are blank).

---

## 6. Proposed A5 implementation plan (pending approval)

### Batch A5.1 — Token consistency fixes (no schema change, CSS + Blade only)

**F1 — Remove text-slate-* from block headings** (highest visual impact)
- Remove `text-slate-900` from h2/h3 in: `heading.blade.php`, `text.blade.php`, `stats.blade.php`, `pricing-table.blade.php`, `tour-itinerary.blade.php`, `faq.blade.php`
- Let headings inherit `var(--frontend-charcoal)` from `.frontend-body`

**F2 — Fix gold fallback + add dark-gold token**
- `frontend-theme.css`: add `--frontend-gold-dark: #b08735` to `:root`
- Update fallback in all `var(--frontend-gold,#D4AF37)` to `var(--frontend-gold,#c8a24a)`
- Replace `#B28B22` with `var(--frontend-gold-dark,#b08735)`

**F3 — CTA gold style → token**
- `cta.blade.php`: `bg-yellow-500` → `bg-[var(--frontend-gold,#c8a24a)]`

**F4 — Contact form focus ring → token**
- `contact-form.blade.php`: `focus:border-indigo-500 focus:ring-indigo-500` → `focus:border-[var(--frontend-gold)] focus:ring-[var(--frontend-gold)]`

**F5 — Button secondary border → token**
- `button-group.blade.php`: `border-slate-900 text-slate-900` → `border-[var(--frontend-charcoal)] text-[var(--frontend-charcoal)]`

### Files that will change in A5.1

```
resources/css/frontend-theme.css
resources/views/frontend/blocks/heading.blade.php
resources/views/frontend/blocks/text.blade.php
resources/views/frontend/blocks/stats.blade.php
resources/views/frontend/blocks/pricing-table.blade.php
resources/views/frontend/blocks/tour-itinerary.blade.php
resources/views/frontend/blocks/faq.blade.php
resources/views/frontend/blocks/button-group.blade.php
resources/views/frontend/blocks/cta.blade.php
resources/views/frontend/blocks/contact-form.blade.php
resources/views/frontend/blocks/hero.blade.php
```

### Files that will NOT change

- No PHP backend file changes.
- No migration, route, controller, or model changes.
- No new packages.
- No Tailwind config changes (all new color usage is via CSS custom properties
  in arbitrary value syntax `text-[var(...)]` which Tailwind already supports).

---

## 7. Rollback

All A5 changes are CSS and Blade only. Rollback with
`git revert <a5-commit>` or manually restore the original lines.

---

## 8. Stage A exit readiness

After A5.1 is implemented and passes the full test suite, Stage A will have:

- A1 ✓ — backend readiness confirmed, block registry authoritative
- A2 ✓ — admin navigation grouped, list/form patterns standardized
- A3 ✓ — 19 block types, nesting foundation with migration
- A4 ✓ — revision restore bulk-updated, form list paginated, 2 unused withCounts removed
- A5 ✓ — token color consistency enforced across all 19 block partials

**Stage A exit gate:** owner sign-off on A5.1 implementation → then
`feature/phase-5-stage-a-foundation` can be merged to `develop` and Stage B
planning can begin.
