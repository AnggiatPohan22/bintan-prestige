# Bintan Prestige CMS — Design System

**Version:** 1.0 | **Stack:** Laravel + Tailwind CSS + Alpine.js

This file is the single source of design truth for **both** the admin backend and the
public frontend. For copy-paste Blade code, see `ai/skills/COMPONENT-LIBRARY.md`. For
frontend patterns and guidelines, see `ai/skills/frontend-design-skill.md`.

---

## 1. Design Philosophy

- **Intentional** — every color, spacing, and type choice is deliberate. No generic AI styling.
- **Luxury** — the public site feels premium: spacious, elegant, gold-accented.
- **Scalable** — shared tokens mean new pages/templates inherit consistency for free.
- **Accessible** — WCAG AA contrast, semantic HTML, keyboard navigation, focus states.
- **Performant** — optimized images, lazy loading below the fold, CSS transitions over JS.
- **Separation** — public luxury styling and admin utility styling never bleed into each other.

---

## 2. Shared Design Tokens

These tokens are the foundation. Admin and frontend draw from the same scale; they differ
only in which slice of the palette and density they apply.

### Color Palette

| Token | Hex | Tailwind | Used By |
|-------|-----|----------|---------|
| Primary Black | `#000000` | `text-black` `bg-black` | Frontend |
| Accent Gold | `#D4AF37` | `text-amber-400` (custom) | Frontend brand |
| Amber 400 | `#FBBF24` | `bg-amber-400` | Admin primary action |
| White | `#FFFFFF` | `bg-white` | Frontend |
| Gray 50 | `#F9FAFB` | `bg-gray-50` | Soft section background |
| Gray 100 | `#F3F4F6` | `text-gray-100` | Admin text-primary |
| Gray 200 | `#E5E7EB` | `border-gray-200` | Frontend borders |
| Gray 300 | `#D1D5DB` | `text-gray-300` | Admin text-secondary |
| Gray 500 | `#6B7280` | `text-gray-500` | Muted text |
| Gray 700 | `#374151` | `border-gray-700` | Admin border / body text |
| Gray 800 | `#1F2937` | `bg-gray-800` | Admin sidebar/header |
| Gray 900 | `#111827` | `bg-gray-900` | Admin content base |
| Success | `#10B981` | `bg-green-500` | Both |
| Warning | `#F59E0B` | `bg-amber-500` | Both |
| Danger | `#EF4444` | `bg-red-500` | Both |
| Info | `#3B82F6` | `bg-blue-500` | Both |

### Spacing Scale (4px base unit)

| Token | Value | Tailwind |
|-------|-------|----------|
| xs | 4px | `p-1` |
| sm | 8px | `p-2` |
| md | 16px | `p-4` |
| lg | 24px | `p-6` |
| xl | 40px | `p-10` |
| 2xl | 80px | `p-20` |

Section top/bottom: `py-20` · Cards gap: `gap-8` · Card padding: `p-6`.
Never use off-scale values (`p-7`, `m-13`, `gap-11`).

### Typography

**Font stacks**
- Frontend headings: Serif — Cinzel / Georgia / Garamond
- Frontend body: Sans-serif — Montserrat / Segoe UI / Roboto
- Admin: system sans-serif for both (utility, not luxury)

**Size scale**

| Element | Classes |
|---------|---------|
| h1 | `text-6xl font-serif font-bold leading-tight` |
| h2 | `text-4xl font-serif font-bold` |
| h3 | `text-2xl font-serif font-bold` |
| body | `text-base text-gray-700 leading-relaxed` |
| small | `text-sm text-gray-500` |

**Weight guide:** `font-bold` for headings & CTAs, `font-medium` for labels/nav,
`font-normal` for body copy.

### Shadows, Radius, Transitions

| Token | Value |
|-------|-------|
| shadow-sm | `0 2px 4px rgba(0,0,0,0.1)` |
| shadow-md | `0 4px 6px rgba(0,0,0,0.1)` |
| shadow-lg | `0 10px 15px rgba(0,0,0,0.15)` |
| radius-sm | `rounded-sm` (4px) |
| radius-md | `rounded-md` (8px) |
| radius-lg | `rounded-lg` (12px) |
| transition fast | `duration-150` |
| transition normal | `duration-300` |
| transition slow | `duration-500` |

---

## 3. Backend Design System (Admin Dashboard)

> **Note (updated 2026-06-23):** The admin uses a **dark sidebar + light content** layout
> (similar to GitHub / Linear), NOT a fully dark admin. The initial spec in this file was
> aspirational. This section now documents the actual implemented design.

### Aesthetic Direction

| Aspect | Style |
|--------|-------|
| Sidebar | Dark — `bg-slate-900` with white text |
| Content base | Light — `bg-slate-50` page background |
| Cards / Panels | `bg-white border-slate-200 shadow-sm` |
| Primary action | Indigo — `bg-indigo-600 hover:bg-indigo-700` (`admin-btn-primary`) |
| Danger action | Red — `bg-red-600 hover:bg-red-700` (`admin-btn-danger`) |
| Density | Medium — efficient for power users |
| Feedback | Instant: toast, loading states, error messages |

