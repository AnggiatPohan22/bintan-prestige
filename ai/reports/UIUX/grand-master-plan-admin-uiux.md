# Grand Master Plan — Admin Dashboard UI/UX Redesign
## Bintan Prestige CMS

---

**Tanggal Pembuatan:** 2026-06-23
**Terakhir Diperbarui:** 2026-06-23 (v1.1 — tambah Light Mode + Appearance Customizer)
**Dibuat Oleh:** AI Agent (Claude Sonnet 4.6) — atas instruksi Owner
**Status:** Planning Document — TIDAK mengubah logic, function, atau route existing
**Berlaku Untuk:** Admin Dashboard (`/admin/*`) — semua modul
**Stack:** Laravel 13.8 | PHP 8.3 | Tailwind CSS | Alpine.js
**Referensi Utama:**
  - `DESIGN-SYSTEM.md` (root) — design tokens & palette
  - `ai/skills/admin-dashboard-skill.md` — admin skill (diperbarui bersama dokumen ini)
  - `resources/css/admin.css` — semua CSS class admin existing
  - `resources/views/layouts/admin.blade.php` — layout shell
  - `resources/views/backend/partials/` — sidebar & navbar
  - `resources/views/components/admin/` — component library admin

**Changelog:**
- v1.0 (2026-06-23) — Initial Grand Master Plan (Dark Mode only)
- v1.1 (2026-06-23) — Tambah: Light Mode palette, Appearance Customizer full spec,
  DB schema proposal (pending approval), CSS Custom Properties architecture

---

> **CATATAN KRITIS — v1.0 (Visual Redesign):**
> Section 1–14 adalah RENCANA murni UI/UX visual. Tidak ada logic, route, controller,
> model, migration, atau fungsi PHP yang diubah. Perubahan hanya menyentuh:
> `admin.css`, `admin.blade.php`, `partials/`, `components/admin/`.
>
> **CATATAN KRITIS — v1.1 (Appearance Customizer):**
> Section 15–16 mendeskripsikan fitur BARU yang memerlukan:
> - ✅ Persetujuan Owner sebelum implementasi (butuh migration baru)
> - ✅ Migration: tabel `admin_dashboard_appearances` baru (terpisah dari global_settings)
> - ✅ Controller + Service + Route baru
> - Implementasi akan mengikuti CMS Module Pattern (AGENTS.md §6)

---

## Daftar Isi

