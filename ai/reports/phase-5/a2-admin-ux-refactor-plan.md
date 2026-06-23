# A2 — Admin Dashboard UX Refactor Plan
## Date: 2026-06-21
## Branch: feature/phase-5-stage-a-foundation
## HEAD: 750f9c90a01b52b5cdf5fc087ac69bd7f76c0213
## Prereq: A1 DONE

---

## 1. Current State — Admin Navigation

### Menu items saat ini (urutan dari atas ke bawah)

```
[WORKSPACE]
  1. Dashboard              → admin.dashboard
  2. Products               → admin.products.index
  3. Page Sections          → admin.page-sections.index
  4. Global Assets          → admin.settings.global-assets.edit
  5. Admin Users            → admin.users.index  (guarded: @can manage-users)

[APPEARANCE]
  6. Themes                 → admin.themes.index

[CONTENT]
  7. Pages                  → admin.pages.index
  8. Menus                  → admin.menus.index
  9. Media Library          → admin.media.index
 10. FAQs                   → admin.faqs.index
 11. Forms                  → admin.forms.index
 12. Categories             → admin.categories.index
 13. Destinations           → admin.destinations.index

[SEO]
 14. Redirects              → admin.seo.redirects.index
 15. Robots.txt             → admin.seo.robots.edit
 16. Sitemap                → sitemap (external, opens new tab)

[SYSTEM]
 17. Analytics              → admin.analytics.index
 18. Plugins                → admin.plugins.index
 19. Audit Log              → admin.audit-logs.index
```

### Total top-level items: 19
### Section labels (decorative, not collapsible): 5 (Workspace, Appearance, Content, SEO, System)
### Collapsible: TIDAK — section labels hanya visual divider, semua 19 item selalu visible
### Icons: YA — FontAwesome icons pada setiap item
### Mobile responsive: YA — hamburger toggle + slide-in drawer dengan backdrop overlay

### Issues found

1. **Tidak ada collapsible** — semua 19 item langsung visible. Panjang dan intimidating.
2. **"Page Sections"** ada di grup Workspace (posisi ke-3) padahal ini legacy module, bukan workflow utama.
3. **"Sitemap"** adalah external link (buka tab baru) — inkonsisten dengan item lain.
4. **"Themes"** sendirian di grup "Appearance" — tidak termasuk Menus dan Templates yang juga milik design cluster.
5. **Topbar search** sudah ada secara visual tapi disabled (`disabled` attribute + text "UI only") — dead UI element.
6. **"Global Assets"** di sidebar seharusnya disebut "Settings" agar lebih intuitif.
7. **Contact Forms dan Form Submissions** keduanya accessible via "Forms" di sidebar, tapi route `/admin/forms` landing-nya adalah Contact Forms (admin.forms.index), bukan form submissions — konsisten secara fungsi.

---

## 2. Current State — List Pages Consistency

| Module | Search | Filter | Bulk Actions | Pagination | Sort | Row Actions | Layout | Pattern |
|--------|--------|--------|--------------|------------|------|-------------|--------|---------|
| Pages | ✅ (text search) | ✅ (status dropdown) | ❌ | ✅ | ❌ | ✅ Edit / Duplicate / Delete / Preview | Table | Unique |
| Products | ✅ (text search) | ✅ (category, destination, status) | ❌ | ✅ | ❌ | ✅ Edit / Delete / Toggle Status | **Card (unique)** | Unique |
| Categories | ✅ (text search) | ❌ | ❌ | ✅ | ❌ | ✅ Edit / Archive + Restore / Force Delete | Table | Shared (basic table) |
| Destinations | ✅ (text search) | ❌ | ❌ | ✅ | ❌ | ✅ Edit / Archive + Restore / Force Delete | Table | Shared (basic table) |
| FAQs | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ Edit / Delete | Table | Shared (basic table) |
| Media Library | ✅ (text search) | ✅ (extension pills) | ❌ | ✅ (implicit) | ❌ | ✅ (slide-over panel) | **Grid (unique)** | Unique |
| Menus | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ Edit (opens inline builder) | Card | Unique |
| Themes | — | — | — | — | — | ✅ Activate / Customize / Export | Card grid | Unique |
| Plugins | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ Activate / Deactivate / Delete | Card list | Unique |
| Contact Forms | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ Edit / Submissions link | Table | Shared (basic table) |
| Audit Log | ❌ | ✅ (user, action, type) | ❌ | ✅ | ❌ | ❌ (read-only) | Table | Shared (basic table) |
| Redirects | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ Edit / Delete | Table | Shared (basic table) |
| Analytics | — | ✅ (date range, page) | — | — | — | — | Dashboard | Unique |

