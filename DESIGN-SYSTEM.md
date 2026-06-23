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

### Aesthetic Direction

| Aspect | Style |
|--------|-------|
| Base | Dark theme (#1F2937 background) |
| Accent | Amber/Gold (#FBBF24) for primary actions |
| Layout | Sidebar (25%) + Main content (55%) + Panel (20%) |
| Density | Medium — efficient for power users |
| Feedback | Instant: toast, loading states, error messages |

### Admin Color Palette

| Role | Hex | Tailwind |
|------|-----|---------|
| Sidebar/Header | #1F2937 | bg-gray-800 |
| Content Background | #111827 | bg-gray-900 |
| Primary Action | #FBBF24 | bg-amber-400 |
| Success | #10B981 | bg-green-500 |
| Danger | #EF4444 | bg-red-500 |
| Border | #374151 | border-gray-700 |
| Text Primary | #F3F4F6 | text-gray-100 |
| Text Secondary | #D1D5DB | text-gray-300 |

### Admin Component Standards

**Buttons**

```blade
{{-- Primary --}}
<button class="bg-amber-400 hover:bg-amber-500 text-gray-900 font-semibold py-2 px-4 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-gray-900">Save</button>

{{-- Secondary --}}
<button class="bg-gray-700 hover:bg-gray-600 text-gray-100 font-semibold py-2 px-4 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-amber-400">Cancel</button>

{{-- Danger --}}
<button class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-red-400">Delete</button>
```

**Card / Panel**

```blade
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
  <h3 class="text-gray-100 font-semibold text-lg mb-4">Panel Title</h3>
  <div class="text-gray-300">{{ $content }}</div>
</div>
```

**Form group**

```blade
<div class="mb-4">
  <label for="title" class="block text-sm font-medium text-gray-300 mb-1">Title <span class="text-red-400">*</span></label>
  <input id="title" type="text"
    class="w-full bg-gray-900 border border-gray-700 text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
  @error('title')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
</div>
```

**Table row**

```blade
<tr class="border-b border-gray-700 hover:bg-gray-700/40 transition-colors">
  <td class="px-4 py-3 text-gray-100">{{ $item->name }}</td>
  <td class="px-4 py-3"><span class="inline-flex px-2 py-1 text-xs rounded-full bg-green-500/20 text-green-400">Active</span></td>
</tr>
```

**Sidebar menu item**

```blade
<a href="{{ $url }}"
  class="flex items-center gap-3 px-4 py-2 rounded-md text-gray-300 hover:bg-gray-700 hover:text-amber-400 transition-colors {{ $active ? 'bg-gray-700 text-amber-400' : '' }}">
  <span class="w-5 h-5">{!! $icon !!}</span>
  <span>{{ $label }}</span>
</a>
```

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
