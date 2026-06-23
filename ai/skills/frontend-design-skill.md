# Frontend Design Skill (Consolidated)

> Consolidated from `frontend-skill.md`, `uiux-skill.md`, `design-system-skill.md`,
> and `component-library-skill.md`. This is the single source of truth for public
> frontend UI patterns. Admin-dashboard aesthetics live in `DESIGN-SYSTEM.md` §3 and
> `admin-dashboard-skill.md`. Copy-paste component code lives in `ai/skills/COMPONENT-LIBRARY.md`.

## Required References
- `AGENTS.md`
- `DESIGN-SYSTEM.md` (root) — shared design tokens (admin + frontend)
- `ai/skills/COMPONENT-LIBRARY.md` — copy-paste component catalog
- `ai/guidelines/04-frontend-uiux-standard.md`
- `ai/guidelines/05-admin-dashboard-cms-builder.md`
- `ai/guidelines/07-seo-ai-discovery.md`
- `ai/guidelines/08-performance-optimization.md`

---

## Section 1: Main Goal

Build premium, modern, fast, and responsive frontend UI for Bintan Prestige.
Make the public site feel premium and the admin dashboard feel clear, efficient, and
trustworthy.

---

## Section 2: Brand Direction

- Luxury travel, premium experience
- Premium taxi/tour/activity experience
- Color palette: Black (#000), Gold (#D4AF37), White (#FFF), Soft Gray (#F9FAFB)
- Typography: Serif headings (Georgia/Garamond, or brand fonts Cinzel),
  Sans-serif body (Segoe UI/Roboto, or brand font Montserrat)
- Spacious layout, elegant animation, smooth interaction
- Clean black, gold, white, soft gray
- Elegant typography

---

## Section 3: Design Tokens (CSS Variables)

```css
/* Colors */
--color-primary-black: #000000;
--color-accent-gold: #D4AF37;
--color-white: #FFFFFF;
--color-gray-50: #F9FAFB;
--color-gray-100: #F3F4F6;
--color-gray-200: #E5E7EB;
--color-gray-300: #D1D5DB;
--color-gray-500: #6B7280;
--color-gray-700: #374151;
--color-gray-800: #1F2937;
--color-gray-900: #111827;
--color-success: #10B981;
--color-warning: #F59E0B;
--color-danger: #EF4444;
--color-info: #3B82F6;

/* Spacing (4px base unit) */
--space-xs: 4px;   /* p-1 */
--space-sm: 8px;   /* p-2 */
--space-md: 16px;  /* p-4 */
--space-lg: 24px;  /* p-6 */
--space-xl: 40px;  /* p-10 */
--space-2xl: 80px; /* p-20 */

/* Section spacing */
Section top/bottom: py-20 (80px)
Cards gap: gap-8 (24px)
Card padding: p-6 (24px)

/* Typography scale */
h1: text-6xl font-serif font-bold leading-tight
h2: text-4xl font-serif font-bold
h3: text-2xl font-serif font-bold
body: text-base text-gray-700 leading-relaxed
small: text-sm text-gray-500

/* Shadows */
--shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
--shadow-md: 0 4px 6px rgba(0,0,0,0.1);
--shadow-lg: 0 10px 15px rgba(0,0,0,0.15);

/* Border radius */
--radius-sm: rounded-sm (4px)
--radius-md: rounded-md (8px)
--radius-lg: rounded-lg (12px)

/* Transitions */
Fast: duration-150
Normal: duration-300
Slow: duration-500
```

---

## Section 4: Component Patterns

Hierarchy:

```
Atoms     → Button, Badge, Input, Label, Link, Spinner
Molecules → Card, Form Group, Alert, Breadcrumb, Menu Item
Organisms → Hero, Product Grid, CTA Banner, Review Section, Footer
```

Button variants:

```blade
Primary:   bg-amber-400 hover:bg-amber-500 text-gray-900 font-bold py-3 px-6 rounded-md transition-colors duration-200
Secondary: border-2 border-gray-800 hover:border-amber-400 text-gray-800 hover:text-amber-400 font-bold py-3 px-6 rounded-md transition-colors duration-200
Danger:    bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-6 rounded-md transition-colors duration-200
```

Card (Product/Destination):

```blade
<div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow duration-300">
  <div class="relative overflow-hidden h-48 bg-gray-200">
    <img src="{{ $image }}" alt="{{ $title }}" loading="lazy"
      class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
  </div>
  <div class="p-6">
    <h3 class="text-xl font-serif font-bold text-gray-900 mb-2">{{ $title }}</h3>
    <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ $description }}</p>
    <button class="w-full bg-amber-400 hover:bg-amber-500 text-gray-900 font-bold py-3 rounded-md transition-colors">
      {{ $cta }}
    </button>
  </div>
</div>
```

Hero Section:

```blade
<section class="relative h-screen flex items-center justify-center overflow-hidden">
  <div class="absolute inset-0 bg-cover bg-center"
    style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.5)), url('{{ $image }}');"></div>
  <div class="relative z-10 text-center text-white px-6 max-w-2xl">
    <h1 class="text-6xl md:text-7xl font-serif font-bold mb-6">{{ $title }}</h1>
    <p class="text-lg md:text-xl text-gray-200 mb-8">{{ $subtitle }}</p>
    <div class="flex gap-4 justify-center flex-wrap">
      <button class="bg-amber-400 hover:bg-amber-500 text-gray-900 font-bold py-4 px-8 rounded-md transition-colors">
        {{ $cta_primary }}
      </button>
    </div>
  </div>
</section>
```

CTA Banner:

```blade
<section class="bg-gray-900 text-white py-16 px-6">
  <div class="max-w-4xl mx-auto text-center">
    <h2 class="text-4xl font-serif font-bold mb-6">{{ $title }}</h2>
    <p class="text-gray-300 text-lg mb-8">{{ $description }}</p>
    <button class="bg-amber-400 hover:bg-amber-500 text-gray-900 font-bold py-4 px-8 rounded-md text-lg transition-colors">
      {{ $cta }}
    </button>
  </div>
</section>
```

---

## Section 5: Layout Patterns

Content Section (standard spacing):

```blade
<section class="py-20 px-6 max-w-7xl mx-auto">
  <div class="text-center mb-16">
    <h2 class="text-4xl font-serif font-bold text-gray-900 mb-4">{{ $title }}</h2>
    <p class="text-gray-600 text-lg">{{ $subtitle }}</p>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    @foreach($items as $item)
      {{-- card here --}}
    @endforeach
  </div>
</section>
```

Image + Text (2-column):

```blade
<section class="py-20 px-6">
  <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
    <div class="order-2 lg:order-1">
      <img src="{{ $image }}" alt="{{ $title }}" loading="lazy"
        class="w-full h-auto rounded-lg shadow-lg">
    </div>
    <div class="order-1 lg:order-2">
      <h2 class="text-4xl font-serif font-bold text-gray-900 mb-6">{{ $title }}</h2>
      <p class="text-gray-700 text-lg leading-relaxed mb-8">{{ $content }}</p>
    </div>
  </div>
</section>
```

Visual layout rules (preserved):
- Use consistent spacing.
- Use max-width containers.
- Use subtle shadows.
- Use rounded cards.
- Use smooth hover transitions.
- Avoid generic AI layout.

---

## Section 6: Responsive Rules (Mobile-First)

```
Mobile (default): 375px — single column, stacked layout
Tablet (md:):     768px — 2 columns where applicable
Desktop (lg:):    1024px — full layout
Wide (xl:):       1280px — max-width container applies

Grid pattern:
grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8

Touch targets: minimum py-3 px-4 for all interactive elements (48px+ height)
```

---

## Section 7: Interaction & Animation

```
All hover states: required on every interactive element
Button hover: bg color darken, duration-200
Card hover: shadow-md → shadow-lg, duration-300
Image hover: scale-105, duration-300
Link hover: color change, duration-200

Focus states (accessibility): focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2
Reduced motion: use @media (prefers-reduced-motion: reduce) for animated sections
```

Lightweight animation that does not slow the page. Use CSS transitions + Alpine.js only.

---

## Section 8: Required Frontend Components

Merged from all source files (deduplicated):

- Hero
- Booking CTA / Booking-WhatsApp CTA (strong, visible placement)
- CTA banner
- Product cards
- Destination cards
- Review / testimonial section
- FAQ section / FAQ block
- Breadcrumb
- Badge
- Button
- Image frame
- Menu item
- Empty state
- Loading state
- Error state
- Disabled state

Public detail pages must surface: prices, duration, meeting point, itinerary, notes, and FAQs.

---

## Section 9: Image Handling

```
Hero images: load immediately (no lazy)
Below-fold: loading="lazy" always
Format: WebP preferred, JPG fallback
Aspect ratio: use aspect-video or aspect-square for consistent sizing
Object fit: object-cover for filling containers

Picture element (optimal):
<picture>
  <source srcset="image.webp" type="image/webp">
  <img src="image.jpg" alt="Descriptive alt text" loading="lazy" class="w-full h-full object-cover">
</picture>
```

Preserve stable dimensions for cards, images, buttons, badges, and form controls
(prevent layout shift).

---

## Section 10: Accessibility (WCAG AA)

```
Contrast: text minimum 4.5:1, UI components 3:1
Alt text: descriptive, not "image of..."
Semantic HTML: header, nav, main, article, aside, footer (not div soup)
Labels: every input must have associated <label>
Focus: all interactive elements keyboard accessible
ARIA: aria-label on icon-only buttons, aria-describedby for form errors
Color: never rely on color alone (icon + color, text + color)
```

Do not hide important content behind JavaScript-only interactions.

---

## Section 11: Template Scaling

```
When creating a new template:
1. Define sections (Hero, Grid, CTA, etc.)
2. Use design tokens from Section 3 (no new colors/spacing)
3. Use component patterns from Section 4
4. Use layout patterns from Section 5
5. Test at 375px, 768px, 1024px, 1440px

All templates share: same color palette, same spacing scale, same typography hierarchy
```

---

## Section 12: Existing Rules (Preserved)

From `frontend-skill.md`:
- Use TailwindCSS only.
- Use Blade components and partials.
- No inline CSS unless unavoidable (exception: dynamic background images).
- Mobile first.
- Keep layout clean and readable.
- Use prepared backend data only (no queries in Blade).
- Use semantic HTML.
- Optimize image rendering.
- Use lazy loading for below-the-fold images.
- Avoid heavy JavaScript.

From `uiux-skill.md` (Public Frontend UX):
- Mobile-first layout.
- Clear travel-focused hierarchy.
- Premium spacing and typography.
- Strong booking/WhatsApp CTA placement.
- Visible prices, duration, meeting point, itinerary, notes, and FAQs.
- Clear image alt text and semantic headings.

From `component-library-skill.md` (Component Rules):
- Prefer reusable Blade components or partials for repeated UI.
- Keep components driven by backend-prepared data.
- Use semantic HTML and accessible labels.
- Include empty, loading, error, and disabled states where relevant.
- Keep styling in TailwindCSS.

From `design-system-skill.md` (brand fonts):
- Cinzel for headings, Montserrat for body (when brand fonts are loaded;
  otherwise serif/sans-serif fallbacks per Section 2).

### Safety (preserved from uiux-skill.md)
- Do not rebuild UI from zero without approval.
- **Do not mix public luxury styling into admin CMS styling** (and vice versa).
- Do not hide important content behind JavaScript-only interactions.
- Do not let text, buttons, images, or cards overlap on mobile or desktop.
- Do not remove existing UI sections without approval.
- Do not hardcode CMS content.
- Check mobile layout and text overflow before completion.

---

## Section 13: Common Mistakes (Avoid These)

```
❌ Hardcoded colors (use Tailwind tokens)
❌ Arbitrary spacing (use 4px scale: p-1, p-2, p-4, p-6, p-10, p-20)
❌ Missing mobile layout (always grid-cols-1 first)
❌ Images without lazy loading (use loading="lazy" below fold)
❌ Missing focus states (add focus:ring-2 to all interactive elements)
❌ Wrong heading hierarchy (h1 → h2 → h3, never skip)
❌ Generic AI styling (be intentional with every design decision)
❌ div soup (use semantic HTML tags)
❌ Inline CSS (use Tailwind classes, except for dynamic background images)
❌ Heavy JS for simple interactions (use CSS transitions + Alpine.js only)
```