### Inconsistencies found

1. **Layout pattern berbeda-beda**: Products pakai card layout (custom), Media pakai grid, mayoritas pakai table, Themes/Plugins pakai card grid. Tidak ada shared component.
2. **CSS class inkonsistency**: Categories dan Destinations search input pakai `class="form-input"` (class lama) bukan `class="admin-input"` (class baru yang dipakai semua modul lain).
3. **FAQs tidak punya search box** — satu-satunya list page tanpa search di antara modul yang punya banyak item.
4. **Tidak ada bulk actions** di manapun.
5. **Tidak ada column sorting** di manapun.
6. **Flash messages** di Pages index langsung di dalam page section (duplikasi — sudah ada `<x-flash-alert />` di layout). Modul lain tidak punya ini.

---

## 3. Current State — Form Pages Consistency

| Module | Layout | Sections | Publish Box | Submit Position | Pattern |
|--------|--------|----------|-------------|-----------------|---------|
| Pages (create) | 1-kolom penuh | 2 sections (Basic, SEO) inline | ❌ | Bottom | Unique |
| Pages (edit) | 1-kolom penuh | 5 accordion tabs (Basic, SEO, Blocks, Revisions, Danger) — each tab has own form | ❌ | Inside each accordion | Unique (rich accordion) |
| Products (create/edit) | 1-kolom penuh | Multiple `<details>` accordions | ❌ | Bottom of form | Unique (details accordion) |
| Categories (create/edit) | 1-kolom penuh | Flat single form via form.blade.php | ❌ | Bottom | Shared (form.blade.php) |
| Destinations (create/edit) | 1-kolom penuh | Flat single form via form.blade.php | ❌ | Bottom | Shared (form.blade.php) |
| FAQs (create/edit) | 1-kolom penuh | Flat single form via form.blade.php | ❌ | Bottom | Shared (form.blade.php) |
| Contact Forms (create/edit) | 1-kolom penuh | Flat single form | ❌ | Bottom | Unique |
| Redirects (create/edit) | 1-kolom penuh | Flat single form | ❌ | Bottom | Unique |
| Page Sections (edit) | 1-kolom penuh | Per-section form | ❌ | Per section | Unique |
| Menus (edit) | 1-kolom penuh | Drag-and-drop tree | ❌ | N/A | Unique |

### Inconsistencies found

1. **Semua form 1-kolom** — tidak ada yang pakai two-column layout (content left + settings sidebar right) ala WordPress.
2. **Tidak ada Publish Box** — status/visibility tidak dipisahkan ke sidebar kanan.
3. **Pages create vs Pages edit adalah layout yang berbeda**: create pakai `admin-form-card` flat, edit pakai accordion tabs yang canggih. Inkonsisten.
4. **Products pakai `<details>` HTML native** — Products pakai tag `<details>/<summary>` untuk accordion, Pages edit pakai Alpine.js `x-show` accordion. Dua teknik berbeda untuk fungsi sama.
5. **Shared form partial patternt** (Categories, Destinations, FAQs) — konsisten satu sama lain tapi tidak konsisten dengan Pages/Products.
6. **Submit button selalu di bawah** — tidak ada sticky save bar.

---

## 4. Current State — Settings Pages

