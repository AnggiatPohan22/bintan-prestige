# STEP 11 — Dashboard Home Redesign
# Bintan Prestige CMS — Admin UI/UX Redesign
# Paste prompt ini ke sesi Claude baru

---

## Konteks Sesi Ini

**Prerequisite:** Step 01 ✅ (CSS vars), Step 04 ✅ (cards), Step 07 ✅ (buttons)

**Scope:** Update `resources/views/backend/dashboard.blade.php`.
Tambahkan KPI stat cards, quick access links ke modul utama,
dan recent activity section. Data diambil dari controller yang sudah ada —
cek terlebih dahulu apa yang di-pass ke view ini.

**Referensi:**
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — Section 9.12

---

## Risk Assessment

**Risk: 🟡 Medium**

Dashboard home adalah Blade file — ada kemungkinan perlu tambah query di
controller untuk KPI counts. AGENTS.md §7: controllers handle request flow,
no queries in Blade.

**Rules:**
- Jika data KPI belum ada di controller → perlu update controller
- Tidak boleh ada query di Blade template
- Tidak boleh mengubah route name (`admin.dashboard`)
- Semua data harus dari controller/service

---

## Phase 1 — Inspect Dashboard Existing

```bash
# Cari file dashboard blade
cat resources/views/backend/dashboard.blade.php

# Cari controller dashboard
grep -r "dashboard" app/Http/Controllers/Admin/ | head -20

# Cari route dashboard
php artisan route:list | grep dashboard
```

Catat:
1. Data apa yang sudah di-pass ke dashboard view?
2. Apakah ada KPI data (product count, booking count, dll.)?
3. Layout apa yang sudah ada di dashboard?

---

## Phase 2 — Inspect Controller

Baca controller yang handle `admin.dashboard` route.
Catat variabel yang sudah tersedia di view.

---

## Phase 3 — Implementasi

### 3.1 Update Controller (jika data KPI belum ada)

Jika controller belum pass KPI counts, tambahkan:

```php
// Di AdminDashboardController@index (atau method yang handle dashboard)
// TIDAK boleh query di Blade — semua disiapkan di sini

use App\Models\Product;
use App\Models\Booking;
use App\Models\Page;
use App\Models\User;

public function index()
{
    return view('backend.dashboard', [
        'productCount' => Product::count(),
        'bookingCount' => Booking::count(),
        'pageCount'    => Page::count(),
        'userCount'    => User::where('is_admin', true)->count(),
        // Tambah sesuai model yang tersedia
    ]);
}
```

> Cek model names yang sebenarnya ada — sesuaikan dengan protected modules di AGENTS.md §5.

### 3.2 Dashboard Blade Layout

