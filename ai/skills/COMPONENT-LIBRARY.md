# Component Library — Bintan Prestige CMS

## What is This File?

A copy-paste catalog of reusable Blade/Tailwind component patterns. Instead of
reinventing styling each time, find the component you need, copy the code, adapt the
content, and ship. Every component here uses the tokens defined in `DESIGN-SYSTEM.md`,
so anything assembled from this catalog stays visually consistent by construction.

Purpose:
- **Find → copy → adapt → done.** No inventing new colors/spacing per page.
- **Consistency enforcement.** All components share the same palette, spacing, and type scale.
- **Rapid scaling.** New pages and templates compose from the same building blocks.

## How to Use

1. **Find** the component by hierarchy (Atom / Molecule / Organism / Admin).
2. **Copy** the Blade snippet.
3. **Adapt** the content (`{{ $var }}`, slot content, props) — keep the classes.
4. **Don't invent new styles.** If a token isn't here, check `DESIGN-SYSTEM.md` before
   adding anything. New colors/spacing require approval.

> All code uses Tailwind CSS classes only. The only allowed inline style is a dynamic
> `background-image` for hero/section backgrounds. No queries in Blade — pass prepared data.

## Component Hierarchy

```
Atoms     → Button, Badge, Input, Label, Link, Spinner
Molecules → Form Group, Card, Alert, Breadcrumb, Menu Item
Organisms → Hero, Product Grid, CTA Banner, Review Section, Navigation Bar, Footer
Admin     → Button, Card/Panel, Form Group, Table Row, Sidebar Item, Toast
```

---

## ATOMS

### Button (Primary, Secondary, Danger, Disabled)

```blade
{{-- Primary --}}
<button class="bg-amber-400 hover:bg-amber-500 text-gray-900 font-bold py-3 px-6 rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2">
  {{ $label }}
</button>

{{-- Secondary --}}
<button class="border-2 border-gray-800 hover:border-amber-400 text-gray-800 hover:text-amber-400 font-bold py-3 px-6 rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2">
  {{ $label }}
</button>

{{-- Danger --}}
<button class="bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-6 rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2">
  {{ $label }}
</button>

{{-- Disabled --}}
<button disabled class="bg-gray-200 text-gray-400 font-bold py-3 px-6 rounded-md cursor-not-allowed">
  {{ $label }}
</button>
```

### Badge (Default, Success, Warning, Info)

```blade
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Default</span>
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Success</span>
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Warning</span>
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Info</span>
```

### Input Field (Default, Error State, Disabled)

```blade
{{-- Default --}}
<input type="text" placeholder="Your name"
  class="w-full border border-gray-300 rounded-md px-3 py-3 text-gray-800 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">

{{-- Error state --}}
<input type="text" aria-invalid="true" aria-describedby="name-error"
  class="w-full border border-red-400 rounded-md px-3 py-3 text-gray-800 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500">

{{-- Disabled --}}
<input type="text" disabled value="Locked"
  class="w-full border border-gray-200 bg-gray-50 text-gray-400 rounded-md px-3 py-3 cursor-not-allowed">
```

### Label (Default, Required)

```blade
<label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>

{{-- Required --}}
<label for="email" class="block text-sm font-medium text-gray-700 mb-1">
  Email <span class="text-red-500" aria-hidden="true">*</span>
</label>
```

### Link (Default, With Chevron)

```blade
<a href="{{ $url }}" class="text-amber-500 hover:text-amber-600 font-medium transition-colors">{{ $label }}</a>

{{-- With chevron --}}
<a href="{{ $url }}" class="inline-flex items-center gap-1 text-amber-500 hover:text-amber-600 font-medium transition-colors group">
  {{ $label }}
  <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
</a>
```

### Spinner / Loading

```blade
<svg class="animate-spin h-6 w-6 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" role="status" aria-label="Loading">
  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
</svg>
```

---

## MOLECULES

### Form Group (Label + Input + Error + Helper)