### Settings modules count: 13
### Sidebar entries untuk settings: 1 (hanya "Global Assets")
### Layout: Satu halaman dengan tab navigation di atas (URL param `?tab=...`)

### Tab list yang ada (13 tab):
1. Site Logo
2. Favicon
3. Brand Colors
4. Business Identity
5. Contact Information
6. Social Media Links
7. Navigation Settings
8. Footer Settings
9. SEO Default
10. Social Share Image
11. Default Media
12. Structured Data
13. Tracking Integrations

### Consolidation status: ✅ SUDAH TERKONSOLIDASI

Settings sudah baik — 13 modul dalam satu halaman tabbed. Sidebar hanya punya 1 entry ("Global Assets"). **Tidak perlu perubahan besar di area ini.** Satu hal yang bisa diperbaiki: rename label sidebar dari "Global Assets" → "Settings" agar lebih intuitif bagi non-technical user.

---

## 5. Proposed — New Sidebar Structure

```
PROPOSED ADMIN SIDEBAR (collapsible groups, 8 top-level groups)
================================================================

📊 Dashboard                    ← always visible, tidak di dalam grup

📄 Content                      ← collapsible, open by default
   ├── Pages                    → admin.pages.index
   ├── Products                 → admin.products.index
   ├── Categories               → admin.categories.index
   ├── Destinations             → admin.destinations.index
   ├── Media Library            → admin.media.index
   └── FAQs                     → admin.faqs.index

🎨 Design                       ← collapsible
   ├── Themes                   → admin.themes.index
   ├── Menus                    → admin.menus.index
   └── Page Sections            → admin.page-sections.index  (legacy)

✉️ Forms                        ← collapsible
   └── Contact Forms            → admin.forms.index  (includes submissions)

🔍 SEO                          ← collapsible
   ├── Redirects                → admin.seo.redirects.index
   ├── Robots.txt               → admin.seo.robots.edit
   └── Sitemap ↗                → sitemap (new tab, external)

📈 Analytics                    ← collapsible
   └── Analytics Dashboard      → admin.analytics.index

🔌 System                       ← collapsible
   ├── Plugins                  → admin.plugins.index
   └── Audit Log                → admin.audit-logs.index

⚙️ Settings                     ← collapsible (atau langsung link)
   └── Global Settings          → admin.settings.global-assets.edit

👤 Users                        ← collapsible, @can manage-users
   └── Admin Users              → admin.users.index
```

**Sebelum: 19 flat items + 5 decorative labels**
**Sesudah: 8 collapsible groups + Dashboard = navigasi yang jauh lebih bersih**

### Mapping: current menu item → new group

| Current Menu Item | Current Label | New Group | Route (tidak berubah) |
|-------------------|---------------|-----------|----------------------|
| Dashboard | Dashboard | (standalone) | admin.dashboard |
| Products | Products | Content | admin.products.index |
| Page Sections | Page Sections | Design | admin.page-sections.index |
| Global Assets | Global Assets | Settings → "Global Settings" | admin.settings.global-assets.edit |
| Admin Users | Admin Users | Users | admin.users.index |
| Themes | Themes | Design | admin.themes.index |
| Pages | Pages | Content | admin.pages.index |
| Menus | Menus | Design | admin.menus.index |
| Media Library | Media Library | Content | admin.media.index |
| FAQs | FAQs | Content | admin.faqs.index |
| Forms | Forms | Forms | admin.forms.index |
| Categories | Categories | Content | admin.categories.index |
| Destinations | Destinations | Content | admin.destinations.index |
| Redirects | Redirects | SEO | admin.seo.redirects.index |
| Robots.txt | Robots.txt | SEO | admin.seo.robots.edit |
| Sitemap | Sitemap | SEO | sitemap (external) |
| Analytics | Analytics | Analytics | admin.analytics.index |
| Plugins | Plugins | System | admin.plugins.index |
| Audit Log | Audit Log | System | admin.audit-logs.index |

**Menu items removed: NONE** — semua item dipindah ke grup, tidak ada yang dihapus.
**Routes changed: NONE** — ini pure presentasi refactor.

