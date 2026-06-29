# STEP 02 — Shell Dark Base & Sidebar Redesign
# Bintan Prestige CMS — Admin UI/UX Redesign
# Paste prompt ini ke sesi Claude baru

---

## Konteks Sesi Ini

Kamu mengerjakan **Step 02** dari Admin UI/UX Redesign. Sesi baru.

**Prerequisite:** Step 01 (CSS Custom Properties) sudah ✅ selesai.
Cek: `ai/reports/UIUX/step-01-handoff.md` harus ada dan status Complete.

**Scope step ini:**
- `.admin-shell` dan `.admin-body` → full dark base
- Seluruh `.admin-sidebar*` class → "Command Center Dark" sidebar
- Sidebar brand mark, title, nav links, icons, section labels, divider
- Mobile sidebar: toggle button + backdrop overlay

**Referensi:**
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — Section 9.1 (Shell) + 9.2 (Sidebar)
- `resources/css/admin.css` — sudah punya var() dari Step 01
- `resources/views/components/admin/sidebar.blade.php` — struktur Blade (read only)
- `resources/views/backend/partials/sidebar.blade.php` — entry point sidebar

---

## Rules

**DILARANG:**
- Mengubah nama CSS class
- Mengubah Blade file sidebar (hanya CSS)
- Mengubah JavaScript/Alpine.js sidebar logic
- Mengubah PHP logic apapun

**BOLEH:**
- Edit `resources/css/admin.css` — bagian sidebar dan shell
- Membuat `ai/reports/UIUX/step-02-handoff.md`

---

## Risk Assessment

**Risk: 🟡 Medium**

Mengapa medium:
- Sidebar terlihat oleh semua user admin di setiap halaman
- Jika ada class yang salah → sidebar hilang atau layout broken
- Brand mark berubah visual (gradient dihapus → solid violet)
- Active state link berubah (solid bg → border + soft glow)

Rollback: `git checkout HEAD -- resources/css/admin.css`

---

## Phase 1 — Baca Konteks

1. `AGENTS.md`
2. `ai/reports/UIUX/grand-master-plan-admin-uiux.md` Section 9.1 dan 9.2
3. `resources/css/admin.css` — baca bagian `.admin-shell*`, `.admin-sidebar*`
4. `resources/views/components/admin/sidebar.blade.php` — read only

Konfirmasi:
```
=== STEP 02 CONTEXT ===
Prerequisite Step 01: [✅/❌ — cek handoff]
Files diubah: resources/css/admin.css (shell + sidebar sections only)
Files di-inspect: sidebar.blade.php (read only)
```

---

## Phase 2 — Inspect

Baca dan catat:
1. Struktur HTML sidebar — class apa saja yang dipakai di Blade?
2. Berapa banyak `.admin-sidebar__*` class ada di CSS?
3. Apakah ada class yang ada di CSS tapi tidak di Blade? (dead CSS)
4. Apakah sidebar sudah pakai `var(--admin-*)` dari Step 01?

---

## Phase 3 — Implementasi

### 3.1 Shell & Body

```css
.admin-body {
    @apply min-h-screen overflow-x-hidden;
    background: var(--admin-bg-base);
    color: var(--admin-text-primary);
}

.admin-shell {
    @apply flex min-h-screen w-full overflow-x-hidden;
    background: var(--admin-bg-base);
    /* Subtle dot grid texture */
    background-image: radial-gradient(
        circle,
        rgba(255, 255, 255, 0.025) 1px,
        transparent 1px
    );
    background-size: 24px 24px;
}

.admin-shell__workspace {
    @apply flex min-w-0 flex-1 flex-col;
}

.admin-shell__main {
    @apply min-w-0 flex-1 overflow-x-hidden px-4 py-5 sm:px-5 lg:px-6 xl:px-8;
}

.admin-shell__content {
    @apply mx-auto w-full max-w-[1600px] space-y-6;
}
```

### 3.2 Sidebar Base

```css
.admin-sidebar-shell {
    @apply w-0 shrink-0 lg:w-64;
}

.admin-sidebar {
    @apply fixed inset-y-0 left-0 z-50 flex h-screen w-64 shrink-0
           flex-col overflow-y-auto transition-transform duration-300
           ease-in-out lg:sticky lg:top-0 lg:z-auto lg:translate-x-0;
    background: var(--admin-sidebar-bg);
    border-right: 1px solid var(--admin-sidebar-border);
}
```

### 3.3 Brand Mark & Title

```css
.admin-sidebar__brand {
    @apply flex items-center gap-3 px-4 py-4;
    border-bottom: 1px solid var(--admin-sidebar-border);
}

/* Brand mark: solid violet dengan gold ring hint */
.admin-sidebar__brand-mark {
    @apply flex h-10 w-10 shrink-0 items-center justify-center
           rounded-lg text-sm font-black text-white;
    background: var(--admin-primary);
    box-shadow:
        0 0 0 2px rgba(212, 175, 55, 0.25),
        0 4px 12px var(--admin-primary-glow);
}

.admin-sidebar__title {
    @apply truncate text-sm font-extrabold tracking-tight;
    color: var(--admin-text-primary);
}

.admin-sidebar__subtitle {
    @apply mt-0.5 truncate text-xs font-medium;
    color: var(--admin-gold);
    opacity: 0.7;
}

.admin-sidebar__close {
    @apply ml-auto flex h-8 w-8 items-center justify-center
           rounded-lg transition hover:bg-white/10 lg:hidden;
    color: var(--admin-text-muted);
}
.admin-sidebar__close:hover {
    color: var(--admin-text-primary);
}
```