```blade
<div class="mb-4">
  <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">
    {{ $label }}@if($required ?? false) <span class="text-red-500" aria-hidden="true">*</span>@endif
  </label>
  <input id="{{ $name }}" name="{{ $name }}" type="{{ $type ?? 'text' }}" value="{{ old($name) }}"
    @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
    class="w-full border rounded-md px-3 py-3 text-gray-800 focus:outline-none focus:ring-1 @error($name) border-red-400 focus:border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-amber-400 focus:ring-amber-400 @enderror">
  @error($name)
    <p id="{{ $name }}-error" class="mt-1 text-sm text-red-500">{{ $message }}</p>
  @else
    @if(isset($helper))<p class="mt-1 text-sm text-gray-500">{{ $helper }}</p>@endif
  @enderror
</div>
```

### Card (Product, Destination, Review variants)

```blade
{{-- Product / Destination card --}}
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

{{-- Review / testimonial card --}}
<figure class="bg-gray-50 rounded-lg p-6 shadow-sm">
  <div class="flex items-center gap-1 mb-3 text-amber-400" aria-label="{{ $rating }} out of 5 stars">
    @for($i = 0; $i < 5; $i++)
      <svg class="w-4 h-4 {{ $i < $rating ? 'fill-current' : 'text-gray-300 fill-current' }}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
    @endfor
  </div>
  <blockquote class="text-gray-700 leading-relaxed mb-4">"{{ $quote }}"</blockquote>
  <figcaption class="text-sm font-semibold text-gray-900">{{ $author }}</figcaption>
</figure>
```

### Alert Box (Info, Success, Warning, Error)

```blade
<div role="alert" class="flex items-start gap-3 rounded-md border-l-4 p-4 border-blue-500 bg-blue-50 text-blue-800">
  <span class="font-medium">Info:</span><span>{{ $message }}</span>
</div>
<div role="alert" class="flex items-start gap-3 rounded-md border-l-4 p-4 border-green-500 bg-green-50 text-green-800">
  <span class="font-medium">Success:</span><span>{{ $message }}</span>
</div>
<div role="alert" class="flex items-start gap-3 rounded-md border-l-4 p-4 border-amber-500 bg-amber-50 text-amber-800">
  <span class="font-medium">Warning:</span><span>{{ $message }}</span>
</div>
<div role="alert" class="flex items-start gap-3 rounded-md border-l-4 p-4 border-red-500 bg-red-50 text-red-800">
  <span class="font-medium">Error:</span><span>{{ $message }}</span>
</div>
```

### Breadcrumb

```blade
<nav aria-label="Breadcrumb" class="text-sm text-gray-500">
  <ol class="flex items-center gap-2 flex-wrap">
    @foreach($crumbs as $crumb)
      <li class="flex items-center gap-2">
        @if(!$loop->last)
          <a href="{{ $crumb['url'] }}" class="hover:text-amber-500 transition-colors">{{ $crumb['label'] }}</a>
          <span aria-hidden="true">/</span>
        @else
          <span class="text-gray-800 font-medium" aria-current="page">{{ $crumb['label'] }}</span>
        @endif
      </li>
    @endforeach
  </ol>
</nav>
```

### Menu Item (Default, Active State)

```blade
<a href="{{ $url }}"
  class="px-4 py-2 font-medium transition-colors {{ $active ? 'text-amber-500' : 'text-gray-700 hover:text-amber-500' }}"
  @if($active) aria-current="page" @endif>
  {{ $label }}
</a>
```

---

## ORGANISMS

### Hero Section

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

### Product Grid (3-column responsive)

```blade
<section class="py-20 px-6 max-w-7xl mx-auto">
  <div class="text-center mb-16">
    <h2 class="text-4xl font-serif font-bold text-gray-900 mb-4">{{ $title }}</h2>
    <p class="text-gray-600 text-lg">{{ $subtitle }}</p>
  </div>
  @if($items->isEmpty())
    <p class="text-center text-gray-500">No items available yet.</p>
  @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      @foreach($items as $item)
        <x-product-card :title="$item->title" :image="$item->image_url"
          :description="$item->excerpt" cta="View Details" />
      @endforeach
    </div>
  @endif
</section>
```