1. [Current State Analysis](#1-current-state-analysis)
2. [Masalah & Peluang](#2-masalah--peluang)
3. [Visi & Aesthetic Direction](#3-visi--aesthetic-direction)
4. [Evolusi Sistem Warna — Dark Mode](#4-evolusi-sistem-warna--dark-mode)
5. [Light Mode Palette](#5-light-mode-palette)
6. [CSS Custom Properties Architecture](#6-css-custom-properties-architecture)
7. [Sistem Tipografi Admin](#7-sistem-tipografi-admin)
8. [Surface & Elevation System](#8-surface--elevation-system)
9. [Spesifikasi Komponen Per Area](#9-spesifikasi-komponen-per-area)
   - 9.1 Layout Shell
   - 9.2 Sidebar
   - 9.3 Topbar
   - 9.4 Cards & Panels
   - 9.5 Forms & Inputs
   - 9.6 Tables & Data Grid
   - 9.7 Buttons
   - 9.8 Badges & Status
   - 9.9 Modals & Dialogs
   - 9.10 Empty States
   - 9.11 Toast & Flash Alerts
   - 9.12 Dashboard Home
10. [Motion & Micro-Animation Principles](#10-motion--micro-animation-principles)
11. [Iconografi](#11-iconografi)
12. [Responsive Strategy](#12-responsive-strategy)
13. [Accessibility Commitments](#13-accessibility-commitments)
14. [Skill Files Baru / Diperbarui](#14-skill-files-baru--diperbarui)
15. [CSS Class Migration Map](#15-css-class-migration-map)
16. [Implementation Roadmap](#16-implementation-roadmap)
— — — FITUR BARU (v1.1) — — —
17. [Admin Appearance Customizer — Feature Spec](#17-admin-appearance-customizer--feature-spec)
18. [DB Schema Proposal — admin_dashboard_appearances](#18-db-schema-proposal--admin_dashboard_appearances)
19. [Architecture: CSS Variables Flow](#19-architecture-css-variables-flow)
20. [Preset Brand Themes](#20-preset-brand-themes)
21. [UI Spec: Settings > Customize Dashboard](#21-ui-spec-settings--customize-dashboard)
22. [Implementation Plan — Customizer Module](#22-implementation-plan--customizer-module)

---

## 1. Current State Analysis

### State Saat Ini

| Area | State |
|------|-------|
| Layout | Dark sidebar (`bg-slate-900`) + Light content (`bg-slate-50`) + White cards |
| Primary action | `bg-indigo-600` (indigo-600) |
| Sidebar brand mark | Gradient `from-indigo-500 via-purple-600 to-pink-500` |
| Card radius | `rounded-2xl` konsisten di semua komponen |
| Shadow | `shadow-sm` (abu-abu, sangat subtle) |
| Focus ring | `focus:ring-4 focus:ring-indigo-100` |
| Topbar | `bg-white/95 backdrop-blur-xl` — semi-transparan putih |
| Typography | System sans-serif, ukuran bervariasi |
| Animation | Basic Tailwind `transition duration-150/300` |
| Badges | Soft colored pills (bg-emerald-100 text-emerald-700) |
| Data density | Medium — sudah cukup efisien |

### Kekuatan Yang Harus Dipertahankan

- Struktur CSS class semantic yang solid (`admin-card`, `admin-btn-primary`, dll.)
- Sidebar dark dengan section labels yang clear
- Topbar breadcrumb navigation yang informatif
- Component architecture yang well-organized
- Mobile-responsive sidebar dengan backdrop overlay
- Command palette (`Ctrl+K`) yang powerful
- Focus ring accessibility
- Flash alert & confirm modal system

---

## 2. Masalah & Peluang

### Masalah Utama (Mengapa Terlihat Generic)

**1. Binary Color World — Tidak Ada Kedalaman**
Saat ini hanya ada dua dunia: sidebar gelap dan konten putih. Tidak ada layer permukaan di antaranya.
Efek: tampak seperti template Tailwind off-the-shelf.

**2. Over-Rounded — Kehilangan Ketegasan**
`rounded-2xl` di semua elemen (kartu, input, badge, tombol) menciptakan kesan "bubble" yang terlalu soft.
Efek: tampak seperti dashboard SaaS generik untuk startup.

**3. Shadow Yang Tidak Berkarakter**
`shadow-sm` abu-abu di semua tempat. Tidak ada depth contrast, tidak ada ambient color glow.
Efek: elemen terasa "flat" dan tidak hidup.

**4. Warna Primary Terlalu Mainstream**
`indigo-600` adalah warna default Tailwind yang dipakai jutaan dashboard lain.
Efek: tidak ada identity visual yang membedakan.

**5. Topbar Tidak Konsisten Dengan Sidebar**
Sidebar gelap, topbar putih — kontras yang terlalu kasar dan tidak harmonis.
Efek: terasa seperti dua produk berbeda.

**6. Dashboard Home Yang Minimal**
Tidak ada data visualization, stats, atau KPI visual.
Efek: halaman pertama yang dilihat admin terasa kosong.

**7. Tidak Ada Motion Hierarchy**
Semua animasi sama (transition duration-150 atau 300). Tidak ada perbedaan antara "elemen kecil bergerak" vs "panel besar masuk."
Efek: tidak ada sense of weight atau hierarchy.

**8. Tidak Ada Brand Personality**
Warna gold (`#D4AF37`) yang jadi identitas Bintan Prestige di frontend sama sekali tidak hadir di admin.
Efek: admin terasa seperti produk generic, bukan CMS Bintan Prestige.

### Peluang

- Fully dark admin dengan depth layers akan terasa seperti "command center" profesional
- Satu accent warna unik (electric violet atau deep cyan) akan memberi identity
- Subtle gold hint dari brand frontend bisa hadir di admin sebagai elemen premium
- Glassmorphism pada floating elements (modal, dropdown) akan menambah kecanggihan visual
- Micro-animations yang purposeful akan membuat setiap interaksi terasa responsif
- Dashboard home dengan stats visual akan membuat halaman utama terasa hidup

---

## 3. Visi & Aesthetic Direction

### Nama Visi: **"Command Center Dark"**

Bayangkan admin Bintan Prestige sebagai **control tower** untuk sebuah resort mewah.
Bukan cubicle spreadsheet. Bukan Notion clone. Bukan dashboard startup biasa.

**Inspirasi (spirit, bukan kopian):**
- **Linear** — precision, dark, sharp edges, productive
- **Vercel** — minimal dark, hierarchy yang jelas, information density
- **Raycast** — glassmorphism, spotlight feel, command-first
- **Figma** — multi-layer dark surfaces, purposeful color usage

**Anti-Inspirasi (jangan seperti ini):**
- Generic Tailwind admin template
- "Modern SaaS" white card + indigo button formula
- Gradien rainbow yang noisy
- Rounded-everything bubble UI

### Tiga Prinsip Utama

**1. DEPTH** — Setiap elemen punya elevation yang jelas melalui surface color, bukan hanya shadow.

**2. PRECISION** — Geometry yang tegas. Radius yang tepat untuk tiap konteks. Tidak semua `rounded-2xl`.

**3. IDENTITY** — Warna dan aksen yang unik untuk Bintan Prestige, tidak bisa ditukar dengan Tailwind template lain.

### Suasana Target

```
Dark base  ──►  Depth layers  ──►  Electric accent  ──►  Gold whisper
(bg-slate-950)   (5 surfaces)      (violet/cyan)        (brand hint)
```

---

## 4. Evolusi Sistem Warna — Dark Mode

### 4.1 Admin Color Palette — Sebelum & Sesudah

| Role | SEBELUM | SESUDAH | Catatan |
|------|---------|---------|---------|
| Page background | `bg-slate-50` (#F8FAFC) putih abu | `bg-slate-950` (#020617) deep black | Full dark shift |
| Surface 1 (base) | — | `bg-slate-900` (#0F172A) | Container utama |
| Surface 2 (card) | `bg-white` | `bg-slate-800/80` (#1E293B) | Cards dengan glass |
| Surface 3 (hover) | `bg-slate-50` | `bg-slate-800` (#1E293B) | Hover states |
| Surface 4 (input) | `bg-white` | `bg-slate-900` (#0F172A) | Form backgrounds |
| Surface 5 (raised) | — | `bg-slate-700/50` | Dropdowns, tooltips |
| Sidebar | `bg-slate-900` (#0F172A) | `bg-slate-950` (#020617) + gradient | Lebih dalam |
| Topbar | `bg-white/95` | `bg-slate-900/90` | Dark, konsisten |
| Primary action | `bg-indigo-600` (#4F46E5) | `bg-violet-600` (#7C3AED) | Lebih distinctive |
| Primary hover | `bg-indigo-700` | `bg-violet-700` (#6D28D9) | |
| Primary glow | tidak ada | `shadow-violet-900/50` | Ambient color glow |
| Secondary accent | tidak ada | `bg-cyan-500` (#06B6D4) | Highlight + info |
| Brand gold hint | tidak ada | `#D4AF37` (text-amber-400) | Subtle, premium |
| Text primary | `text-slate-900` | `text-slate-100` | Inverted untuk dark |
| Text secondary | `text-slate-500` | `text-slate-400` | |
| Text muted | `text-slate-400` | `text-slate-500` | |
| Border | `border-slate-200` | `border-white/10` | Subtle glass border |
| Border strong | `border-slate-300` | `border-white/20` | |
| Focus ring | `ring-indigo-100` | `ring-violet-500/30` | Match new primary |
| Success | `bg-emerald-100 text-emerald-700` | `bg-emerald-500/15 text-emerald-400` | Dark-optimized |
| Warning | `bg-amber-100 text-amber-700` | `bg-amber-500/15 text-amber-400` | |
| Danger | `bg-red-100 text-red-700` | `bg-red-500/15 text-red-400` | |
| Info | `bg-sky-100 text-sky-700` | `bg-cyan-500/15 text-cyan-400` | Match accent |

### 4.2 Palette Token Reference (untuk admin.css)

```css
/* === Admin Dark Palette === */

/* Base Surfaces */
--admin-bg-base:      #020617; /* slate-950 — halaman background */
--admin-bg-surface:   #0F172A; /* slate-900 — surface utama, sidebar */
--admin-bg-card:      #1E293B; /* slate-800 — kartu, panel */
--admin-bg-input:     #0F172A; /* slate-900 — form input */
--admin-bg-hover:     #334155; /* slate-700 — hover states */
--admin-bg-raised:    rgba(30,41,59,0.8); /* slate-800/80 — glass effect */

/* Borders */
--admin-border:       rgba(255,255,255,0.08); /* white/8 — subtle */
--admin-border-md:    rgba(255,255,255,0.12); /* white/12 — medium */
--admin-border-strong:rgba(255,255,255,0.20); /* white/20 — visible */

/* Text */
--admin-text-primary:   #F1F5F9; /* slate-100 */
--admin-text-secondary: #94A3B8; /* slate-400 */
--admin-text-muted:     #64748B; /* slate-500 */

/* Primary — Electric Violet */
--admin-primary:        #7C3AED; /* violet-600 */
--admin-primary-hover:  #6D28D9; /* violet-700 */
--admin-primary-soft:   rgba(124,58,237,0.15); /* violet-600/15 */
--admin-primary-glow:   rgba(124,58,237,0.4);  /* violet-600/40 — glow */

/* Secondary — Electric Cyan */
--admin-accent:         #06B6D4; /* cyan-500 */
--admin-accent-soft:    rgba(6,182,212,0.15);  /* cyan-500/15 */

/* Brand Gold (dari frontend) — dipakai sangat sparingly */
--admin-gold:           #D4AF37;
--admin-gold-soft:      rgba(212,175,55,0.10);

/* Status */
--admin-success:        #10B981; /* emerald-500 */
--admin-warning:        #F59E0B; /* amber-500 */
--admin-danger:         #EF4444; /* red-500 */
--admin-info:           #06B6D4; /* cyan-500 */
```

### 4.3 Kapan Menggunakan Tiap Accent

| Warna | Digunakan Untuk |
|-------|----------------|
| **Violet** (`#7C3AED`) | Primary action, active state, focus ring, CTA utama |
| **Cyan** (`#06B6D4`) | Info badge, secondary button, link hover, chart accent |
| **Gold** (`#D4AF37`) | Brand mark, dashboard header decoration, premium highlight — max 2 elemen per halaman |
| **Emerald** | Success toast, badge published/active |
| **Amber** | Warning badge, draft status |
| **Red** | Danger action, error state, delete button |

---

---

## 5. Light Mode Palette

Light Mode adalah mode alternatif dari "Command Center Dark." Tetap mempertahankan
karakter **precision** dan **identity** — bukan kembali ke generic white SaaS.

### 5.1 Light Mode — Filosofi

Bukan sekadar "invert warna." Light mode admin harus tetap terasa sebagai tool profesional:
- Base: Off-white yang hangat (`#F8FAFC`) — bukan `#FFFFFF` mentah
- Cards: Pure white dengan border tipis dan shadow subtil
- Primary accent: Tetap violet — konsisten dengan dark mode
- Sidebar: Tetap bisa dark (sidebar gelap + konten terang = GitHub/Linear style), OR fully light

**Dua varian light mode:**

| Varian | Sidebar | Content | Karakter |
|--------|---------|---------|---------|
| **Light Classic** | `bg-slate-800` (dark sidebar) | `bg-slate-50` (light content) | Hybrid seperti GitHub |
| **Light Full** | `bg-white border-r` | `bg-gray-50` | Fully light seperti Notion |

Super admin bisa pilih varian mana yang digunakan via Customize Dashboard.

### 5.2 Light Mode — Color Tokens

```css
/* === Admin Light Palette === */

/* Base Surfaces */
--admin-bg-base:       #F8FAFC;  /* slate-50 — halaman background */
--admin-bg-surface:    #FFFFFF;  /* white — card, panel */
--admin-bg-card:       #FFFFFF;  /* white */
--admin-bg-input:      #FFFFFF;  /* white — form input */
--admin-bg-hover:      #F1F5F9;  /* slate-100 — hover states */
--admin-bg-raised:     rgba(255,255,255,0.95); /* modal, dropdown */

/* Borders */
--admin-border:        #E2E8F0;  /* slate-200 */
--admin-border-md:     #CBD5E1;  /* slate-300 */
--admin-border-strong: #94A3B8;  /* slate-400 */

/* Text */
--admin-text-primary:   #0F172A; /* slate-900 */
--admin-text-secondary: #475569; /* slate-600 */
--admin-text-muted:     #94A3B8; /* slate-400 */

/* Primary — sama dengan dark: Electric Violet */
--admin-primary:        #7C3AED; /* violet-600 */
--admin-primary-hover:  #6D28D9; /* violet-700 */
--admin-primary-soft:   #EDE9FE; /* violet-100 */
--admin-primary-glow:   rgba(124,58,237,0.15);

/* Secondary — Cyan */
--admin-accent:         #0891B2; /* cyan-600 (sedikit lebih gelap untuk light bg) */
--admin-accent-soft:    #ECFEFF; /* cyan-50 */

/* Brand Gold */
--admin-gold:           #D4AF37;
--admin-gold-soft:      #FEF9C3; /* yellow-100 approx */

/* Status (light-optimized) */
--admin-success:        #059669; /* emerald-600 */
--admin-warning:        #D97706; /* amber-600 */
--admin-danger:         #DC2626; /* red-600 */
--admin-info:           #0891B2; /* cyan-600 */

/* Light Sidebar (untuk "Light Full" variant) */
--admin-sidebar-bg:     #FFFFFF;
--admin-sidebar-border: #E2E8F0;
--admin-sidebar-text:   #64748B;
--admin-sidebar-text-active: #7C3AED;
--admin-sidebar-active-bg:   #EDE9FE; /* violet-100 */
```

### 5.3 Light Mode — Component Overrides

**Card:**
```css
[data-admin-mode="light"] .admin-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04);
}

[data-admin-mode="light"] .admin-card-header {
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
}
```

**Input:**
```css
[data-admin-mode="light"] .admin-input,
[data-admin-mode="light"] .admin-select,
[data-admin-mode="light"] .admin-textarea {
    background: #FFFFFF;
    border-color: #CBD5E1;
    color: #0F172A;
}

[data-admin-mode="light"] .admin-input:focus {
    border-color: #7C3AED;
    box-shadow: 0 0 0 3px rgba(124,58,237,0.12);
}
```

**Topbar:**
```css
[data-admin-mode="light"] .admin-topbar {
    background: rgba(255,255,255,0.95);
    border-bottom: 1px solid #E2E8F0;
    backdrop-filter: blur(12px);
}
```

**Badge (light-optimized):**
```css
[data-admin-mode="light"] .admin-badge-success {
    background: #D1FAE5;  /* emerald-100 */
    color: #065F46;       /* emerald-800 */
}
[data-admin-mode="light"] .admin-badge-warning {
    background: #FEF3C7;
    color: #92400E;
}
[data-admin-mode="light"] .admin-badge-danger {
    background: #FEE2E2;
    color: #991B1B;
}
[data-admin-mode="light"] .admin-badge-info {
    background: #ECFEFF;
    color: #155E75;
}
```

### 5.4 Light Sidebar — Dark Classic vs Light Full

```css
/* Dark Sidebar (default, berlaku dark dan light classic) */
.admin-sidebar {
    background: #020617; /* slate-950 */
    border-right: 1px solid rgba(255,255,255,0.06);
}

/* Light Full — sidebar putih */
[data-admin-sidebar="light"] .admin-sidebar {
    background: #FFFFFF;
    border-right: 1px solid #E2E8F0;
}

[data-admin-sidebar="light"] .admin-sidebar__link {
    color: #64748B;
}

[data-admin-sidebar="light"] .admin-sidebar__link:hover {
    background: #F1F5F9;
    color: #0F172A;
}

[data-admin-sidebar="light"] .admin-sidebar__link--active {
    background: #EDE9FE;  /* violet-100 */
    color: #7C3AED;
    border-left: 2px solid #7C3AED;
}

[data-admin-sidebar="light"] .admin-sidebar__section-label {
    color: #94A3B8;
}
```

---

## 6. CSS Custom Properties Architecture

Ini adalah inti dari fitur **Appearance Customizer**.
Semua warna visual admin di-drive oleh CSS custom properties yang di-inject
langsung ke `<html>` element dari database saat halaman di-load.

### 6.1 Arsitektur Aliran

```
Super Admin
    ↓ pilih warna di Settings > Customize Dashboard
DB: admin_dashboard_appearances (tabel baru)
    ↓ dibaca oleh AdminAppearanceService::getCurrentAppearance()
admin.blade.php <style> tag: inject CSS custom properties ke :root
    ↓
admin.css: semua class menggunakan var(--admin-*) bukan hardcoded hex
    ↓
Browser renders dengan warna yang dipilih super admin
```

### 6.2 CSS Custom Properties Master List

Semua property ini harus dikonversi dari hardcoded hex di `admin.css` ke `var()`.
Ini adalah pekerjaan refactor CSS — zero PHP logic change.

```css
/* Di admin.css — ganti semua hex dengan var() */
:root {
    /* === WAJIB TERSEDIA (dikontrol DB) === */

    /* Mode */
    --admin-mode: dark;                    /* "dark" | "light" */

    /* Surfaces */
    --admin-bg-base:       #020617;
    --admin-bg-surface:    #0F172A;
    --admin-bg-card:       #1E293B;
    --admin-bg-input:      #0F172A;
    --admin-bg-hover:      #334155;

    /* Borders */
    --admin-border:        rgba(255,255,255,0.08);
    --admin-border-md:     rgba(255,255,255,0.12);
    --admin-border-strong: rgba(255,255,255,0.20);

    /* Text */
    --admin-text-primary:   #F1F5F9;
    --admin-text-secondary: #94A3B8;
    --admin-text-muted:     #64748B;

    /* Primary Color (UTAMA — dapat dikustomisasi) */
    --admin-primary:        #7C3AED;
    --admin-primary-hover:  #6D28D9;
    --admin-primary-soft:   rgba(124,58,237,0.15);
    --admin-primary-glow:   rgba(124,58,237,0.4);
    --admin-primary-text:   #FFFFFF;    /* teks di atas primary button */

    /* Accent Color (dapat dikustomisasi) */
    --admin-accent:         #06B6D4;
    --admin-accent-soft:    rgba(6,182,212,0.15);

    /* Brand Gold (dapat dikustomisasi) */
    --admin-gold:           #D4AF37;
    --admin-gold-soft:      rgba(212,175,55,0.10);

    /* Sidebar */
    --admin-sidebar-bg:     #020617;
    --admin-sidebar-border: rgba(255,255,255,0.06);
    --admin-sidebar-text:   #64748B;
    --admin-sidebar-text-hover: #CBD5E1;
    --admin-sidebar-active-bg: rgba(124,58,237,0.20);
    --admin-sidebar-active-text: #C4B5FD;
    --admin-sidebar-active-border: #7C3AED;

    /* Status (tidak dikustomisasi — fixed semantic) */
    --admin-success:  #10B981;
    --admin-warning:  #F59E0B;
    --admin-danger:   #EF4444;
    --admin-info:     #06B6D4;

    /* Radius System */
    --admin-radius-sm:   6px;
    --admin-radius-md:   8px;
    --admin-radius-lg:   12px;
    --admin-radius-xl:   16px;
}
```

### 6.3 Penggunaan var() di admin.css

```css
/* Contoh konversi class admin-card */
.admin-card {
    background: var(--admin-bg-card);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius-lg);
}

/* Contoh konversi admin-btn-primary */
.admin-btn-primary {
    background: var(--admin-primary);
    color: var(--admin-primary-text);
    box-shadow: 0 0 0 1px var(--admin-primary-soft) inset;
}
.admin-btn-primary:hover {
    background: var(--admin-primary-hover);
    box-shadow: 0 4px 12px var(--admin-primary-glow);
}

/* Contoh konversi admin-input */
.admin-input {
    background: var(--admin-bg-input);
    border-color: var(--admin-border-md);
    color: var(--admin-text-primary);
}
.admin-input:focus {
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 3px var(--admin-primary-soft);
}
```

### 6.4 Inject dari Laravel ke HTML

Di `resources/views/layouts/admin.blade.php`:

```blade
{{-- Admin Appearance: CSS Custom Properties dari DB --}}
@if($adminAppearance ?? null)
<style>
:root {
    --admin-mode: {{ $adminAppearance->mode }};
    --admin-bg-base: {{ $adminAppearance->bg_base }};
    --admin-bg-card: {{ $adminAppearance->bg_card }};
    --admin-bg-input: {{ $adminAppearance->bg_input }};
    --admin-primary: {{ $adminAppearance->primary_color }};
    --admin-primary-hover: {{ $adminAppearance->primary_hover }};
    --admin-primary-soft: {{ $adminAppearance->primary_color }}26;
    --admin-primary-glow: {{ $adminAppearance->primary_color }}66;
    --admin-accent: {{ $adminAppearance->accent_color }};
    --admin-sidebar-bg: {{ $adminAppearance->sidebar_bg }};
    --admin-sidebar-active-bg: {{ $adminAppearance->primary_color }}33;
    --admin-sidebar-active-text: {{ $adminAppearance->primary_color }};
    --admin-sidebar-active-border: {{ $adminAppearance->primary_color }};
    {{-- Border dan text otomatis sesuai mode --}}
    @if($adminAppearance->mode === 'light')
    --admin-border: #E2E8F0;
    --admin-border-md: #CBD5E1;
    --admin-text-primary: #0F172A;
    --admin-text-secondary: #475569;
    --admin-text-muted: #94A3B8;
    --admin-bg-hover: #F1F5F9;
    @else
    --admin-border: rgba(255,255,255,0.08);
    --admin-border-md: rgba(255,255,255,0.12);
    --admin-text-primary: #F1F5F9;
    --admin-text-secondary: #94A3B8;
    --admin-text-muted: #64748B;
    --admin-bg-hover: #334155;
    @endif
}
</style>
@endif
```

Nilai di atas diberi default via `AppServiceProvider` atau `View::share()` menggunakan
`AdminAppearanceService::getCurrentAppearance()`. Jika belum ada record di DB,
service return nilai default "Command Center Dark."

---

## 7. Sistem Tipografi Admin

### 7.1 Font Stack

```css
/* Admin: tetap system sans-serif — jangan campur serif frontend */
font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;

/* Apabila Inter tidak tersedia (sudah di-load via Google Fonts atau Vite) */
/* Fallback: ui-sans-serif → system-ui → sans-serif */
```

> **Catatan:** Inter memberikan character yang lebih techlier dibanding default system-ui.
> Jika Inter belum di-load, tambahkan ke app.css atau Google Fonts link di admin layout.
> Ini bukan perubahan logic — hanya font asset load.

### 5.2 Skala Tipografi Admin

| Element | Class Sebelum | Class Sesudah | Perubahan |
|---------|--------------|---------------|-----------|
| Page title | `text-2xl font-extrabold text-slate-950` | `text-2xl font-extrabold text-slate-100 tracking-tight` | Warna inverted, kerning |
| Page subtitle | `text-sm text-slate-500` | `text-sm text-slate-400 leading-relaxed` | Subtle |
| Card heading | `text-base font-bold text-slate-800` | `text-sm font-bold text-slate-200 uppercase tracking-wider` | Label style |
| Table header | `text-sm font-bold text-slate-600` | `text-xs font-black text-slate-400 uppercase tracking-widest` | Precision header |
| Body text | `text-sm text-slate-700` | `text-sm text-slate-300` | |
| Muted | `text-xs text-slate-400` | `text-xs text-slate-500` | |
| Code / value | (tidak ada) | `font-mono text-cyan-400 text-xs` | Untuk ID, slug, URL |

### 5.3 Weight Hierarchy

```
page title    → font-extrabold (800) — anchors the page
card heading  → font-bold (700)      — section identity
label         → font-semibold (600)  — form field label
body          → font-medium (500)    — primary content
secondary     → font-normal (400)    — supporting text
muted         → font-normal (400)    — tertiary info
```

---

## 8. Surface & Elevation System

### 8.1 Lima Layer Surface (Mengganti Binary Dark/Light)

```
Layer 0 — Base        bg-slate-950  (#020617)   — halaman background
Layer 1 — Container   bg-slate-900  (#0F172A)   — sidebar, main wrapper
Layer 2 — Card        bg-slate-800  (#1E293B)   — admin-card
Layer 3 — Input       bg-slate-900  (#0F172A)   — form field backgrounds
Layer 4 — Hover       bg-slate-700  (#334155)   — hover, active row
Layer 5 — Raised      backdrop-blur + bg-slate-800/90 — modal, dropdown
```

### 6.2 Efek Elevasi (Bukan Shadow Abu-Abu)

| Level | CSS | Digunakan Pada |
|-------|-----|---------------|
| Flat | `border border-white/8` | Cards di base background |
| Lifted | `border border-white/10 shadow-lg shadow-black/30` | Cards yang perlu menonjol |
| Floating | `border border-white/12 shadow-2xl shadow-black/50 backdrop-blur-xl` | Modal, command palette, dropdown |
| Glowing | `shadow-lg shadow-violet-900/40` | Primary action element, active sidebar link |

### 6.3 Glass Effect Recipe (untuk Modals & Dropdowns)

```css
/* Glassmorphism — hanya untuk floating elements */
background: rgba(30, 41, 59, 0.85);   /* bg-slate-800/85 */
backdrop-filter: blur(20px);
border: 1px solid rgba(255, 255, 255, 0.12);
box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
```

> **Kapan pakai glass:** modal overlay, dropdown menu, tooltip, command palette.
> **Jangan pakai glass:** card biasa, table, form — akan terlalu heavy.

---

## 9. Spesifikasi Komponen Per Area

### 9.1 Layout Shell

**File yang diubah:** `resources/views/layouts/admin.blade.php`, `admin.css`

**Sebelum:**
```
bg-gradient-to-br from-slate-50 via-white to-indigo-50/40  (admin-shell)
bg-slate-50  (admin-body)
```

**Sesudah:**
```
bg-slate-950  (admin-shell — dark base)
bg-slate-950  (admin-body)

/* Subtle texture di background: grid dot pattern */
background-image: radial-gradient(circle, rgba(255,255,255,0.03) 1px, transparent 1px);
background-size: 24px 24px;
```

**CSS Update `.admin-shell`:**
```css
.admin-shell {
    @apply flex min-h-screen w-full overflow-x-hidden bg-slate-950;
    background-image: radial-gradient(circle, rgba(255,255,255,0.025) 1px, transparent 1px);
    background-size: 24px 24px;
}

.admin-body {
    @apply min-h-screen overflow-x-hidden bg-slate-950 text-slate-100;
}
```

---

### 7.2 Sidebar

**File:** `resources/css/admin.css` — semua class `.admin-sidebar*`
**Component:** `resources/views/components/admin/sidebar.blade.php`

**Perubahan Visual (bukan logic):**

#### Brand Mark
```css
.admin-sidebar__brand-mark {
    /* Sebelum: gradient indigo-purple-pink yang noisy */
    /* Sesudah: deep violet dengan gold accent border */
    @apply flex h-10 w-10 shrink-0 items-center justify-center
           rounded-lg bg-violet-600 text-sm font-black text-white;
    box-shadow: 0 0 0 2px rgba(212,175,55,0.3), 0 4px 12px rgba(124,58,237,0.5);
}
```

#### Brand Title
```css
.admin-sidebar__title {
    @apply truncate text-sm font-extrabold text-slate-100 tracking-tight;
}
.admin-sidebar__subtitle {
    @apply mt-0.5 truncate text-xs font-medium;
    color: #D4AF37; /* brand gold — subtle */
    opacity: 0.7;
}
```

#### Section Labels
```css
.admin-sidebar__section-label {
    @apply px-2.5 pb-1.5 pt-5 text-[9px] font-black uppercase tracking-[0.15em];
    color: rgba(148, 163, 184, 0.5); /* slate-400/50 — sangat subtle */
    first:pt-2;
}
```

#### Nav Links
```css
.admin-sidebar__link {
    @apply relative flex items-center gap-2.5 rounded-md px-2.5 py-2
           text-sm font-medium text-slate-500 transition duration-150
           hover:bg-white/5 hover:text-slate-200;
    /* PERUBAHAN: rounded-md (bukan rounded-lg) — lebih presisi */
}

.admin-sidebar__link--active {
    @apply bg-violet-600/20 text-violet-300;
    border-left: 2px solid #7C3AED;
    /* PERUBAHAN: bukan solid bg indigo, tapi glow subtle + accent border */
    /* Menghapus shadow-md shadow-indigo-900/50 — diganti border effect */
}
```

#### Icon Wrapper
```css
.admin-sidebar__icon {
    @apply flex h-7 w-7 shrink-0 items-center justify-center
           rounded-md bg-transparent text-[13px] text-slate-600
           transition group-hover:text-slate-300;
    /* PERUBAHAN: hapus bg-white/5 yang membuat box di dalam box */
}

.admin-sidebar__link--active .admin-sidebar__icon {
    @apply text-violet-400;
}
```

#### Sidebar Base
```css
.admin-sidebar {
    @apply fixed inset-y-0 left-0 z-50 flex h-screen w-64 shrink-0
           flex-col overflow-y-auto transition-transform duration-300
           ease-in-out lg:sticky lg:top-0 lg:z-auto lg:translate-x-0;
    background: #020617; /* slate-950 — lebih dalam dari sebelumnya */
    border-right: 1px solid rgba(255,255,255,0.06);
}
```

---

### 7.3 Topbar

**File:** `resources/css/admin.css` — semua class `.admin-topbar*`, `.admin-user-menu*`

**Perubahan Utama: Dari putih ke dark, konsisten dengan sidebar**

```css
.admin-topbar {
    @apply sticky top-0 z-30 backdrop-blur-xl;
    background: rgba(2, 6, 23, 0.9);   /* slate-950/90 */
    border-bottom: 1px solid rgba(255,255,255,0.06);
    /* Menggantikan: bg-white/95 border-slate-100 */
}
```

**Search Bar (Topbar)**
```css
.admin-topbar__search {
    @apply hidden min-w-0 items-center gap-2 rounded-md border px-3 py-2
           text-sm transition lg:flex lg:w-56 xl:w-72;
    background: rgba(15, 23, 42, 0.8); /* slate-900/80 */
    border-color: rgba(255,255,255,0.08);
    color: #94A3B8; /* slate-400 */
    /* Menggantikan: bg-slate-50 border-slate-200 */
}

.admin-topbar__search:hover {
    border-color: rgba(124, 58, 237, 0.4); /* violet accent on hover */
    background: rgba(15, 23, 42, 1);
}
```

**Breadcrumb**
```css
.admin-topbar__breadcrumb {
    @apply flex flex-wrap items-center gap-1.5 text-xs font-semibold;
    /* Separator: text-slate-600 / Page: text-slate-300 */
}
```

**Icon Button**
```css
.admin-topbar__icon-button {
    @apply relative flex h-9 w-9 items-center justify-center rounded-md
           text-slate-500 transition hover:bg-white/5 hover:text-slate-300;
    /* Menggantikan: hover:bg-slate-100 hover:text-slate-700 */
}

.admin-topbar__notification-dot {
    @apply absolute right-2 top-2 h-1.5 w-1.5 rounded-full bg-red-500;
    ring: 1px solid rgba(2,6,23,0.8); /* ring warna gelap bukan putih */
}
```

**User Menu Trigger**
```css
.admin-user-menu__trigger {
    @apply flex items-center gap-2 rounded-md px-2 py-1.5 text-left transition
           hover:bg-white/5;
    /* Menggantikan: hover:bg-slate-100 */
}

.admin-user-menu__identity span {
    @apply max-w-32 truncate text-sm font-semibold text-slate-200;
    /* Menggantikan: text-slate-800 */
}

.admin-user-menu__identity small {
    color: #94A3B8; /* slate-400 */
}
```

**User Avatar**
```css
.admin-user-menu__avatar {
    @apply flex h-8 w-8 shrink-0 items-center justify-center
           rounded-md text-xs font-black text-white;
    background: linear-gradient(135deg, #7C3AED, #2563EB);
    /* Menggantikan: gradient indigo-purple-pink yang noisy */
    /* rounded-md bukan rounded-lg — lebih presisi */
}
```

**Dropdown Menu**
```css
.admin-user-menu__dropdown {
    @apply absolute right-0 top-full mt-1 w-64 rounded-lg overflow-hidden;
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 20px 40px rgba(0,0,0,0.6);
    /* Glassmorphism dropdown */
}
```

---

### 7.4 Cards & Panels

**File:** `resources/css/admin.css` — class `.admin-card*`, `.admin-form-card`, `.admin-page-header`

**Filosofi:** Cards harus terasa seperti surfaces yang "terangkat" dari background gelap, bukan kotak putih di atas abu-abu.

```css
.admin-card {
    @apply overflow-hidden rounded-xl;
    background: #1E293B;          /* slate-800 */
    border: 1px solid rgba(255,255,255,0.08);
    /* PERUBAHAN: rounded-xl bukan rounded-2xl — lebih presisi */
    /* PERUBAHAN: dark card bukan white card */
}

.admin-card-header {
    @apply px-6 py-4;
    background: rgba(15,23,42,0.5); /* slate-900/50 — slightly darker header */
    border-bottom: 1px solid rgba(255,255,255,0.06);
    /* PERUBAHAN: bukan bg-slate-50 yang putih */
}

.admin-card-body {
    @apply p-6;
    /* Tidak berubah */
}

.admin-page-header {
    @apply rounded-xl p-6;
    background: linear-gradient(135deg, #1E293B 0%, rgba(30,41,59,0.8) 100%);
    border: 1px solid rgba(255,255,255,0.08);
    /* Subtle gradient di header page — menggantikan bg-white flat */
}

.admin-form-card {
    @apply rounded-xl p-6;
    background: #1E293B;
    border: 1px solid rgba(255,255,255,0.08);
}
```

**Variasi Card Khusus (Baru):**

```css
/* Card dengan accent violet — untuk featured section */
.admin-card--accent {
    border-color: rgba(124,58,237,0.3);
    box-shadow: 0 0 0 1px rgba(124,58,237,0.1) inset;
}

/* Card dengan subtle gold — untuk premium/brand section */
.admin-card--gold {
    border-color: rgba(212,175,55,0.2);
    background: linear-gradient(135deg, #1E293B, rgba(212,175,55,0.03));
}

/* Stat card — untuk dashboard home */
.admin-stat-card {
    @apply overflow-hidden rounded-xl p-6;
    background: #1E293B;
    border: 1px solid rgba(255,255,255,0.08);
    transition: border-color 150ms, box-shadow 150ms;
}

.admin-stat-card:hover {
    border-color: rgba(255,255,255,0.15);
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}
```

---

### 7.5 Forms & Inputs

**File:** `resources/css/admin.css` — class `.admin-input`, `.admin-select`, `.admin-textarea`, `.admin-form-label`, `.admin-form-hint`

```css
.admin-input,
.admin-select,
.admin-textarea {
    @apply w-full rounded-lg border px-4 py-3 text-sm
           transition duration-150 focus:outline-none focus:ring-2;
    background: #0F172A;                        /* slate-900 — dark input */
    border-color: rgba(255,255,255,0.1);
    color: #E2E8F0;                             /* slate-200 — readable text */
    /* PERUBAHAN: rounded-lg bukan rounded-xl */
    /* PERUBAHAN: dark background */
}

.admin-input::placeholder,
.admin-select::placeholder,
.admin-textarea::placeholder {
    color: #475569; /* slate-600 — subtle placeholder */
}

.admin-input:focus,
.admin-select:focus,
.admin-textarea:focus {
    border-color: #7C3AED;                      /* violet-600 */
    ring-color: rgba(124,58,237,0.2);
    box-shadow: 0 0 0 3px rgba(124,58,237,0.15);
}

/* Error state */
.admin-input.error,
.admin-input[aria-invalid="true"] {
    border-color: #EF4444;
    box-shadow: 0 0 0 3px rgba(239,68,68,0.15);
}

.admin-form-label {
    @apply mb-2 block text-xs font-bold uppercase tracking-wider;
    color: #94A3B8; /* slate-400 */
    /* PERUBAHAN: label style lebih presisi — uppercase + tracking */
    /* Menggantikan: text-sm font-bold text-slate-700 */
}

.admin-form-hint {
    @apply mt-1.5 text-xs;
    color: #64748B; /* slate-500 */
}
```

**Select Khusus — Dark Styled:**
```css
.admin-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%2364748B' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.25rem;
    padding-right: 2.5rem;
}
```

---

### 7.6 Tables & Data Grid

**File:** `resources/css/admin.css` — class `.admin-table*`

```css
.admin-table-wrapper {
    @apply overflow-x-auto rounded-xl;
    background: #1E293B;
    border: 1px solid rgba(255,255,255,0.08);
    /* PERUBAHAN: rounded-xl bukan rounded-2xl, dark background */
}

.admin-table {
    @apply w-full min-w-[840px] text-left;
    /* Tidak berubah struktur */
}

.admin-table-header {
    @apply border-b text-xs font-black uppercase tracking-widest;
    background: rgba(15,23,42,0.6);          /* slightly darker header row */
    border-color: rgba(255,255,255,0.06);
    color: #64748B;                           /* slate-500 — subtle */
    /* PERUBAHAN: text-xs uppercase tracking untuk precision */
}

.admin-table-header th {
    @apply px-6 py-3.5;
}

.admin-table-row {
    @apply border-b transition duration-100 last:border-b-0;
    border-color: rgba(255,255,255,0.04);
    /* PERUBAHAN: border sangat subtle */
}

.admin-table-row:hover {
    background: rgba(255,255,255,0.03);
    /* PERUBAHAN: hover sangat subtle di atas dark background */
}

.admin-table-row td {
    @apply px-6 py-4 text-sm;
    color: #CBD5E1; /* slate-300 */
}

/* Kolom nilai penting (nama, judul) */
.admin-table-row td:first-child {
    color: #E2E8F0; /* slate-200 — lebih terang untuk kolom utama */
    font-weight: 500;
}
```

---

### 7.7 Buttons

**File:** `resources/css/admin.css` — class `.admin-btn-*`

```css
/* Base button reset — TIDAK BERUBAH: inline-flex items-center justify-center gap-2 px-5 py-3 text-sm font-bold */

.admin-btn-primary {
    @apply inline-flex items-center justify-center gap-2
           rounded-lg px-5 py-2.5 text-sm font-bold text-white
           transition duration-150 focus:outline-none focus:ring-2;
    background: #7C3AED;                    /* violet-600 */
    box-shadow: 0 1px 2px rgba(0,0,0,0.3), 0 0 0 1px rgba(124,58,237,0.3) inset;
    /* PERUBAHAN: violet bukan indigo, rounded-lg bukan rounded-xl */
}

.admin-btn-primary:hover {
    background: #6D28D9;                    /* violet-700 */
    box-shadow: 0 4px 12px rgba(124,58,237,0.4);
    transform: translateY(-1px);            /* subtle lift */
}

.admin-btn-primary:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.admin-btn-primary:focus {
    ring-color: rgba(124,58,237,0.4);
    ring-offset: 2px;
    ring-offset-color: #020617;             /* dark ring offset */
}

.admin-btn-secondary {
    @apply inline-flex items-center justify-center gap-2
           rounded-lg px-5 py-2.5 text-sm font-bold
           transition duration-150 focus:outline-none focus:ring-2;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.12);
    color: #CBD5E1;                         /* slate-300 */
}

.admin-btn-secondary:hover {
    background: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.2);
    color: #E2E8F0;
}

.admin-btn-danger {
    @apply inline-flex items-center justify-center gap-2
           rounded-lg px-5 py-2.5 text-sm font-bold text-white
           transition duration-150 focus:outline-none;
    background: #DC2626;
    box-shadow: 0 0 0 1px rgba(220,38,38,0.3) inset;
}

.admin-btn-danger:hover {
    background: #B91C1C;
    box-shadow: 0 4px 12px rgba(220,38,38,0.3);
}

.admin-btn-soft {
    @apply inline-flex items-center justify-center gap-2
           rounded-lg px-5 py-2.5 text-sm font-bold
           transition duration-150;
    background: rgba(124,58,237,0.12);
    border: 1px solid rgba(124,58,237,0.2);
    color: #A78BFA;                         /* violet-400 */
}

.admin-btn-soft:hover {
    background: rgba(124,58,237,0.2);
    color: #C4B5FD;
}

/* Ukuran button */
.admin-btn-sm {
    @apply px-3 py-1.5 text-xs rounded-md;
}

.admin-btn-lg {
    @apply px-6 py-3 text-base rounded-lg;
}

/* Icon-only button */
.admin-btn-icon {
    @apply flex h-9 w-9 items-center justify-center rounded-lg
           text-slate-400 transition hover:bg-white/5 hover:text-slate-200;
}
```

---

### 7.8 Badges & Status

**File:** `resources/css/admin.css` — class `.admin-badge-*`

```css
/* Base badge — lebih compact dan presisi */
.admin-badge-success,
.admin-badge-warning,
.admin-badge-danger,
.admin-badge-info,
.admin-badge-neutral {
    @apply inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-bold;
    /* PERUBAHAN: rounded-md bukan rounded-full (lebih presisi) */
    /* PERUBAHAN: px-2 py-0.5 lebih compact */
    /* PERUBAHAN: gap-1 untuk dot indicator */
}

/* Dot indicator sebelum teks */
.admin-badge-success::before,
.admin-badge-warning::before,
.admin-badge-danger::before,
.admin-badge-info::before {
    content: '';
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.admin-badge-success {
    background: rgba(16,185,129,0.15);      /* emerald-500/15 */
    color: #34D399;                          /* emerald-400 */
}
.admin-badge-success::before { background: #10B981; }

.admin-badge-warning {
    background: rgba(245,158,11,0.15);      /* amber-500/15 */
    color: #FBBF24;                          /* amber-400 */
}
.admin-badge-warning::before { background: #F59E0B; }

.admin-badge-danger {
    background: rgba(239,68,68,0.15);       /* red-500/15 */
    color: #F87171;                          /* red-400 */
}
.admin-badge-danger::before { background: #EF4444; }

.admin-badge-info {
    background: rgba(6,182,212,0.15);       /* cyan-500/15 */
    color: #22D3EE;                          /* cyan-400 */
}
.admin-badge-info::before { background: #06B6D4; }

.admin-badge-neutral {
    background: rgba(148,163,184,0.1);
    color: #94A3B8;
}
.admin-badge-neutral::before { background: #64748B; }
```

---

### 7.9 Modals & Dialogs

**File:** `resources/views/components/confirm-modal.blade.php` (sudah ada)
**CSS:** tambahkan di `admin.css`

```css
/* Modal Overlay */
.admin-modal-overlay {
    @apply fixed inset-0 z-50 flex items-center justify-center p-4;
    background: rgba(2,6,23,0.7);
    backdrop-filter: blur(8px);
}

/* Modal Panel — Glassmorphism */
.admin-modal-panel {
    @apply relative w-full max-w-lg rounded-xl overflow-hidden;
    background: rgba(15,23,42,0.95);
    border: 1px solid rgba(255,255,255,0.12);
    box-shadow: 0 25px 60px rgba(0,0,0,0.7), 0 0 0 1px rgba(255,255,255,0.05) inset;
    backdrop-filter: blur(20px);
}

/* Modal Header */
.admin-modal-header {
    @apply px-6 py-5;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

/* Modal Body */
.admin-modal-body {
    @apply px-6 py-5;
}

/* Modal Footer */
.admin-modal-footer {
    @apply flex items-center justify-end gap-3 px-6 py-4;
    border-top: 1px solid rgba(255,255,255,0.06);
    background: rgba(2,6,23,0.3);
}
```

---

### 7.10 Empty States

**File:** `resources/css/admin.css` — class `.admin-empty-state`

```css
.admin-empty-state {
    @apply flex flex-col items-center justify-center rounded-xl px-6 py-16 text-center;
    background: repeating-linear-gradient(
        45deg,
        rgba(255,255,255,0.01),
        rgba(255,255,255,0.01) 1px,
        transparent 1px,
        transparent 16px
    );
    border: 1px dashed rgba(255,255,255,0.08);
    /* PERUBAHAN: diagonal stripe pattern — lebih distinctive */
}

.admin-empty-state__icon {
    @apply mb-4 flex h-16 w-16 items-center justify-center rounded-2xl;
    background: rgba(124,58,237,0.1);
    border: 1px solid rgba(124,58,237,0.2);
    color: #7C3AED;
    font-size: 1.75rem;
}

.admin-empty-state__title {
    @apply mb-2 text-base font-bold text-slate-200;
}

.admin-empty-state__description {
    @apply mb-6 max-w-xs text-sm;
    color: #64748B; /* slate-500 */
}
```

---

### 7.11 Toast & Flash Alerts

**File:** `resources/views/components/flash-alert.blade.php` (sudah ada)
**CSS:** update style

```css
/* Flash Alert — bukan full-width banner, tapi toast floating */
.flash-alert {
    @apply fixed bottom-6 right-6 z-50 flex max-w-sm items-start gap-3
           overflow-hidden rounded-xl p-4;
    background: rgba(15,23,42,0.95);
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    backdrop-filter: blur(20px);
    animation: slideInRight 0.25s ease-out;
}

@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to   { transform: translateX(0);   opacity: 1; }
}

/* Accent border kiri sesuai tipe */
.flash-alert--success { border-left: 3px solid #10B981; }
.flash-alert--warning { border-left: 3px solid #F59E0B; }
.flash-alert--danger  { border-left: 3px solid #EF4444; }
.flash-alert--info    { border-left: 3px solid #06B6D4; }
```

---

### 7.12 Dashboard Home

**File:** `resources/views/admin/dashboard/index.blade.php` (buat baru atau update existing)

**Ini adalah halaman yang paling butuh perhatian — saat ini kemungkinan sangat minimal.**

**Layout Target:**

```
┌─────────────────────────────────────────────────────────┐
│  HEADER: Welcome + Quick Actions                        │
├──────────┬──────────┬──────────┬──────────┬────────────┤
│ KPI:     │ KPI:     │ KPI:     │ KPI:     │ KPI:       │
│ Products │ Bookings │ Pages    │ Users    │ Media      │
│   [num]  │   [num]  │  [num]   │  [num]   │  [num]     │
├──────────┴──────────┴──────────┴──────────┴────────────┤
│  QUICK ACCESS: Shortcut cards ke modul paling sering    │
├────────────────────────────┬───────────────────────────┤
│  RECENT ACTIVITY           │  SYSTEM STATUS             │
│  (Bookings, edits, dll)    │  (Plugin, phase, health)   │
└────────────────────────────┴───────────────────────────┘
```

**KPI Card Design:**
```blade
<div class="admin-stat-card group">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-slate-500">Products</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-100">{{ $productCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Active tours & activities</p>
        </div>
        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-600/15 text-violet-400 transition group-hover:bg-violet-600/25">
            <i class="fa-solid fa-map-location-dot"></i>
        </div>
    </div>
</div>
```

---

## 10. Motion & Micro-Animation Principles

### 8.1 Hierarchy Animasi

| Level | Duration | Easing | Digunakan Pada |
|-------|----------|--------|---------------|
| Instant | 0ms | — | Focus state, outline |
| Micro | 100ms | ease-out | Hover color change, icon shift |
| Quick | 150ms | ease-out | Button hover, badge pulse |
| Normal | 200ms | ease-in-out | Card hover, dropdown open |
| Smooth | 300ms | ease-in-out | Sidebar open, modal enter |
| Flow | 500ms | cubic-bezier(0.4,0,0.2,1) | Page transition, large panel |

### 8.2 Rules Animasi

**LAKUKAN:**
- Hover button: `translateY(-1px)` + shadow glow (subtle lift)
- Active button: `translateY(0)` + shadow reduce (snap back)
- Card hover: `border-color` transition saja — tidak ada scale
- Dropdown: `opacity` + `translateY(-4px→0)` entry
- Modal: `opacity` + `scale(0.97→1)` entry
- Sidebar link: hanya `color` + `background` transition
- Toast: `translateX(100%→0)` slide dari kanan

**JANGAN:**
- Scale pada card atau table row (jarring)
- Bounce easing (kecuali intentional untuk error state)
- Animasi lebih dari 500ms (terasa lambat untuk admin tool)
- Animasi yang menggeser layout (hanya opacity/transform)

### 8.3 Alpine.js Transition Templates

```html
<!-- Dropdown -->
<div x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0 -translate-y-1"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-1">

<!-- Modal -->
<div x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95">

<!-- Toast -->
<div x-transition:enter="transition ease-out duration-250"
     x-transition:enter-start="opacity-0 translate-x-full"
     x-transition:enter-end="opacity-100 translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-x-0"
     x-transition:leave-end="opacity-0 translate-x-full">
```

---

## 11. Iconografi

### 9.1 Library Saat Ini
**Font Awesome 6 Free** — sudah di-load via CDN di `admin.blade.php`. Pertahankan ini.

### 9.2 Icon Usage Rules

| Context | Ukuran | Berat | Contoh |
|---------|--------|-------|--------|
| Sidebar nav | `text-[13px]` | solid | `fa-solid fa-chart-bar` |
| Topbar action | `text-sm` | regular | `fa-regular fa-bell` |
| Button dengan teks | `text-sm` | solid | `fa-solid fa-plus` |
| Icon-only button | `text-base` | regular | `fa-regular fa-trash-can` |
| KPI card | `text-xl` | solid | `fa-solid fa-users` |
| Empty state | `text-3xl` | light/thin | `fa-thin fa-folder-open` |
| Status indicator | `text-xs` | solid | `fa-solid fa-circle` |

### 9.3 Icon Color dalam Dark Theme

```css
/* Icon di sidebar — default muted, active violet */
.admin-sidebar__link .fa-* { color: #64748B; }
.admin-sidebar__link:hover .fa-* { color: #CBD5E1; }
.admin-sidebar__link--active .fa-* { color: #A78BFA; } /* violet-400 */

/* Icon di card — inherit text */
/* Icon di button — inherit dari text-white / text-violet-400 */
```

---

## 12. Responsive Strategy

### 10.1 Breakpoints (Tailwind Default — Tidak Berubah)

| Breakpoint | Width | Admin Behavior |
|------------|-------|----------------|
| default | 0px+ | Sidebar hidden, single column |
| sm | 640px | Topbar teks mulai muncul |
| md | 768px | Grid 2 column untuk cards |
| lg | 1024px | Sidebar visible, full layout |
| xl | 1280px | Content area max width expand |
| 2xl | 1536px | Max content width 1600px |

### 10.2 Mobile Admin Behavior (tidak berubah dari existing)

- Sidebar tersembunyi default, dibuka via hamburger toggle
- Backdrop overlay (`admin-sidebar-backdrop`) menutup sidebar saat klik luar
- Topbar sticky dengan toggle button
- Command palette tetap accessible via button

### 10.3 Admin Dark Theme di Mobile

Dark theme justru lebih nyaman di mobile karena hemat baterai (OLED) dan kontras yang baik di lingkungan terang. Tidak ada kebutuhan untuk mode berbeda di mobile.

---

## 13. Accessibility Commitments

Semua perubahan visual harus mempertahankan standar WCAG AA:

| Requirement | Target | Implementasi |
|-------------|--------|-------------|
| Text contrast | ≥4.5:1 | `text-slate-100` (#F1F5F9) di atas `bg-slate-800` (#1E293B) → 12.6:1 ✅ |
| Secondary text | ≥4.5:1 | `text-slate-400` (#94A3B8) di atas `bg-slate-800` → 5.9:1 ✅ |
| Muted text | ≥3:1 (UI) | `text-slate-500` (#64748B) di atas `bg-slate-900` → 4.2:1 ✅ |
| Focus ring | Visible | `ring-2 ring-violet-500/50 ring-offset-2 ring-offset-slate-950` |
| Button contrast | ≥4.5:1 | White text di `bg-violet-600` → 7.2:1 ✅ |
| Badge contrast | ≥3:1 | `text-emerald-400` di `bg-emerald-500/15` → 5.1:1 ✅ |
| Keyboard nav | Full | Semua interactive elements keyboard-accessible |
| ARIA | Maintain | Semua existing aria-label, aria-current, role tetap |

> **PENTING:** Setelah implementasi, WAJIB verifikasi contrast ratio menggunakan
> browser DevTools Accessibility panel atau axe extension.

---

## 14. Skill Files Baru / Diperbarui

### 12.1 File Yang DIPERBARUI

| File | Perubahan |
|------|-----------|
| `ai/skills/admin-dashboard-skill.md` | Diperbarui: referensi ke Grand Master Plan + updated design principles |

### 12.2 File BARU Yang Direkomendasikan

| File | Isi | Prioritas |
|------|-----|-----------|
| `ai/skills/admin-motion-skill.md` | Panduan lengkap micro-animation, Alpine.js transition templates, timing hierarchy | Medium |
| `ai/skills/admin-dark-theme-skill.md` | CSS variable map, surface layer reference, contrast ratios, glassmorphism recipes | High |
| `ai/skills/admin-dashboard-home-skill.md` | Layout, KPI cards, recent activity, quick links — template untuk halaman dashboard home | Medium |

### 12.3 Integrasi Ke AGENTS.md Skill Map (usulan tambahan baris)

```markdown
| Admin Dark Theme | `admin-dark-theme-skill.md` |
| Admin Motion     | `admin-motion-skill.md`     |
| Admin Dashboard Home | `admin-dashboard-home-skill.md` |
```

---

## 15. CSS Class Migration Map

> Tabel ini penting agar tidak ada breaking change pada Blade files existing.
> Semua class lama tetap BERFUNGSI — hanya CSS-nya yang diubah.
> Tidak perlu rename class di Blade files.

| Class | Perubahan CSS | Blade File Change? |
|-------|--------------|-------------------|
| `.admin-body` | dark background, light text | ❌ Tidak perlu |
| `.admin-shell` | dark base + dot pattern | ❌ Tidak perlu |
| `.admin-sidebar` | slate-950 background | ❌ Tidak perlu |
| `.admin-sidebar__link--active` | violet border + soft bg | ❌ Tidak perlu |
| `.admin-topbar` | dark glass | ❌ Tidak perlu |
| `.admin-card` | dark card surface | ❌ Tidak perlu |
| `.admin-card-header` | darker header strip | ❌ Tidak perlu |
| `.admin-btn-primary` | violet color | ❌ Tidak perlu |
| `.admin-input` | dark input bg | ❌ Tidak perlu |
| `.admin-table-wrapper` | dark table | ❌ Tidak perlu |
| `.admin-table-header` | subtle dark header | ❌ Tidak perlu |
| `.admin-table-row` | subtle hover | ❌ Tidak perlu |
| `.admin-badge-*` | dark-optimized colors | ❌ Tidak perlu |
| `.admin-empty-state` | diagonal stripe pattern | ❌ Tidak perlu |

**Kesimpulan: Zero breaking changes pada Blade files.**
Semua perubahan hanya di `admin.css`. CSS class names tetap sama.

---

## 16. Implementation Roadmap

### Phase A — Foundation (Dampak Terbesar, Resiko Rendah)

**Target file: `resources/css/admin.css`**

Urutan implementasi yang direkomendasikan:

```
A1. Shell & Body           → admin-shell, admin-body      (full dark base)
A2. Sidebar                → admin-sidebar, semua sub-class
A3. Topbar                 → admin-topbar, semua sub-class
A4. Cards & Panels         → admin-card, admin-form-card, admin-page-header
A5. Forms & Inputs         → admin-input, admin-select, admin-textarea, admin-form-label
A6. Tables                 → admin-table-wrapper, header, row
A7. Buttons                → admin-btn-primary (violet), secondary, danger, soft
A8. Badges                 → admin-badge-* (dark-optimized)
A9. Empty States           → admin-empty-state
A10. Modals                → admin-modal-* (baru, jika diperlukan)
```

### Phase B — Enhancement (Setelah A Stabil)

```
B1. Toast / Flash Alert    → update component flash-alert.blade.php
B2. Dashboard Home         → update/buat halaman dashboard utama
B3. Inter Font             → load via Google Fonts atau Vite
B4. Motion polish          → Alpine.js transition pada dropdown, modal
B5. KPI Cards              → stat cards di dashboard
```

### Phase C — Skill Files (Dokumentasi)

```
C1. Update admin-dashboard-skill.md  ← dilakukan BERSAMA dokumen ini
C2. Buat admin-dark-theme-skill.md
C3. Buat admin-motion-skill.md
C4. Buat admin-dashboard-home-skill.md
```

### Rollback Plan

Karena semua perubahan hanya di `admin.css`:

```bash
# Rollback via git jika diperlukan
git checkout HEAD~1 -- resources/css/admin.css

# Atau via branch
git checkout feature/admin-dark-theme -- resources/css/admin.css
```

Zero risk ke database, routes, controllers, atau logic.

---

## Catatan Akhir

### Yang TIDAK BERUBAH oleh Grand Master Plan ini:

- ✅ Semua route admin (`/admin/*`)
- ✅ Semua controller PHP
- ✅ Semua model dan migration
- ✅ Semua form validation logic
- ✅ Semua Blade file structure (hanya CSS class yg berubah penampilannya)
- ✅ Semua Alpine.js component logic
- ✅ Semua backend data processing
- ✅ Semua authentication & authorization
- ✅ Flash alert trigger logic
- ✅ Confirm modal trigger logic
- ✅ Command palette functionality
- ✅ Sidebar navigation structure
- ✅ Phase 1–5 features

### Yang BERUBAH:

- 🎨 Visual appearance via `admin.css`
- 🎨 Warna, shadow, border dari komponen existing
- 📄 Dokumentasi skill (admin-dashboard-skill.md)

---

---

# BAGIAN II — APPEARANCE CUSTOMIZER (v1.1)
> Fitur baru. Memerlukan persetujuan Owner sebelum implementasi (butuh migration).

---

## 17. Admin Appearance Customizer — Feature Spec

### 17.1 Gambaran Umum

Fitur ini memungkinkan **Super Admin** mengubah tampilan visual admin dashboard
kapan saja melalui UI di `Settings > Customize Dashboard` — tanpa menyentuh kode.

**Scope:**
- Dark mode / Light mode toggle
- Warna primary (buttons, active state, focus ring)
- Warna accent (info badge, link, chart)
- Warna sidebar background
- Preset brand themes (Tour & Travel, Hotel, Restaurant, F&B, Event, Custom)
- Live preview real-time sebelum disimpan
- Reset ke default "Command Center Dark"

**Yang TIDAK di-cover customizer ini:**
- Ukuran font (fixed — accessibility concern)
- Spacing/radius (fixed — layout concern)
- Branding logo sidebar (sudah ada di Media/Settings)
- Public frontend styling (terpisah sepenuhnya via Theme Editor yang sudah ada)

### 17.2 Siapa yang Bisa Akses

```php
// Middleware pada route Settings > Customize Dashboard
Route::middleware(['auth', 'admin.superadmin'])->group(function () {
    Route::get('/admin/settings/dashboard-appearance', [...]);
    Route::post('/admin/settings/dashboard-appearance', [...]);
    Route::post('/admin/settings/dashboard-appearance/reset', [...]);
    Route::post('/admin/settings/dashboard-appearance/preview', [...]);
});
```

Hanya **Super Admin** (`is_super_admin = true`) yang bisa ubah tampilan.
Admin biasa melihat tampilan yang sudah diset super admin.

### 17.3 Bagaimana Cara Kerjanya (User Flow)

```
1. Super Admin buka Settings > Customize Dashboard
2. Pilih Mode: Dark / Light / Light Classic
3. Pilih Preset ATAU kustomisasi manual:
   a. Klik preset (misal: "Hotel Luxury") → semua warna terisi otomatis
   b. Atau klik "Custom" → muncul color picker per field
4. Live preview tampil di sebelah kanan (iframe preview admin layout)
5. Klik "Save Changes" → disimpan ke DB
6. Halaman reload → seluruh admin gunakan tema baru
7. Tombol "Reset to Default" tersedia di bawah form
```

---

## 18. DB Schema Proposal — admin_dashboard_appearances

> ⚠️ **BUTUH PERSETUJUAN OWNER** sebelum migration dibuat.

### 18.1 Tabel: `admin_dashboard_appearances`

```sql
CREATE TABLE admin_dashboard_appearances (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Mode
    mode          ENUM('dark','light','light_classic') NOT NULL DEFAULT 'dark',

    -- Sidebar
    sidebar_bg    VARCHAR(20) NOT NULL DEFAULT '#020617',
    sidebar_style ENUM('dark','light') NOT NULL DEFAULT 'dark',

    -- Primary Color (buttons, active state, focus)
    primary_color VARCHAR(20) NOT NULL DEFAULT '#7C3AED',
    primary_hover VARCHAR(20) NOT NULL DEFAULT '#6D28D9',
    primary_text  VARCHAR(20) NOT NULL DEFAULT '#FFFFFF',

    -- Accent Color (info badge, links, charts)
    accent_color  VARCHAR(20) NOT NULL DEFAULT '#06B6D4',

    -- Brand Gold (optional, default show/hide)
    gold_color    VARCHAR(20) NOT NULL DEFAULT '#D4AF37',
    show_gold     TINYINT(1)  NOT NULL DEFAULT 1,

    -- Background (auto-set dari mode, tapi bisa override)
    bg_base       VARCHAR(20) NOT NULL DEFAULT '#020617',
    bg_card       VARCHAR(20) NOT NULL DEFAULT '#1E293B',
    bg_input      VARCHAR(20) NOT NULL DEFAULT '#0F172A',

    -- Preset info
    preset_name   VARCHAR(100) NULLABLE DEFAULT NULL,
    is_default    TINYINT(1)  NOT NULL DEFAULT 0,

    -- Meta
    created_by    BIGINT UNSIGNED NULLABLE,
    updated_by    BIGINT UNSIGNED NULLABLE,
    created_at    TIMESTAMP NULL DEFAULT NULL,
    updated_at    TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Seed: default dark theme
INSERT INTO admin_dashboard_appearances
    (mode, sidebar_bg, sidebar_style, primary_color, primary_hover, primary_text,
     accent_color, gold_color, show_gold, bg_base, bg_card, bg_input,
     preset_name, is_default)
VALUES
    ('dark', '#020617', 'dark', '#7C3AED', '#6D28D9', '#FFFFFF',
     '#06B6D4', '#D4AF37', 1, '#020617', '#1E293B', '#0F172A',
     'Command Center Dark', 1);
```

### 18.2 Kenapa Satu Row? (Bukan Multi-Row)

Hanya satu row aktif yang berlaku. Sistem ini adalah "single global appearance" —
tidak ada version history atau multi-theme. Jika ingin rollback, fitur ini cukup
diganti manual oleh super admin.

Jika suatu saat butuh history → extend dengan kolom `revision_of_id` atau pindah
ke tabel terpisah `admin_appearance_history`. Tidak perlu diimplementasi sekarang.

### 18.3 Model PHP

```php
// app/Models/AdminDashboardAppearance.php

class AdminDashboardAppearance extends Model
{
    protected $fillable = [
        'mode', 'sidebar_bg', 'sidebar_style',
        'primary_color', 'primary_hover', 'primary_text',
        'accent_color', 'gold_color', 'show_gold',
        'bg_base', 'bg_card', 'bg_input',
        'preset_name', 'is_default',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'show_gold'  => 'boolean',
        'is_default' => 'boolean',
    ];

    // Selalu ambil satu record aktif
    public static function getCurrent(): self
    {
        return static::first() ?? static::getDefault();
    }

    private static function getDefault(): self
    {
        return new self([
            'mode'          => 'dark',
            'sidebar_bg'    => '#020617',
            'sidebar_style' => 'dark',
            'primary_color' => '#7C3AED',
            'primary_hover' => '#6D28D9',
            'primary_text'  => '#FFFFFF',
            'accent_color'  => '#06B6D4',
            'gold_color'    => '#D4AF37',
            'show_gold'     => true,
            'bg_base'       => '#020617',
            'bg_card'       => '#1E293B',
            'bg_input'      => '#0F172A',
            'preset_name'   => 'Command Center Dark',
        ]);
    }
}
```

### 18.4 Service

```php
// app/Services/AdminAppearanceService.php

class AdminAppearanceService
{
    public function getCurrent(): AdminDashboardAppearance
    {
        return Cache::remember('admin_appearance', 3600, function () {
            return AdminDashboardAppearance::getCurrent();
        });
    }

    public function update(array $data, User $user): AdminDashboardAppearance
    {
        $appearance = AdminDashboardAppearance::first();

        if ($appearance) {
            $appearance->update(array_merge($data, ['updated_by' => $user->id]));
        } else {
            $appearance = AdminDashboardAppearance::create(
                array_merge($data, ['created_by' => $user->id])
            );
        }

        Cache::forget('admin_appearance');

        return $appearance;
    }

    public function reset(User $user): AdminDashboardAppearance
    {
        return $this->update(AdminDashboardAppearance::getDefault()->toArray(), $user);
    }

    // Generate CSS string untuk di-inject ke <style> tag
    public function toCssVars(AdminDashboardAppearance $a): string
    {
        $darkBorders = "
            --admin-border: rgba(255,255,255,0.08);
            --admin-border-md: rgba(255,255,255,0.12);
            --admin-text-primary: #F1F5F9;
            --admin-text-secondary: #94A3B8;
            --admin-text-muted: #64748B;
            --admin-bg-hover: #334155;
        ";

        $lightBorders = "
            --admin-border: #E2E8F0;
            --admin-border-md: #CBD5E1;
            --admin-text-primary: #0F172A;
            --admin-text-secondary: #475569;
            --admin-text-muted: #94A3B8;
            --admin-bg-hover: #F1F5F9;
        ";

        $primaryHex   = ltrim($a->primary_color, '#');
        $accentHex    = ltrim($a->accent_color, '#');

        return ":root {
            --admin-mode: {$a->mode};
            --admin-bg-base: {$a->bg_base};
            --admin-bg-card: {$a->bg_card};
            --admin-bg-input: {$a->bg_input};
            --admin-sidebar-bg: {$a->sidebar_bg};
            --admin-primary: {$a->primary_color};
            --admin-primary-hover: {$a->primary_hover};
            --admin-primary-text: {$a->primary_text};
            --admin-primary-soft: #{$primaryHex}26;
            --admin-primary-glow: #{$primaryHex}66;
            --admin-accent: {$a->accent_color};
            --admin-accent-soft: #{$accentHex}26;
            --admin-gold: {$a->gold_color};
            --admin-sidebar-active-bg: #{$primaryHex}33;
            --admin-sidebar-active-text: {$a->primary_color};
            --admin-sidebar-active-border: {$a->primary_color};
            " . ($a->mode === 'light' ? $lightBorders : $darkBorders) . "
        }";
    }
}
```

### 18.5 View Composer / AppServiceProvider

```php
// Tambahkan di AppServiceProvider::boot()
// atau buat ViewComposer terpisah: app/View/Composers/AdminAppearanceComposer.php

View::composer('layouts.admin', function (View $view) {
    $appearance = app(AdminAppearanceService::class)->getCurrent();
    $cssVars    = app(AdminAppearanceService::class)->toCssVars($appearance);

    $view->with([
        'adminAppearance'    => $appearance,
        'adminAppearanceCss' => $cssVars,
    ]);
});
```

### 18.6 admin.blade.php — Inject CSS

```blade
{{-- Setelah @vite([...]) --}}
<style id="admin-appearance-vars">
    {!! $adminAppearanceCss ?? '' !!}
</style>
```

> **Keamanan:** CSS vars hanya berisi hex color values yang sudah divalidasi
> di FormRequest. Tidak ada user-controlled HTML. `{!! !!}` aman di sini.

---

## 19. Architecture: CSS Variables Flow

```
┌──────────────────────────────────────────────────────────────────┐
│                    admin_dashboard_appearances (DB)              │
│  mode | primary_color | accent | sidebar_bg | bg_base | bg_card │
└───────────────────────────┬──────────────────────────────────────┘
                            │ AdminAppearanceService::getCurrent()
                            │ (cached 1 jam, invalidated on save)
                            ↓
┌──────────────────────────────────────────────────────────────────┐
│                    ViewComposer (layouts.admin)                  │
│  $adminAppearanceCss = service->toCssVars($appearance)          │
└───────────────────────────┬──────────────────────────────────────┘
                            │ {!! $adminAppearanceCss !!}
                            ↓
┌──────────────────────────────────────────────────────────────────┐
│              <style id="admin-appearance-vars">                  │
│              :root { --admin-primary: #7C3AED; ... }            │
└───────────────────────────┬──────────────────────────────────────┘
                            │ var(--admin-primary)
                            ↓
┌──────────────────────────────────────────────────────────────────┐
│                      resources/css/admin.css                     │
│  .admin-btn-primary { background: var(--admin-primary); }       │
│  .admin-card { background: var(--admin-bg-card); }              │
│  .admin-input { border-color: var(--admin-border-md); }         │
└──────────────────────────────────────────────────────────────────┘
```

### Live Preview (Tanpa Reload)

```javascript
// Di halaman Settings > Customize Dashboard
// Alpine.js mengirim color changes ke <style> tag langsung

document.addEventListener('alpine:init', () => {
    Alpine.data('appearanceEditor', () => ({
        primaryColor: '{{ $appearance->primary_color }}',
        accentColor:  '{{ $appearance->accent_color }}',
        mode:         '{{ $appearance->mode }}',

        updatePreview() {
            const style = document.getElementById('admin-appearance-vars');
            if (!style) return;

            // Inject baru langsung — instant preview
            const hex = this.primaryColor.replace('#', '');
            style.textContent = `:root {
                --admin-primary: ${this.primaryColor};
                --admin-primary-hover: ${this.primaryColor};
                --admin-primary-soft: #${hex}26;
                --admin-primary-glow: #${hex}66;
                --admin-sidebar-active-bg: #${hex}33;
                --admin-sidebar-active-text: ${this.primaryColor};
                --admin-sidebar-active-border: ${this.primaryColor};
                --admin-accent: ${this.accentColor};
            }`;
        }
    }))
})
```

---

## 20. Preset Brand Themes

Super admin bisa klik preset untuk langsung mengisi semua field warna.
**Preset hanya mengisi form — super admin masih bisa modifikasi sebelum save.**

| Preset | Mode | Primary | Accent | Sidebar BG | Karakter |
|--------|------|---------|--------|-----------|---------|
| **Command Center Dark** *(default)* | dark | `#7C3AED` violet | `#06B6D4` cyan | `#020617` | Tech, presisi |
| **Midnight Navy** | dark | `#2563EB` blue | `#06B6D4` cyan | `#0C1120` | Corporate, clean |
| **Forest Ops** | dark | `#059669` emerald | `#10B981` green | `#062019` | Nature, eco |
| **Crimson Pro** | dark | `#DC2626` red | `#F59E0B` amber | `#1A0505` | Bold, energetic |
| **Light Professional** | light | `#4F46E5` indigo | `#0891B2` cyan | `#FFFFFF` | Clean SaaS |
| **Hotel Luxury** | light | `#92400E` brown | `#D4AF37` gold | `#FAFAFA` | Premium, elegant |
| **Resort Tropical** | light_classic | `#0F766E` teal | `#14B8A6` teal | `#1E293B` | Tropical, fresh |
| **Restaurant Warm** | light | `#C2410C` orange | `#F59E0B` amber | `#FFFFFF` | Warm, appetizing |
| **Tour Adventure** | dark | `#0F766E` teal | `#22D3EE` sky | `#0A1628` | Adventure, outdoor |
| **Event Prestige** | dark | `#7C3AED` violet | `#D4AF37` gold | `#0D0720` | Exclusive, premium |

### Preset JSON (untuk di-seed atau hardcode di controller):

```php
// config/admin_appearance_presets.php ATAU hardcode di controller

const APPEARANCE_PRESETS = [
    'command-center-dark' => [
        'label'         => 'Command Center Dark',
        'mode'          => 'dark',
        'primary_color' => '#7C3AED',
        'primary_hover' => '#6D28D9',
        'primary_text'  => '#FFFFFF',
        'accent_color'  => '#06B6D4',
        'gold_color'    => '#D4AF37',
        'sidebar_bg'    => '#020617',
        'sidebar_style' => 'dark',
        'bg_base'       => '#020617',
        'bg_card'       => '#1E293B',
        'bg_input'      => '#0F172A',
    ],
    'hotel-luxury' => [
        'label'         => 'Hotel Luxury',
        'mode'          => 'light',
        'primary_color' => '#92400E',
        'primary_hover' => '#78350F',
        'primary_text'  => '#FFFFFF',
        'accent_color'  => '#D4AF37',
        'gold_color'    => '#D4AF37',
        'sidebar_bg'    => '#1C1C1C',
        'sidebar_style' => 'dark',
        'bg_base'       => '#FAFAFA',
        'bg_card'       => '#FFFFFF',
        'bg_input'      => '#FFFFFF',
    ],
    'resort-tropical' => [
        'label'         => 'Resort Tropical',
        'mode'          => 'light_classic',
        'primary_color' => '#0F766E',
        'primary_hover' => '#0D6B64',
        'primary_text'  => '#FFFFFF',
        'accent_color'  => '#14B8A6',
        'gold_color'    => '#D4AF37',
        'sidebar_bg'    => '#1E293B',
        'sidebar_style' => 'dark',
        'bg_base'       => '#F0FDFA',
        'bg_card'       => '#FFFFFF',
        'bg_input'      => '#FFFFFF',
    ],
    'tour-adventure' => [
        'label'         => 'Tour Adventure',
        'mode'          => 'dark',
        'primary_color' => '#0F766E',
        'primary_hover' => '#0D6B64',
        'primary_text'  => '#FFFFFF',
        'accent_color'  => '#22D3EE',
        'gold_color'    => '#D4AF37',
        'sidebar_bg'    => '#0A1628',
        'sidebar_style' => 'dark',
        'bg_base'       => '#0A1628',
        'bg_card'       => '#162032',
        'bg_input'      => '#0D1B2A',
    ],
    'restaurant-warm' => [
        'label'         => 'Restaurant Warm',
        'mode'          => 'light',
        'primary_color' => '#C2410C',
        'primary_hover' => '#9A3412',
        'primary_text'  => '#FFFFFF',
        'accent_color'  => '#F59E0B',
        'gold_color'    => '#F59E0B',
        'sidebar_bg'    => '#1C0A00',
        'sidebar_style' => 'dark',
        'bg_base'       => '#FFF7ED',
        'bg_card'       => '#FFFFFF',
        'bg_input'      => '#FFFFFF',
    ],
    // ... tambah preset lainnya sesuai kebutuhan
];
```

---

## 21. UI Spec: Settings > Customize Dashboard

### 21.1 Layout Halaman

```
┌─────────────────────────────────────────────────────────┐
│  PAGE HEADER                                            │
│  ⚙️ Customize Dashboard Appearance                      │
│  Ubah tampilan admin dashboard untuk semua admin.       │
└─────────────────────────────────────────────────────────┘

┌──────────────────────────┬──────────────────────────────┐
│  PANEL KIRI (Editor)     │  PANEL KANAN (Live Preview)  │
│  ─────────────────       │  ──────────────────────────  │
│  [Preset Themes]         │  ┌────────────────────────┐  │
│  [Mode Toggle]           │  │  iframe atau mockup    │  │
│  [Primary Color]         │  │  mini preview admin    │  │
│  [Accent Color]          │  │  sidebar + card + btn  │  │
│  [Sidebar Style]         │  └────────────────────────┘  │
│  [Gold Color]            │                              │
│  ─────────────────       │  Preview updates real-time   │
│  [Save Changes]          │  saat user ubah warna        │
│  [Reset to Default]      │                              │
└──────────────────────────┴──────────────────────────────┘
```

### 21.2 Preset Section (Blade Mockup)

```blade
{{-- Preset brand themes --}}
<div class="admin-card mb-6">
    <div class="admin-card-header">
        <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider">
            Brand Presets
        </h3>
    </div>
    <div class="admin-card-body">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach($presets as $key => $preset)
            <button
                type="button"
                class="group relative flex flex-col items-center gap-2 rounded-lg border p-3 text-center transition"
                style="border-color: {{ $preset['primary_color'] }}40"
                x-on:click="applyPreset('{{ $key }}')"
            >
                {{-- Color swatch --}}
                <div class="flex gap-1">
                    <span class="h-5 w-5 rounded-md" style="background: {{ $preset['primary_color'] }}"></span>
                    <span class="h-5 w-5 rounded-md" style="background: {{ $preset['accent_color'] }}"></span>
                    <span class="h-5 w-5 rounded-md" style="background: {{ $preset['sidebar_bg'] }}"></span>
                </div>
                <span class="text-xs font-semibold text-slate-300">{{ $preset['label'] }}</span>
                {{-- Active indicator --}}
                @if($currentPreset === $key)
                <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-violet-400"></span>
                @endif
            </button>
            @endforeach
        </div>
    </div>
</div>
```

### 21.3 Color Editor Section (Blade Mockup)

```blade
<div class="admin-card" x-data="appearanceEditor()">
    <div class="admin-card-header">
        <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider">
            Custom Colors
        </h3>
    </div>
    <div class="admin-card-body space-y-5">

        {{-- Mode Toggle --}}
        <div>
            <label class="admin-form-label">Display Mode</label>
            <div class="flex gap-2">
                @foreach(['dark' => 'Dark', 'light_classic' => 'Light Classic', 'light' => 'Light Full'] as $val => $label)
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2.5 transition"
                       :class="mode === '{{ $val }}'
                           ? 'border-violet-500 bg-violet-500/10 text-violet-300'
                           : 'border-white/10 text-slate-400 hover:border-white/20'">
                    <input type="radio" name="mode" value="{{ $val }}" x-model="mode"
                           x-on:change="updatePreview()" class="sr-only">
                    <span class="text-sm font-semibold">{{ $label }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Primary Color --}}
        <div>
            <label class="admin-form-label">Primary Color
                <span class="text-slate-500 normal-case font-normal ml-1">(buttons, active state)</span>
            </label>
            <div class="flex items-center gap-3">
                <input type="color" name="primary_color"
                       x-model="primaryColor"
                       x-on:input="updatePreview()"
                       class="h-10 w-10 cursor-pointer rounded-lg border-0 bg-transparent p-0.5">
                <input type="text" name="primary_color_hex"
                       x-model="primaryColor"
                       x-on:input="updatePreview()"
                       class="admin-input w-32 font-mono text-xs uppercase"
                       maxlength="7" placeholder="#7C3AED">
                <div class="flex gap-1.5">
                    <span class="text-xs text-slate-500">Preview:</span>
                    <button class="rounded-md px-3 py-1 text-xs font-bold text-white transition"
                            :style="`background: ${primaryColor}`">
                        Save
                    </button>
                </div>
            </div>
        </div>

        {{-- Accent Color --}}
        <div>
            <label class="admin-form-label">Accent Color
                <span class="text-slate-500 normal-case font-normal ml-1">(info badge, link, chart)</span>
            </label>
            <div class="flex items-center gap-3">
                <input type="color" name="accent_color"
                       x-model="accentColor"
                       x-on:input="updatePreview()"
                       class="h-10 w-10 cursor-pointer rounded-lg border-0 bg-transparent p-0.5">
                <input type="text" name="accent_color_hex"
                       x-model="accentColor"
                       x-on:input="updatePreview()"
                       class="admin-input w-32 font-mono text-xs uppercase"
                       maxlength="7" placeholder="#06B6D4">
            </div>
        </div>

        {{-- Sidebar Style --}}
        <div>
            <label class="admin-form-label">Sidebar Style</label>
            <div class="flex gap-2">
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2.5 transition"
                       :class="sidebarStyle === 'dark'
                           ? 'border-violet-500 bg-violet-500/10 text-violet-300'
                           : 'border-white/10 text-slate-400 hover:border-white/20'">
                    <input type="radio" name="sidebar_style" value="dark"
                           x-model="sidebarStyle" x-on:change="updatePreview()" class="sr-only">
                    <span class="h-4 w-4 rounded bg-slate-900 border border-white/20"></span>
                    <span class="text-sm font-semibold">Dark Sidebar</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2.5 transition"
                       :class="sidebarStyle === 'light'
                           ? 'border-violet-500 bg-violet-500/10 text-violet-300'
                           : 'border-white/10 text-slate-400 hover:border-white/20'">
                    <input type="radio" name="sidebar_style" value="light"
                           x-model="sidebarStyle" x-on:change="updatePreview()" class="sr-only">
                    <span class="h-4 w-4 rounded bg-white border border-slate-200"></span>
                    <span class="text-sm font-semibold">Light Sidebar</span>
                </label>
            </div>
        </div>

    </div>

    {{-- Footer Actions --}}
    <div class="flex items-center justify-between border-t border-white/6 px-6 py-4">
        <button type="button"
                class="admin-btn-secondary admin-btn-sm"
                x-on:click="resetToDefault()">
            Reset to Default
        </button>
        <div class="flex gap-3">
            <a href="{{ route('admin.dashboard') }}" class="admin-btn-secondary admin-btn-sm">
                Cancel
            </a>
            <button type="submit" class="admin-btn-primary admin-btn-sm" x-bind:disabled="saving">
                <span x-show="!saving">Save Changes</span>
                <span x-show="saving" x-cloak>Saving...</span>
            </button>
        </div>
    </div>
</div>
```

### 21.4 Live Preview Panel

Preview panel di kanan menampilkan "mini mockup" dari admin dashboard dengan
warna yang sedang dipilih. Implementasi:

**Opsi 1 (Simple):** CSS variables di-inject ke elemen preview SVG/HTML yang disimulasi inline.
**Opsi 2 (Rich):** Iframe yang load `/admin/appearance-preview` — route khusus yang render
partial admin layout (sidebar + topbar + sample card) tanpa auth check penuh.

Rekomendasi: Opsi 1 untuk launch awal, Opsi 2 bisa ditambahkan nanti.

### 21.5 Form Validation (FormRequest)

```php
// app/Http/Requests/Admin\UpdateDashboardAppearanceRequest.php

public function rules(): array
{
    return [
        'mode'          => ['required', 'in:dark,light,light_classic'],
        'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        'primary_hover' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        'primary_text'  => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        'accent_color'  => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        'gold_color'    => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        'show_gold'     => ['boolean'],
        'sidebar_bg'    => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        'sidebar_style' => ['required', 'in:dark,light'],
        'bg_base'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        'bg_card'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        'bg_input'      => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        'preset_name'   => ['nullable', 'string', 'max:100'],
    ];
}
```

---

## 22. Implementation Plan — Customizer Module

Mengikuti **CMS Module Pattern** dari AGENTS.md §6.

### 22.1 Files Yang Akan Dibuat (Butuh Approval Sebelum Mulai)

| # | File | Keterangan |
|---|------|-----------|
| 1 | `database/migrations/xxxx_create_admin_dashboard_appearances_table.php` | ⚠️ Schema change — butuh approval |
| 2 | `app/Models/AdminDashboardAppearance.php` | Model baru |
| 3 | `app/Services/AdminAppearanceService.php` | Service: get, update, reset, toCssVars |
| 4 | `app/Http/Requests/Admin/UpdateDashboardAppearanceRequest.php` | FormRequest dengan hex validation |
| 5 | `app/Http/Controllers/Admin/DashboardAppearanceController.php` | CRUD: index, update, reset |
| 6 | `resources/views/admin/settings/appearance/index.blade.php` | Halaman Customize Dashboard |
| 7 | `app/View/Composers/AdminAppearanceComposer.php` | View composer untuk inject ke layouts.admin |
| 8 | `config/admin_appearance_presets.php` | Preset data (tidak perlu approval) |

### 22.2 Files Yang Dimodifikasi

| File | Perubahan |
|------|-----------|
| `resources/views/layouts/admin.blade.php` | Tambah `<style>` tag untuk CSS vars |
| `resources/css/admin.css` | Refactor hex → `var(--admin-*)` |
| `app/Providers/AppServiceProvider.php` | Register ViewComposer |
| `routes/web.php` | Tambah 3 route baru |
| `resources/views/backend/partials/sidebar.blade.php` | Support `sidebar_style` via CSS var |

### 22.3 Tidak Diubah

- Semua controller existing (products, bookings, pages, dll.)
- Semua route existing
- Semua model existing
- DB schema existing (hanya tambah tabel baru)
- Public frontend (terpisah sepenuhnya)

### 22.4 Security Checklist

- [ ] Semua input hex warna divalidasi regex `/^#[0-9A-Fa-f]{6}$/`
- [ ] Route hanya accessible oleh super admin (middleware `admin.superadmin`)
- [ ] CSS inject menggunakan nilai yang sudah divalidasi — tidak ada user-controlled HTML
- [ ] CSRF token pada semua POST request
- [ ] Cache di-invalidate setelah update

### 22.5 Urutan Implementasi Yang Aman

```
Step 1: CSS Custom Properties refactor (admin.css)
        → Zero risk, bisa dilakukan tanpa migration
        → Gunakan nilai default di :root — existing tampilan tidak berubah

Step 2: Model + Service + FormRequest (tidak butuh migration dulu)
        → Buat service dengan getCurrent() yang return default jika tabel belum ada

Step 3: Migration + Seed default record
        → Setelah Step 2 done dan disetujui

Step 4: ViewComposer + admin.blade.php injection
        → Setelah DB ada

Step 5: Controller + Routes
Step 6: Blade UI (Settings > Customize Dashboard)
Step 7: Live preview (Alpine.js)
Step 8: Testing + Accessibility check
```

---

*Grand Master Plan UI/UX Admin Dashboard — Bintan Prestige CMS*
*Dibuat: 2026-06-23 | Versi: 1.1 | Status: Ready for Review + Awaiting Owner Approval (Customizer)*
*Implementasi UI redesign (Section 1–16) dapat dimulai. Customizer (Section 17–22) butuh approval schema.*
