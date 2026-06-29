# STEP 13 — AdminAppearanceService + ViewComposer
# Bintan Prestige CMS — Admin UI/UX Redesign
# Paste prompt ini ke sesi Claude baru

---

## Konteks Sesi Ini

**Prerequisite:** Step 12 ✅ (tabel + model ada)

**Scope:**
1. `app/Services/AdminAppearanceService.php` — get, update, reset, toCssVars
2. `app/View/Composers/AdminAppearanceComposer.php` — inject ke layouts.admin
3. Update `app/Providers/AppServiceProvider.php` — register composer
4. Update `resources/views/layouts/admin.blade.php` — inject `<style>` CSS vars

**Referensi:**
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — Section 18.3–18.6

---

## Risk Assessment

**Risk: 🟡 Medium**

Service baru: zero risk (baru, tidak modify existing).
AppServiceProvider update: medium — error di sini bisa break semua view.
admin.blade.php: medium — inject CSS vars baru, tampilan tidak berubah
selama service return default yang benar.

---

## Phase 1 — Inspect

```bash
# Cek struktur Services yang sudah ada
ls app/Services/

# Cek ViewComposers yang sudah ada
ls app/View/ 2>/dev/null || echo "belum ada"

# Baca AppServiceProvider
cat app/Providers/AppServiceProvider.php

# Baca admin.blade.php
cat resources/views/layouts/admin.blade.php
```

---

## Phase 3 — Implementasi

### 3.1 AdminAppearanceService

Buat `app/Services/AdminAppearanceService.php`:

```php
<?php

namespace App\Services;

use App\Models\AdminDashboardAppearance;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AdminAppearanceService
{
    private const CACHE_KEY = 'admin_dashboard_appearance';
    private const CACHE_TTL = 3600; // 1 jam

    public function getCurrent(): AdminDashboardAppearance
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
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

        $this->clearCache();

        return $appearance->fresh();
    }

    public function reset(User $user): AdminDashboardAppearance
    {
        $defaults = AdminDashboardAppearance::makeDefault()->toArray();
        unset($defaults['id'], $defaults['created_at'], $defaults['updated_at']);

        return $this->update($defaults, $user);
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Generate :root CSS string untuk inject ke <style> tag di admin.blade.php.
     * Semua hex values sudah divalidasi via FormRequest sebelum masuk DB.
     */
    public function toCssVars(AdminDashboardAppearance $a): string
    {
        $primaryHex = ltrim($a->primary_color, '#');
        $accentHex  = ltrim($a->accent_color, '#');
        $goldHex    = ltrim($a->gold_color, '#');

        $modeVars = $a->mode === 'light'
            ? $this->lightModeVars()
            : $this->darkModeVars();

        return ":root {
            --admin-mode: {$a->mode};
            --admin-bg-base: {$a->bg_base};
            --admin-bg-surface: {$a->sidebar_bg};
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
            --admin-gold-soft: #{$goldHex}1A;
            --admin-sidebar-active-bg: #{$primaryHex}33;
            --admin-sidebar-active-text: {$a->primary_color};
            --admin-sidebar-active-border: {$a->primary_color};
            {$modeVars}
        }";
    }

    private function darkModeVars(): string
    {
        return "
            --admin-border: rgba(255, 255, 255, 0.08);
            --admin-border-md: rgba(255, 255, 255, 0.12);
            --admin-border-strong: rgba(255, 255, 255, 0.20);
            --admin-text-primary: #F1F5F9;
            --admin-text-secondary: #94A3B8;
            --admin-text-muted: #64748B;
            --admin-bg-hover: #334155;
            --admin-sidebar-border: rgba(255, 255, 255, 0.06);
            --admin-sidebar-text: #64748B;
            --admin-sidebar-text-hover: #CBD5E1;
        ";
    }

    private function lightModeVars(): string
    {
        return "
            --admin-border: #E2E8F0;
            --admin-border-md: #CBD5E1;
            --admin-border-strong: #94A3B8;
            --admin-text-primary: #0F172A;
            --admin-text-secondary: #475569;
            --admin-text-muted: #94A3B8;
            --admin-bg-hover: #F1F5F9;
            --admin-sidebar-border: rgba(255, 255, 255, 0.06);
            --admin-sidebar-text: #64748B;
            --admin-sidebar-text-hover: #CBD5E1;
        ";
    }
}
```

### 3.2 ViewComposer

Buat `app/View/Composers/AdminAppearanceComposer.php`:

```php
<?php

namespace App\View\Composers;

use App\Services\AdminAppearanceService;
use Illuminate\View\View;

class AdminAppearanceComposer
{
    public function __construct(
        private readonly AdminAppearanceService $service
    ) {}

    public function compose(View $view): void
    {
        $appearance = $this->service->getCurrent();

        $view->with([
            'adminAppearance'    => $appearance,
            'adminAppearanceCss' => $this->service->toCssVars($appearance),
        ]);
    }
}
```

### 3.3 AppServiceProvider Update

Tambahkan di `AppServiceProvider::boot()`:

```php
use App\View\Composers\AdminAppearanceComposer;
use Illuminate\Support\Facades\View;

public function boot(): void
{
    // ... existing code ...

    // Admin Appearance Customizer
    View::composer('layouts.admin', AdminAppearanceComposer::class);
}
```

> **PENTING:** Tambahkan di DALAM method `boot()` yang sudah ada — jangan replace
> existing code. Cek dulu isi `AppServiceProvider::boot()`.

### 3.4 admin.blade.php Update

Tambahkan setelah `@vite([...])` di `<head>`:

```blade
    {{-- Admin Appearance: CSS Custom Properties dari DB --}}
    {{-- Dikelola via Settings > Customize Dashboard (super admin only) --}}
    @if(isset($adminAppearanceCss))
    <style id="admin-appearance-vars">
        {!! $adminAppearanceCss !!}
    </style>
    @endif
```

> **Security note:** `$adminAppearanceCss` hanya berisi hex color values yang
> sudah divalidasi via regex di FormRequest. `{!! !!}` aman di sini.
> Tapi WAJIB pastikan FormRequest validation ada di Step 14 sebelum
> menerima user input untuk field ini.

---

## Phase 4 — Verifikasi

```bash
# Test service
php artisan tinker
>>> app(App\Services\AdminAppearanceService::class)->getCurrent()
# Output: AdminDashboardAppearance object dengan Command Center Dark values

>>> app(App\Services\AdminAppearanceService::class)->toCssVars(
...     app(App\Services\AdminAppearanceService::class)->getCurrent()
... )
# Output: string berisi :root { --admin-primary: #7C3AED; ... }
```

**Test di browser:**
- [ ] Buka halaman admin manapun
- [ ] DevTools → Elements → `<head>` → ada `<style id="admin-appearance-vars">`
- [ ] Isi style: `:root { --admin-primary: #7C3AED; ... }`
- [ ] Tampilan admin: tidak ada perubahan (karena CSS vars default sudah sama)
- [ ] Tidak ada PHP error di log

---

## Phase 5 — Buat Handoff

Buat `ai/reports/UIUX/step-13-handoff.md`. Catat:
- File baru yang dibuat
- AppServiceProvider line yang ditambah
- Output tinker test

---

## STOP