### CTA Banner

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

### Review Section

```blade
<section class="py-20 px-6 bg-gray-50">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-16">
      <h2 class="text-4xl font-serif font-bold text-gray-900 mb-4">{{ $title }}</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      @foreach($reviews as $review)
        <x-review-card :rating="$review->rating" :quote="$review->body" :author="$review->author" />
      @endforeach
    </div>
  </div>
</section>
```

### Navigation Bar

```blade
<nav x-data="{ open: false }" class="bg-white shadow-sm sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-6 flex items-center justify-between h-16">
    <a href="/" class="text-2xl font-serif font-bold text-gray-900">{{ $brand }}</a>
    <div class="hidden md:flex items-center gap-2">
      @foreach($menu as $item)
        <x-menu-item :url="$item['url']" :label="$item['label']" :active="$item['active']" />
      @endforeach
    </div>
    <button @click="open = !open" class="md:hidden p-2 text-gray-700" aria-label="Toggle menu" :aria-expanded="open">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
  </div>
  <div x-show="open" x-cloak class="md:hidden border-t border-gray-200 px-6 py-4 flex flex-col gap-2">
    @foreach($menu as $item)
      <x-menu-item :url="$item['url']" :label="$item['label']" :active="$item['active']" />
    @endforeach
  </div>
</nav>
```

### Footer

```blade
<footer class="bg-gray-900 text-gray-300 py-16 px-6">
  <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8">
    <div>
      <h3 class="text-xl font-serif font-bold text-white mb-4">{{ $brand }}</h3>
      <p class="text-sm text-gray-400">{{ $tagline }}</p>
    </div>
    @foreach($columns as $column)
      <div>
        <h4 class="text-sm font-semibold text-white uppercase tracking-wide mb-4">{{ $column['heading'] }}</h4>
        <ul class="space-y-2">
          @foreach($column['links'] as $link)
            <li><a href="{{ $link['url'] }}" class="text-sm hover:text-amber-400 transition-colors">{{ $link['label'] }}</a></li>
          @endforeach
        </ul>
      </div>
    @endforeach
  </div>
  <div class="max-w-7xl mx-auto border-t border-gray-700 mt-12 pt-6 text-sm text-gray-500 text-center">
    &copy; {{ date('Y') }} {{ $brand }}. All rights reserved.
  </div>
</footer>
```

---

## ADMIN COMPONENTS

### Admin Button (Primary, Secondary, Danger + size variants)

```blade
{{-- Primary --}}
<button class="bg-amber-400 hover:bg-amber-500 text-gray-900 font-semibold py-2 px-4 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-gray-900">Save</button>

{{-- Secondary --}}
<button class="bg-gray-700 hover:bg-gray-600 text-gray-100 font-semibold py-2 px-4 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-amber-400">Cancel</button>

{{-- Danger --}}
<button class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-red-400">Delete</button>

{{-- Small --}}
<button class="bg-amber-400 hover:bg-amber-500 text-gray-900 font-semibold py-1 px-2.5 text-sm rounded transition-colors">Edit</button>

{{-- Large --}}
<button class="bg-amber-400 hover:bg-amber-500 text-gray-900 font-semibold py-3 px-6 text-lg rounded-md transition-colors">Publish</button>
```

### Admin Card / Panel

```blade
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
  <div class="flex items-center justify-between mb-4">
    <h3 class="text-gray-100 font-semibold text-lg">{{ $title }}</h3>
    {{ $actions ?? '' }}
  </div>
  <div class="text-gray-300">{{ $slot }}</div>
</div>
```

### Admin Form Group

```blade
<div class="mb-4">
  <label for="{{ $name }}" class="block text-sm font-medium text-gray-300 mb-1">
    {{ $label }}@if($required ?? false) <span class="text-red-400" aria-hidden="true">*</span>@endif
  </label>
  <input id="{{ $name }}" name="{{ $name }}" type="{{ $type ?? 'text' }}" value="{{ old($name, $value ?? '') }}"
    class="w-full bg-gray-900 border border-gray-700 text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
  @error($name)<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
</div>
```