### 3.4 Navigation

```css
.admin-sidebar__nav {
    @apply flex flex-1 flex-col gap-0.5 px-3 py-4 pb-8;
}

.admin-sidebar__section-label {
    @apply px-2.5 pb-1.5 pt-5 text-[9px] font-black uppercase first:pt-2;
    letter-spacing: 0.15em;
    color: rgba(148, 163, 184, 0.4); /* slate-400/40 */
}

.admin-sidebar__divider {
    @apply my-2 border-t;
    border-color: var(--admin-sidebar-border);
}
```

### 3.5 Nav Links

```css
/* Default link */
.admin-sidebar__link {
    @apply relative flex items-center gap-2.5 rounded-md px-2.5 py-2
           text-sm font-medium transition duration-150;
    color: var(--admin-sidebar-text);
}
.admin-sidebar__link:hover {
    background: rgba(255, 255, 255, 0.05);
    color: var(--admin-sidebar-text-hover);
}

/* Active link — border kiri + soft glow, bukan solid bg */
.admin-sidebar__link--active {
    background: var(--admin-sidebar-active-bg);
    color: var(--admin-sidebar-active-text);
    border-left: 2px solid var(--admin-sidebar-active-border);
    padding-left: calc(0.625rem - 2px); /* kompensasi border */
}

/* Icon wrapper */
.admin-sidebar__icon {
    @apply flex h-7 w-7 shrink-0 items-center justify-center
           rounded-md text-[13px] transition;
    background: transparent;
    color: var(--admin-sidebar-text);
}
.admin-sidebar__link:hover .admin-sidebar__icon {
    color: var(--admin-sidebar-text-hover);
}
.admin-sidebar__link--active .admin-sidebar__icon {
    color: var(--admin-sidebar-active-text);
}
```

### 3.6 Mobile Toggle & Backdrop

```css
.admin-sidebar-toggle {
    @apply fixed left-4 top-4 z-40 flex h-10 w-10 items-center
           justify-center rounded-xl transition lg:hidden;
    background: rgba(30, 41, 59, 0.9);
    border: 1px solid var(--admin-border-md);
    color: var(--admin-text-secondary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}
.admin-sidebar-toggle:hover {
    border-color: var(--admin-sidebar-active-border);
    color: var(--admin-sidebar-active-text);
}

.admin-sidebar-backdrop {
    @apply fixed inset-0 z-40 lg:hidden;
    background: rgba(2, 6, 23, 0.7);
    backdrop-filter: blur(4px);
}
```

---

## Phase 4 — Verifikasi

**4.1 Visual Check (manual di browser):**
- [ ] Sidebar background: deep black/navy (bukan abu-abu tua lama)
- [ ] Brand mark: solid violet square (bukan gradient rainbow)
- [ ] Subtitle (CMS tagline): ada sentuhan warna gold
- [ ] Nav link default: muted, tidak terlalu terang
- [ ] Nav link hover: sedikit lebih terang, tidak ada bg box
- [ ] Nav link active: ada border kiri violet + soft violet bg
- [ ] Section labels: sangat subtle, hampir tidak terlihat (tapi terbaca)
- [ ] Mobile: hamburger button terlihat, backdrop gelap saat dibuka

**4.2 No Visual Regression Check:**
- [ ] Konten area kanan sidebar tidak terpengaruh
- [ ] Sidebar scrollable jika menu banyak
- [ ] Mobile sidebar bisa dibuka dan ditutup normal

**4.3 Accessibility:**
- [ ] Kontras sidebar text vs sidebar background ≥ 3:1
- [ ] Active link visually distinct dari non-active

---

## Phase 5 — Buat Handoff

Buat `ai/reports/UIUX/step-02-handoff.md`:

```markdown
# Step 02 Handoff — Shell Dark Base & Sidebar Redesign
**Tanggal:** [isi]
**Status:** ✅ Complete / ❌ Blocked / ⚠️ Partial
**Branch:** feature/admin-uiux-redesign

## Checklist
- [ ] admin-shell + admin-body: dark base ✅
- [ ] Sidebar brand mark: violet solid (bukan gradient) ✅
- [ ] Sidebar subtitle: gold hint ✅
- [ ] Nav link default/hover/active: redesign ✅
- [ ] Section labels: ultra subtle ✅
- [ ] Mobile toggle + backdrop: dark styling ✅
- [ ] Visual check: OK ✅
- [ ] Accessibility: OK ✅

## Temuan
[Tulis temuan]

## Perubahan File
- `resources/css/admin.css` — sidebar + shell section

## Rollback
git checkout HEAD -- resources/css/admin.css

## Next Step
Step 03 — Topbar Redesign
```

---

## STOP — Tunggu approval owner sebelum lanjut ke Step 03.