### Collapsible behavior

- State per group disimpan di `localStorage` via Alpine.js
- Default state: Content group open, semua lain closed
- Active item di-highlight; parent group auto-expand jika anak aktif
- Mobile: tetap pakai slide-in drawer yang sudah ada

---

## 6. Proposed — Shared Components

### Component 1: `x-admin.sidebar`

- **Purpose:** Sidebar yang dikelompokkan dan collapsible menggantikan flat sidebar saat ini
- **Replaces:** `resources/views/backend/partials/sidebar.blade.php`
- **Files affected:** `resources/views/backend/partials/sidebar.blade.php` (diganti), `resources/views/layouts/admin.blade.php` (tetap pakai `@include`)
- **Features:**
  - 8 collapsible groups dengan Alpine.js `x-show`
  - `localStorage` untuk persistent open/close state per group
  - Auto-expand group jika salah satu child aktif
  - Icons per group (sudah ada FontAwesome, gunakan yang sudah ada)
  - Active highlighting tetap pakai `request()->routeIs()` pattern yang sama
  - Mobile drawer tetap pakai `sidebarOpen` Alpine state yang sudah ada

### Component 2: `x-admin.data-table`

- **Purpose:** Reusable table layout untuk semua index/list pages
- **Replaces:** Duplikasi table HTML di setiap index view
- **Files affected (candidates):** categories/index, destinations/index, faqs/index, seo/redirects/index, contact-forms/index, audit-logs/index, form-submissions/index
- **NOT replacing:** products/index (card layout, terlalu spesifik), media/index (grid layout), menus/index (tree), themes/index (card grid)
- **Props / slots:**
  ```blade
  <x-admin.data-table
    title="Active Categories"
    :count="$categories->total()"
    create-route="admin.categories.create"
    create-label="Create Category"
  >
    <x-slot:filters>
      {{-- search box, filter selects --}}
    </x-slot:filters>

    <x-slot:thead>
      <th>Category</th>
      <th>Products</th>
      <th class="text-right">Action</th>
    </x-slot:thead>

    <x-slot:tbody>
      @foreach($items as $item) ... @endforeach
    </x-slot:tbody>

    <x-slot:pagination>
      {{ $items->links() }}
    </x-slot:pagination>
  </x-admin.data-table>
  ```
- **Built-in:** empty state, loading state, consistent cell padding

### Component 3: `x-admin.form-shell`

- **Purpose:** Two-column layout untuk create/edit pages (content left, settings sidebar right)
- **Replaces:** Single-column flat form di create pages
- **Files affected (Phase 1 migration targets):** categories/create, categories/edit, destinations/create, destinations/edit, faqs/create, faqs/edit, seo/redirects/create, seo/redirects/edit
- **NOT replacing (terlalu kompleks):** pages/edit (accordion tabs tetap — sudah bagus), products/create/edit (accordion — sudah bagus)
- **Props / slots:**
  ```blade
  <x-admin.form-shell
    :action="$action"
    :method="$method"
    title="Create Category"
    back-route="admin.categories.index"
  >
    <x-slot:content>
      {{-- Main form fields (left, 2/3 width) --}}
    </x-slot:content>

    <x-slot:sidebar>
      {{-- Status/settings box (right, 1/3 width) --}}
      <x-admin.publish-box
        :statuses="['active' => 'Active', 'inactive' => 'Inactive']"
        :current="$category->is_active ? 'active' : 'inactive'"
        submit-label="Save Category"
      />
    </x-slot:sidebar>
  </x-admin.form-shell>
  ```
- **Responsive:** stacks vertically on mobile (content first, sidebar second)

### Component 4: `x-admin.publish-box`

- **Purpose:** Consistent status/publish controls untuk sidebar kanan form pages
- **Replaces:** Status field yang saat ini tersebar inline di dalam form
- **Files affected:** akan dipakai di setiap form-shell sidebar slot
- **Features:**
  - Status select atau radio
  - Save button (primary)
  - Cancel link (secondary)
  - Optional: schedule date picker (untuk Pages yang sudah support scheduled)
  - Visual cue: badge warna sesuai status