### Admin Table Row

```blade
<tr class="border-b border-gray-700 hover:bg-gray-700/40 transition-colors">
  <td class="px-4 py-3 text-gray-100">{{ $item->name }}</td>
  <td class="px-4 py-3">
    <span class="inline-flex px-2 py-1 text-xs rounded-full {{ $item->active ? 'bg-green-500/20 text-green-400' : 'bg-gray-600/40 text-gray-400' }}">
      {{ $item->active ? 'Active' : 'Inactive' }}
    </span>
  </td>
  <td class="px-4 py-3 text-right">
    <a href="{{ route('admin.items.edit', $item) }}" class="text-amber-400 hover:text-amber-300 text-sm font-medium">Edit</a>
  </td>
</tr>
```

### Sidebar Menu Item

```blade
<a href="{{ $url }}"
  class="flex items-center gap-3 px-4 py-2 rounded-md transition-colors {{ $active ? 'bg-gray-700 text-amber-400' : 'text-gray-300 hover:bg-gray-700 hover:text-amber-400' }}"
  @if($active) aria-current="page" @endif>
  <span class="w-5 h-5">{!! $icon !!}</span>
  <span>{{ $label }}</span>
</a>
```

### Toast Notification

```blade
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-cloak
  role="status" aria-live="polite"
  class="fixed bottom-6 right-6 z-50 flex items-center gap-3 bg-gray-800 border border-gray-700 text-gray-100 rounded-lg px-4 py-3 shadow-lg">
  <span class="w-2 h-2 rounded-full bg-green-400"></span>
  <span>{{ $message }}</span>
  <button @click="show = false" class="ml-2 text-gray-400 hover:text-gray-200" aria-label="Dismiss">&times;</button>
</div>
```

---

## Usage Patterns

### Pattern 1: Blade Component file

Create `resources/views/components/product-card.blade.php`, paste the card markup, then use
it as `<x-product-card :title="$item->title" :image="$item->image_url" :description="$item->excerpt" cta="View Details" />`.

### Pattern 2: Passing props / slots

```blade
{{-- components/admin/panel.blade.php --}}
@props(['title'])
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
  <h3 class="text-gray-100 font-semibold text-lg mb-4">{{ $title }}</h3>
  <div class="text-gray-300">{{ $slot }}</div>
</div>

{{-- usage --}}
<x-admin.panel title="SEO Settings">
  <x-admin.form-group name="meta_title" label="Meta Title" :required="true" />
</x-admin.panel>
```

### Pattern 3: Admin component with PHP props

```blade
{{-- components/admin/sidebar-item.blade.php --}}
@props(['url', 'label', 'icon' => '', 'active' => false])
<a href="{{ $url }}"
  class="flex items-center gap-3 px-4 py-2 rounded-md transition-colors {{ $active ? 'bg-gray-700 text-amber-400' : 'text-gray-300 hover:bg-gray-700 hover:text-amber-400' }}"
  @if($active) aria-current="page" @endif>
  <span class="w-5 h-5">{!! $icon !!}</span>
  <span>{{ $label }}</span>
</a>

{{-- usage --}}
<x-admin.sidebar-item url="{{ route('admin.pages.index') }}" label="Pages"
  :active="request()->routeIs('admin.pages.*')" />
```

---

## Adding New Components

1. **Choose hierarchy** — Atom, Molecule, Organism, or Admin.
2. **Create the Blade file** under `resources/views/components/` (or `components/admin/`).
3. **Use only existing tokens** from `DESIGN-SYSTEM.md` — no new colors/spacing.
4. **Add states** — empty, loading, error, disabled where relevant.
5. **Add it to this catalog** under the right section with a working snippet.
6. **Test responsive** at 375px / 768px / 1024px / 1440px, and check focus + alt text.
7. **Commit** focused on one concern.