### Admin CSS Classes (use these, not inline Tailwind)

All admin component classes are defined in `resources/css/admin.css`. Use the semantic
class names — never inline their Tailwind equivalents, so CSS updates propagate automatically.

| Component | CSS Class | Do NOT use |
|-----------|-----------|-----------|
| Page wrapper | `admin-page` | raw spacing |
| Page header card | `admin-page-header` | `bg-white rounded-2xl ...` |
| Card/panel | `admin-card` | `bg-white border border-slate-200 ...` |
| Card header | `admin-card-header` | `bg-slate-50 border-b ...` |
| Card body | `admin-card-body` | `p-6` |
| Form card | `admin-form-card` | `bg-white rounded-2xl p-6 ...` |
| Primary button | `admin-btn-primary` | `btn-primary` (legacy emerald) |
| Secondary button | `admin-btn-secondary` | `btn-secondary` (legacy) |
| Danger button | `admin-btn-danger` | raw red classes |
| Text input | `admin-input` | `form-input` (legacy) |
| Textarea | `admin-textarea` | `form-textarea` (legacy) |
| Select | `admin-select` | `form-select` (legacy) |
| Label | `admin-form-label` | `form-label` (legacy) |
| Hint text | `admin-form-hint` | raw `text-xs text-slate-400` |
| Table wrapper | `admin-table-wrapper` | raw `overflow-x-auto rounded-2xl ...` |
| Table | `admin-table` | — |
| Table header | `admin-table-header` | — |
| Table row | `admin-table-row` | — |
| Success badge | `admin-badge-success` | raw colors |
| Warning badge | `admin-badge-warning` | raw colors + DO NOT add `style=` override |
| Danger badge | `admin-badge-danger` | raw colors |
| Info badge | `admin-badge-info` | raw colors |
| Empty state | `admin-empty-state` | — |

### Admin Color Palette (actual)

| Role | Value | CSS/Tailwind |
|------|-------|-------------|
| Sidebar | `#0F172A` | `admin-sidebar` (`bg-slate-900`) |
| Page background | `#F8FAFC` | `admin-body` (`bg-slate-50`) |
| Cards / panels | `#FFFFFF` + `#E2E8F0` border | `admin-card` |
| Primary action | `#4F46E5` | `admin-btn-primary` (`bg-indigo-600`) |
| Active sidebar link | `#4F46E5` | `admin-sidebar__link--active` (`bg-indigo-600`) |
| Success | `#059669` | `admin-btn-success` / `admin-badge-success` |
| Danger | `#DC2626` | `admin-btn-danger` / `admin-badge-danger` |
| Focus ring | `focus:ring-4 focus:ring-indigo-100` | on all interactive elements |
| Checkbox checked | `text-indigo-600 focus:ring-indigo-500` | — |
| Text primary | `#0F172A` | `text-slate-900` |
| Text secondary | `#64748B` | `text-slate-500` |
| Borders | `#E2E8F0` | `border-slate-200` |

### Admin Component Standards (quick reference)

**Buttons**

```blade
{{-- Primary --}}
<button class="admin-btn-primary">Save</button>

{{-- Secondary --}}
<button class="admin-btn-secondary">Cancel</button>

{{-- Danger --}}
<button class="admin-btn-danger">Delete</button>
```

**Card / Panel**

```blade
<div class="admin-card">
  <div class="admin-card-header">
    <h3 class="text-base font-bold text-slate-800">Panel Title</h3>
  </div>
  <div class="admin-card-body text-slate-600">{{ $content }}</div>
</div>
```

**Form group**

```blade
<div class="mb-4">
  <label for="title" class="admin-form-label">Title <span class="text-red-500" aria-hidden="true">*</span></label>
  <input id="title" name="title" type="text"
    class="admin-input @error('title') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror">
  @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
```

**Table row**

```blade
<tr class="admin-table-row">
  <td class="px-6 py-4 text-sm text-slate-900">{{ $item->name }}</td>
  <td class="px-6 py-4"><span class="admin-badge-success">Active</span></td>
</tr>
```

**Sidebar menu item**

```blade
<a href="{{ $url }}"
  class="admin-sidebar__link {{ $active ? 'admin-sidebar__link--active' : '' }}"
  @if($active) aria-current="page" @endif>
  <span class="admin-sidebar__icon">{!! $icon !!}</span>
  <span>{{ $label }}</span>
</a>
```

### Admin UX Principles (preserved)

- Clear create/edit/list flows · Obvious Save/Cancel · Helpful validation messages
- Filters, search, status badges, pagination for lists
- Confirmation before destructive actions
- Compact repeatable modules (prices, images, features, FAQs, itineraries)
- Tabs/sections for long product-detail forms
- User always knows what to do next
- Do NOT mix public luxury styling into admin styling (and vice versa)