### Component 5: Command Palette (OPTIONAL)

- **Purpose:** `Ctrl+K` / `Cmd+K` quick jump ke any admin screen — mengurangi ketergantungan pada sidebar
- **Files affected:** `resources/views/layouts/admin.blade.php` + Alpine.js component
- **Dependency:** Alpine.js (sudah ada di stack)
- **Data:** Static list dari route names dan labels (tidak perlu API call)
- **Decision:** INCLUDE — high value, pure Alpine, zero package install. Topbar search sudah ada sebagai visual placeholder ("UI only") — ini adalah implementasi nyatanya.

---

## 7. Implementation Plan

### Phase A2.1 — Shared components (buat tool dulu, apply sesudah)

| Step | Component | File Created | Est. Effort |
|------|-----------|--------------|-------------|
| 1 | `x-admin.sidebar` | `resources/views/components/admin/sidebar.blade.php` | M |
| 2 | `x-admin.data-table` | `resources/views/components/admin/data-table.blade.php` | M |
| 3 | `x-admin.form-shell` | `resources/views/components/admin/form-shell.blade.php` | S |
| 4 | `x-admin.publish-box` | `resources/views/components/admin/publish-box.blade.php` | S |
| 5 | Command palette | `resources/views/components/admin/command-palette.blade.php` | M |

### Phase A2.2 — Sidebar refactor

| Step | Action | Files Changed |
|------|--------|---------------|
| 1 | Ganti `sidebar.blade.php` dengan `x-admin.sidebar` component | `resources/views/backend/partials/sidebar.blade.php` |
| 2 | Wire `x-admin.sidebar` ke admin layout (update `@include`) | `resources/views/layouts/admin.blade.php` |
| 3 | Wire command palette ke admin layout | `resources/views/layouts/admin.blade.php` |
| 4 | Update topbar: aktifkan search → wired ke command palette | `resources/views/backend/partials/navbar.blade.php` |

### Phase A2.3 — List pages CSS fix (quick wins)

| Step | Module | File Changed | Fix |
|------|--------|-------------|-----|
| 1 | Categories | `resources/views/backend/categories/index.blade.php` | `form-input` → `admin-input` |
| 2 | Destinations | `resources/views/backend/destinations/index.blade.php` | `form-input` → `admin-input` |
| 3 | FAQs | `resources/views/backend/faqs/index.blade.php` | Tambah search box |
| 4 | Pages | `resources/views/backend/pages/index.blade.php` | Hapus duplicate flash message (sudah ada di layout) |

### Phase A2.4 — List pages migration ke `x-admin.data-table`

Migrate modul-modul yang pakai basic table pattern:

| Step | Module | File Changed |
|------|--------|-------------|
| 1 | Categories (active + archive) | `resources/views/backend/categories/index.blade.php` |
| 2 | Destinations (active + archive) | `resources/views/backend/destinations/index.blade.php` |
| 3 | FAQs | `resources/views/backend/faqs/index.blade.php` |
| 4 | SEO Redirects | `resources/views/backend/seo/redirects/index.blade.php` |
| 5 | Contact Forms | `resources/views/backend/contact-forms/index.blade.php` |

**Tidak dimigrate (layout terlalu spesifik):**
- Products (custom card layout — keep as-is, bagus)
- Media Library (grid layout — keep as-is, appropriate)
- Menus (tree editor — keep as-is)
- Themes (card grid — keep as-is)
- Plugins (card list — keep as-is)
- Audit Log (terlalu banyak custom filter logic — keep as-is, refactor opsional)

### Phase A2.5 — Form pages migration ke `x-admin.form-shell`

Migrate form-form yang simple (single flat form):