```blade
@extends('layouts.admin')

@section('content')
<div class="admin-page">

    {{-- Page Header --}}
    <div class="admin-page-header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="admin-page-title">Dashboard</h1>
                <p class="admin-page-subtitle">Selamat datang di Bintan Prestige CMS</p>
            </div>
            <a href="{{ route('admin.products.create') }}" class="admin-btn-primary">
                <i class="fa-solid fa-plus"></i>
                Buat Product Baru
            </a>
        </div>
    </div>

    {{-- KPI Stat Cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">

        <div class="admin-stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest" style="color: var(--admin-text-muted)">Products</p>
                    <p class="mt-2 text-3xl font-extrabold" style="color: var(--admin-text-primary)">{{ $productCount ?? 0 }}</p>
                    <p class="mt-1 text-xs" style="color: var(--admin-text-muted)">Tours & activities</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg" style="background: var(--admin-primary-soft); color: var(--admin-primary)">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
            </div>
            <a href="{{ route('admin.products.index') }}" class="mt-4 flex items-center gap-1 text-xs font-semibold" style="color: var(--admin-text-muted)">
                Lihat semua <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <div class="admin-stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest" style="color: var(--admin-text-muted)">Bookings</p>
                    <p class="mt-2 text-3xl font-extrabold" style="color: var(--admin-text-primary)">{{ $bookingCount ?? 0 }}</p>
                    <p class="mt-1 text-xs" style="color: var(--admin-text-muted)">Total reservasi</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg" style="background: var(--admin-accent-soft); color: var(--admin-accent)">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
            </div>
            <a href="{{ route('admin.bookings.index') }}" class="mt-4 flex items-center gap-1 text-xs font-semibold" style="color: var(--admin-text-muted)">
                Lihat semua <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <div class="admin-stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest" style="color: var(--admin-text-muted)">Pages</p>
                    <p class="mt-2 text-3xl font-extrabold" style="color: var(--admin-text-primary)">{{ $pageCount ?? 0 }}</p>
                    <p class="mt-1 text-xs" style="color: var(--admin-text-muted)">Halaman aktif</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg" style="background: rgba(16,185,129,0.12); color: var(--admin-success)">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
            </div>
            <a href="{{ route('admin.pages.index') }}" class="mt-4 flex items-center gap-1 text-xs font-semibold" style="color: var(--admin-text-muted)">
                Lihat semua <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <div class="admin-stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest" style="color: var(--admin-text-muted)">Admin Users</p>
                    <p class="mt-2 text-3xl font-extrabold" style="color: var(--admin-text-primary)">{{ $userCount ?? 0 }}</p>
                    <p class="mt-1 text-xs" style="color: var(--admin-text-muted)">Pengguna admin</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg" style="background: rgba(212,175,55,0.1); color: var(--admin-gold)">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <a href="{{ route('admin.users.index') }}" class="mt-4 flex items-center gap-1 text-xs font-semibold" style="color: var(--admin-text-muted)">
                Lihat semua <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

    </div>

    {{-- Quick Access --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="text-xs font-black uppercase tracking-wider" style="color: var(--admin-text-secondary)">Quick Access</h3>
        </div>
        <div class="admin-card-body">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                @foreach([
                    ['icon' => 'fa-map-location-dot', 'label' => 'Products',   'route' => 'admin.products.index'],
                    ['icon' => 'fa-calendar-check',   'label' => 'Bookings',   'route' => 'admin.bookings.index'],
                    ['icon' => 'fa-file-lines',        'label' => 'Pages',      'route' => 'admin.pages.index'],
                    ['icon' => 'fa-images',            'label' => 'Media',      'route' => 'admin.media.index'],
                    ['icon' => 'fa-bars-staggered',    'label' => 'Menus',      'route' => 'admin.menus.index'],
                    ['icon' => 'fa-gear',              'label' => 'Settings',   'route' => 'admin.settings.index'],
                ] as $item)
                <a href="{{ route($item['route']) }}"
                   class="flex flex-col items-center gap-2 rounded-lg p-4 text-center transition duration-150"
                   style="background: rgba(255,255,255,0.03); border: 1px solid var(--admin-border);"
                   onmouseover="this.style.background='rgba(124,58,237,0.08)'; this.style.borderColor='var(--admin-primary-soft)'"
                   onmouseout="this.style.background='rgba(255,255,255,0.03)'; this.style.borderColor='var(--admin-border)'">
                    <i class="fa-solid {{ $item['icon'] }} text-lg" style="color: var(--admin-primary)"></i>
                    <span class="text-xs font-semibold" style="color: var(--admin-text-secondary)">{{ $item['label'] }}</span>
                </a>
                @endforeach
            </div>
        </div>
    </div>

</div>
@endsection
```

> **Catatan untuk Quick Access:** Cek setiap route yang digunakan apakah memang
> tersedia. Gunakan `php artisan route:list | grep admin` untuk verifikasi.
> Jika ada route yang belum ada → hapus dari array, jangan buat route baru.

---

## Phase 4 — Verifikasi

- [ ] Dashboard load tanpa error
- [ ] KPI cards tampil dengan angka yang benar
- [ ] Hover stat card: lift effect
- [ ] Quick access grid: 6 modul shortcuts
- [ ] Route yang digunakan semuanya valid
- [ ] Data KPI dari controller, bukan dari Blade query

---

## Phase 5 — Buat Handoff

Buat `ai/reports/UIUX/step-11-handoff.md`. Catat:
- Controller yang diupdate (jika ada)
- Route names yang diverifikasi
- Data variables yang di-pass

---

## STOP — Setelah ini, siap untuk Fase D (Customizer). Tunggu approval owner untuk Step 12.