Admin UX principles (preserved): clear create/edit/list flows · obvious Save/Cancel ·
helpful validation messages · filters, search, status badges, pagination for lists ·
confirmation before destructive actions · compact repeatable modules · use tabs/sections
for long product-detail forms · user always knows what to do next.

---

## 4. Frontend Design System (Public Site)

### Aesthetic Direction

| Aspect | Style |
|--------|-------|
| Mood | Luxury travel, premium, sophisticated |
| Base | White backgrounds, generous white space |
| Accent | Gold (#D4AF37) for brand elements |
| Layout | Hero-driven, spacious sections, max-w-7xl |
| Density | Spacious — breathing room, not cramped |

### Frontend Color Palette

| Role | Hex | Tailwind |
|------|-----|---------|
| Primary Black | #000000 | text-black, bg-black |
| Accent Gold | #D4AF37 | text-amber-400 / custom |
| Background White | #FFFFFF | bg-white |
| Soft Gray BG | #F9FAFB | bg-gray-50 |
| Text Dark | #1F2937 | text-gray-800 |
| Text Gray | #6B7280 | text-gray-500 |
| Border Light | #E5E7EB | border-gray-200 |
| CTA Green | #10B981 | bg-green-500 |

### Frontend Component Standards

- Full code examples: `ai/skills/frontend-design-skill.md` (Sections 4–5)
- Copy-paste templates: `ai/skills/COMPONENT-LIBRARY.md`

Quick reference — primary CTA button:

```blade
<button class="bg-amber-400 hover:bg-amber-500 text-gray-900 font-bold py-3 px-6 rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2">
  {{ $cta }}
</button>
```

---

## 5. Template Scaling Guide

### Multi-Template Strategy

New page templates are assembled from shared tokens + the component library — never from
new colors or spacing. Define the sections, drop in component patterns, test responsive.

### Template Types

| Template | Sections |
|----------|---------|
| Home | Hero + Features + Products + Reviews + CTA |
| Destination | Hero + Overview + Gallery + Activities + Reviews + CTA |
| Activity Listing | Filter Bar + Grid + Pagination |
| Single Activity | Hero + Detail + Gallery + Booking Form + Reviews |
| Contact | Hero + Form + Map + Info |

All templates use: same tokens, same spacing, same component library. Test every template
at 375px, 768px, 1024px, and 1440px before completion.

---

## 6. Dark/Light Mode Strategy

- **Frontend:** Light mode only.
- **Admin:** Dark mode default.

Tailwind config: `darkMode: 'class'`. Toggle by adding/removing the `dark` class on
`<html>`. Do not introduce a light admin theme or a dark public theme without explicit
approval.

---

## 7. Accessibility Standards (WCAG AA)

- **Contrast:** text minimum 4.5:1, UI components 3:1.
- **Semantic HTML:** `header`, `nav`, `main`, `article`, `aside`, `footer` — not div soup.
- **Keyboard:** all interactive elements reachable and operable by keyboard.
- **Focus:** visible focus ring on every interactive element
  (`focus:ring-2 focus:ring-amber-400 focus:ring-offset-2`).
- **ARIA:** `aria-label` on icon-only buttons, `aria-describedby` for form errors.
- **Forms:** every input has an associated `<label>`.
- **Color:** never rely on color alone — pair with icon or text.
- **Alt text:** descriptive, not "image of…".

---

## 8. Performance Principles

- **Images:** WebP preferred with JPG fallback; `object-cover`; fixed aspect ratios to
  prevent layout shift.
- **Lazy loading:** `loading="lazy"` on every below-the-fold image; hero images load eagerly.
- **Minimal JS:** prefer CSS transitions; reach for Alpine.js only for small interactions.
- **No queries in Blade:** controllers/services prepare display-ready data.
- **Stable dimensions:** cards, images, buttons, badges, form controls keep consistent size.

---

## 9. Quick Reference

### Tailwind Class Reference

| Need | Class |
|------|-------|
| Section wrapper | `py-20 px-6 max-w-7xl mx-auto` |
| Responsive grid | `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8` |
| Primary CTA (frontend) | `bg-amber-400 hover:bg-amber-500 text-gray-900 font-bold py-3 px-6 rounded-md` |
| Card | `bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300` |
| Heading h2 | `text-4xl font-serif font-bold text-gray-900` |
| Body text | `text-base text-gray-700 leading-relaxed` |
| Muted text | `text-sm text-gray-500` |
| Focus ring | `focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2` |
| Admin panel | `bg-gray-800 border border-gray-700 rounded-lg p-6` |
| Admin input | `bg-gray-900 border-gray-700 text-gray-100 focus:border-amber-400` |
| Image (lazy) | `w-full h-full object-cover` + `loading="lazy"` |