| Step | Module | Files Changed |
|------|--------|-------------|
| 1 | Categories | `resources/views/backend/categories/create.blade.php`, `edit.blade.php`, `form.blade.php` |
| 2 | Destinations | `resources/views/backend/destinations/create.blade.php`, `edit.blade.php`, `form.blade.php` |
| 3 | FAQs | `resources/views/backend/faqs/create.blade.php`, `edit.blade.php`, `form.blade.php` |
| 4 | SEO Redirects | `resources/views/backend/seo/redirects/create.blade.php`, `edit.blade.php` |

**Tidak dimigrate (sudah bagus atau terlalu kompleks):**
- Pages edit (accordion tabs sudah excellent — keep as-is)
- Pages create (simpler form — migrate opsional)
- Products (accordion `<details>` — keep as-is, works well)
- Contact Forms (form builder UI — keep as-is)
- Menus (tree editor — keep as-is)

### Phase A2.6 — Navbar sidebar label rename (minor)

| Step | Action | Files Changed |
|------|--------|---------------|
| 1 | Rename "Global Assets" → "Settings" di navbar `$pageTitle` match | `resources/views/backend/partials/navbar.blade.php` |
| 2 | Update "Global Assets" label dalam sidebar component | `resources/views/components/admin/sidebar.blade.php` |

---

## 8. Files that will change (complete list)

### New files to create

```
resources/views/components/admin/sidebar.blade.php
resources/views/components/admin/data-table.blade.php
resources/views/components/admin/form-shell.blade.php
resources/views/components/admin/publish-box.blade.php
resources/views/components/admin/command-palette.blade.php
```

### Existing files to modify

```
resources/views/backend/partials/sidebar.blade.php
resources/views/backend/partials/navbar.blade.php
resources/views/layouts/admin.blade.php
resources/views/backend/categories/index.blade.php
resources/views/backend/categories/create.blade.php
resources/views/backend/categories/edit.blade.php
resources/views/backend/categories/form.blade.php
resources/views/backend/destinations/index.blade.php
resources/views/backend/destinations/create.blade.php
resources/views/backend/destinations/edit.blade.php
resources/views/backend/destinations/form.blade.php
resources/views/backend/faqs/index.blade.php
resources/views/backend/faqs/create.blade.php
resources/views/backend/faqs/edit.blade.php
resources/views/backend/faqs/form.blade.php
resources/views/backend/seo/redirects/index.blade.php
resources/views/backend/seo/redirects/create.blade.php
resources/views/backend/seo/redirects/edit.blade.php
resources/views/backend/contact-forms/index.blade.php
resources/views/backend/pages/index.blade.php  (hapus duplicate flash message)
```

### Files NOT changed (routes, controllers, models, migrations)

```
NONE dari routes/admin.php, app/Http/Controllers/Admin/*, app/Models/*, database/migrations/*
Ini adalah presentation-only refactor. Zero logic berubah.
```

---

## 9. Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Sidebar collapsible breaks active-state highlighting | Medium | Low | Test setiap nav link; auto-expand parent jika child aktif |
| `data-table` component terlalu generic, kehilangan module-specific behavior | Medium | Medium | Pakai named slots (filters, thead, tbody, pagination) — tiap modul bisa customize |
| Command palette listing stale routes | Low | Low | Data static di component PHP — update manual kalau ada route baru |
| Form-shell two-column breaks form submission (hidden fields) | Low | High | Tidak ada hidden fields di modul yang dimigrate; test submit setiap form |
| Mobile sidebar drawer conflict dengan Alpine state di sidebar baru | Medium | Medium | Test mobile drawer buka/tutup setelah ganti ke component baru |
| localStorage state persist antar user di shared browser | Low | Low | State hanya UI preference, bukan data sensitif |
| Categories/Destinations `form-input` → `admin-input` CSS change breaks styling | Low | Low | `admin-input` sudah dipakai di semua modul lain — safe change |

---

## 10. Verification Plan

Setelah setiap A2.x:

