# STEP 03 — Topbar Dark Redesign
# Bintan Prestige CMS — Admin UI/UX Redesign
# Paste prompt ini ke sesi Claude baru

---

## Konteks Sesi Ini

Kamu mengerjakan **Step 03** dari Admin UI/UX Redesign. Sesi baru.

**Prerequisite:** Step 01 ✅ + Step 02 ✅
Cek `ai/reports/UIUX/step-02-handoff.md` sebelum mulai.

**Scope:** `.admin-topbar*`, `.admin-user-menu*`, search bar dark styling.
Topbar berubah dari `bg-white/95` ke dark glass konsisten dengan sidebar.

**Referensi:**
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — Section 9.3
- `resources/views/backend/partials/navbar.blade.php` — read only

---

## Rules

**DILARANG:** Mengubah PHP logic di navbar.blade.php, Alpine.js click handlers,
route/breadcrumb logic, atau Blade structure.

**BOLEH:** Edit `resources/css/admin.css` — bagian topbar dan user menu only.

---

## Risk Assessment

**Risk: 🟢 Low**

Topbar hanya CSS change. User menu dropdown masih Alpine.js — tidak berubah.
Search bar sudah triggering command palette via existing JS — tidak berubah.
Breadcrumb text warna berubah (dari gelap ke terang) tapi logicnya tetap.

Rollback: `git checkout HEAD -- resources/css/admin.css`

---

## Phase 1 — Baca & Inspect

1. Baca `ai/reports/UIUX/grand-master-plan-admin-uiux.md` Section 9.3
2. Baca `resources/views/backend/partials/navbar.blade.php` — catat semua class yang dipakai
3. Baca `resources/css/admin.css` — bagian `.admin-topbar*` dan `.admin-user-menu*`

---

## Phase 3 — Implementasi

### Topbar Base

```css
.admin-topbar {
    @apply sticky top-0 z-30 backdrop-blur-xl;
    background: rgba(2, 6, 23, 0.92);
    border-bottom: 1px solid var(--admin-sidebar-border);
}

.admin-topbar__main {
    @apply flex h-14 items-center gap-3 px-4 lg:px-6;
}

.admin-topbar__title-group {
    @apply min-w-0 flex-1 pl-10 lg:pl-0;
}

.admin-topbar__breadcrumb {
    @apply flex flex-wrap items-center gap-1.5 text-xs font-semibold;
    color: var(--admin-text-muted);
}
.admin-topbar__breadcrumb span {
    color: var(--admin-text-secondary);
}
/* Separator */
.admin-topbar__breadcrumb span[aria-hidden="true"] {
    color: var(--admin-text-muted);
}

.admin-topbar__actions {
    @apply ml-auto flex items-center gap-2;
}
```

### Search Bar

```css
.admin-topbar__search {
    @apply hidden min-w-0 cursor-pointer items-center gap-2 rounded-lg
           px-3 py-2 text-sm transition lg:flex lg:w-56 xl:w-72;
    background: rgba(15, 23, 42, 0.8);
    border: 1px solid var(--admin-border);
    color: var(--admin-text-muted);
}
.admin-topbar__search:hover {
    border-color: var(--admin-sidebar-active-border);
    color: var(--admin-text-secondary);
}

.admin-topbar__search span:last-child {
    @apply rounded-md px-1.5 py-0.5 text-[10px] font-bold;
    background: rgba(255, 255, 255, 0.06);
    color: var(--admin-text-muted);
    border: 1px solid var(--admin-border);
}

.admin-topbar__search .flex-1 {
    /* text di dalam search */
    color: var(--admin-text-muted);
}
```

### Icon Button & Notification Dot

```css
.admin-topbar__icon-button {
    @apply relative flex h-9 w-9 items-center justify-center
           rounded-lg transition;
    color: var(--admin-text-muted);
}
.admin-topbar__icon-button:hover {
    background: rgba(255, 255, 255, 0.06);
    color: var(--admin-text-secondary);
}

.admin-topbar__notification-dot {
    @apply absolute right-2 top-2 h-1.5 w-1.5 rounded-full;
    background: var(--admin-danger);
    box-shadow: 0 0 0 2px rgba(2, 6, 23, 0.9);
}
```

### User Menu

```css
.admin-user-menu {
    @apply relative;
}

.admin-user-menu__trigger {
    @apply flex items-center gap-2 rounded-lg px-2 py-1.5 text-left transition;
}
.admin-user-menu__trigger:hover {
    background: rgba(255, 255, 255, 0.05);
}

/* Avatar: violet gradient, bukan rainbow */
.admin-user-menu__avatar {
    @apply flex h-8 w-8 shrink-0 items-center justify-center
           rounded-lg text-xs font-black text-white;
    background: linear-gradient(135deg, var(--admin-primary), #2563EB);
}

.admin-user-menu__identity {
    @apply hidden min-w-0 flex-col sm:flex;
}
.admin-user-menu__identity span {
    @apply max-w-32 truncate text-sm font-semibold;
    color: var(--admin-text-primary);
}
.admin-user-menu__identity small {
    @apply text-xs;
    color: var(--admin-text-muted);
}

.admin-user-menu__chevron {
    @apply text-xs transition;
    color: var(--admin-text-muted);
}

/* Dropdown — Glassmorphism */
.admin-user-menu__dropdown {
    @apply absolute right-0 top-full mt-1 w-64 overflow-hidden rounded-xl;
    background: rgba(15, 23, 42, 0.97);
    border: 1px solid var(--admin-border-md);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255,255,255,0.04) inset;
    backdrop-filter: blur(20px);
}

.admin-user-menu__summary {
    @apply flex items-center gap-3 p-4;
    border-bottom: 1px solid var(--admin-border);
}
.admin-user-menu__summary div p {
    @apply text-sm font-semibold;
    color: var(--admin-text-primary);
}
.admin-user-menu__summary div small {
    @apply text-xs;
    color: var(--admin-text-muted);
}

.admin-user-menu__logout {
    @apply flex w-full items-center gap-3 px-4 py-3 text-sm font-semibold
           transition;
    color: var(--admin-danger);
}
.admin-user-menu__logout:hover {
    background: rgba(239, 68, 68, 0.08);
}
```

---

## Phase 4 — Verifikasi

- [ ] Topbar: dark glass (tidak putih)
- [ ] Search bar: dark, hover glow violet border
- [ ] Keyboard shortcut badge di search: subtle dark pill
- [ ] Notification dot: terlihat merah di background gelap
- [ ] User avatar: violet gradient (bukan rainbow)
- [ ] User menu dropdown: glassmorphism dark
- [ ] Breadcrumb text: muted slate, page name lebih terang
- [ ] Tidak ada area putih yang muncul di antara sidebar dan topbar

---

## Phase 5 — Buat Handoff

Buat `ai/reports/UIUX/step-03-handoff.md`.

---

## STOP — Tunggu approval owner sebelum lanjut ke Step 04.