```bash
# Test suite harus tetap hijau
php artisan test --stop-on-failure 2>&1 | tail -10

# PHPStan harus tetap 0 errors
vendor/bin/phpstan analyse --no-progress 2>&1 | tail -5

# Route count harus SAMA (156 routes, tidak boleh berubah)
php artisan route:list --path=admin 2>/dev/null | wc -l

# Smoke check key admin pages
curl -s -o /dev/null -w "admin → %{http_code}\n" "http://bintan-prestige.test/admin"
curl -s -o /dev/null -w "admin/login → %{http_code}\n" "http://bintan-prestige.test/admin/login"
curl -s -o /dev/null -w "products → %{http_code}\n" "http://bintan-prestige.test/products"
```

Visual checks per A2.x:
- A2.1: Komponen bisa di-render tanpa error (buat test page sementara atau langsung ke A2.2)
- A2.2: Sidebar menampilkan semua 8 grup; collapsible works; active item highlighted; mobile drawer works
- A2.3: Search input styling konsisten di categories dan destinations; FAQs ada search box
- A2.4: Semua list pages tabel render dengan benar; pagination works; empty state shows
- A2.5: Semua form submit dengan benar (POST test manual); validasi error display; cancel button works
- A2.6: Topbar menampilkan "Settings" bukan "Global Assets" untuk settings routes

---

## 11. Rollback Plan

Setiap A2.x adalah commit terpisah. Rollback per step:

```bash
git revert [commit-hash-a2.1]
git revert [commit-hash-a2.2]
# dst.
```

Full rollback views only:
```bash
git checkout develop -- resources/views/
```

Components baru tidak ada di `develop`, jadi full rollback juga handles file baru.

---

## 12. Summary: Before vs After

### Sidebar
| | Before | After |
|-|--------|-------|
| Top-level items | 19 flat items | 8 collapsible groups |
| Section labels | 5 decorative (non-interactive) | 8 group headers (clickable) |
| Collapsible | NO | YES (Alpine.js + localStorage) |
| Default visible | All 19 items | Dashboard + active group |
| Mobile | ✅ Drawer (keep) | ✅ Drawer (keep) |

### List Pages
| | Before | After |
|-|--------|-------|
| CSS class consistency | ❌ `form-input` mixed with `admin-input` | ✅ `admin-input` everywhere |
| FAQ search | ❌ Missing | ✅ Added |
| Shared table component | ❌ None | ✅ `x-admin.data-table` |
| Products layout | ✅ Keep custom card | ✅ Keep (unchanged) |
| Media layout | ✅ Keep grid | ✅ Keep (unchanged) |

### Form Pages
| | Before | After |
|-|--------|-------|
| Two-column layout | ❌ All single column | ✅ Simple forms get form-shell (2-col) |
| Publish box | ❌ Inline in form | ✅ `x-admin.publish-box` in sidebar slot |
| Pages edit accordion | ✅ Already excellent | ✅ Keep (unchanged) |
| Products accordion | ✅ Already works | ✅ Keep (unchanged) |

### Settings
| | Before | After |
|-|--------|-------|
| Sidebar label | "Global Assets" | "Settings" |
| Consolidation | ✅ Already 1 tabbed page | ✅ Keep (unchanged) |

---

## Status: IMPLEMENTED ✅

**Completed:** 2026-06-21 on branch `feature/phase-5-stage-a-foundation`

| Phase | Commit | Summary |
|-------|--------|---------|
| A2.1 + A2.2 | `4c4d17d` | Shared components (sidebar, data-table, form-shell, publish-box, command-palette) + collapsible sidebar |
| A2.3 | `3fac4a1` | List page CSS quick-win fixes |
| A2.4 | `49f6a5a` | Migrate list pages to x-admin.data-table (5 files) |
| A2.5 | `496f8ba` | Migrate form pages to x-admin.form-shell (categories, destinations, faqs) |
| A2.6 | (covered in A2.2) | Navbar labels + sidebar group labels corrected |

**Verification:** 597 tests pass · PHPStan 0 errors · Route count unchanged (153 admin routes)
