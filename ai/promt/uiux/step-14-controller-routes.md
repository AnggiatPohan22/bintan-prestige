# STEP 14 — Controller, FormRequest & Routes
# Bintan Prestige CMS — Admin UI/UX Redesign
# Paste prompt ini ke sesi Claude baru

---

## Konteks Sesi Ini

**Prerequisite:** Step 12 ✅ + Step 13 ✅

**Scope:**
1. `app/Http/Requests/Admin/UpdateDashboardAppearanceRequest.php`
2. `app/Http/Controllers/Admin/DashboardAppearanceController.php`
3. 3 route baru di `routes/web.php`

**Referensi:**
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — Section 17.2, 21.5

---

## Risk Assessment

**Risk: 🟡 Medium**

Menambah routes baru: zero risk ke existing routes.
Controller baru: zero risk.
FormRequest: zero risk.
Satu-satunya risk: jika middleware name berbeda dari yang dipakai project ini.

---

## Phase 1 — Inspect Existing Patterns

```bash
# Cek middleware names yang tersedia
cat app/Http/Kernel.php | grep -A 5 "routeMiddleware\|middlewareAliases"

# Cek routes/web.php untuk melihat pola admin routes
tail -50 routes/web.php

# Cek satu controller existing untuk pola
head -50 app/Http/Controllers/Admin/UserController.php
```

Sesuaikan middleware name `admin.superadmin` dengan yang ada di project.
Jika belum ada middleware super admin → gunakan `auth` saja dan tambahkan
manual check di controller (`abort_unless(auth()->user()?->isSuperAdmin(), 403)`).

---

## Phase 3 — Implementasi

### 3.1 FormRequest

Buat `app/Http/Requests/Admin/UpdateDashboardAppearanceRequest.php`:

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDashboardAppearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya super admin
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'mode'          => ['required', 'in:dark,light,light_classic'],
            'sidebar_style' => ['required', 'in:dark,light'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'primary_hover' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'primary_text'  => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color'  => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'gold_color'    => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'show_gold'     => ['boolean'],
            'sidebar_bg'    => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bg_base'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bg_card'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bg_input'      => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'preset_name'   => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.regex' => 'Warna harus dalam format hex (#RRGGBB).',
        ];
    }
}
```

### 3.2 Controller

Buat `app/Http/Controllers/Admin/DashboardAppearanceController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateDashboardAppearanceRequest;
use App\Services\AdminAppearanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardAppearanceController extends Controller
{
    public function __construct(
        private readonly AdminAppearanceService $service
    ) {}

    /**
     * Halaman Settings > Customize Dashboard.
     * Hanya super admin.
     */
    public function index(): View
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403, 'Access denied.');

        $appearance = $this->service->getCurrent();
        $presets    = config('admin_appearance_presets', []);

        return view('admin.settings.appearance.index', compact('appearance', 'presets'));
    }

    /**
     * Simpan perubahan appearance.
     */
    public function update(UpdateDashboardAppearanceRequest $request): RedirectResponse
    {
        $this->service->update($request->validated(), $request->user());

        return redirect()
            ->route('admin.settings.appearance')
            ->with('success', 'Dashboard appearance berhasil disimpan.');
    }

    /**
     * Reset ke default "Command Center Dark".
     */
    public function reset(): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->service->reset(auth()->user());

        return redirect()
            ->route('admin.settings.appearance')
            ->with('success', 'Appearance direset ke default.');
    }
}
```

### 3.3 Routes

Tambahkan di `routes/web.php`, di dalam grup admin middleware yang ada:

```php
// Settings > Customize Dashboard (Super Admin only)
Route::get('/settings/dashboard-appearance', [DashboardAppearanceController::class, 'index'])
    ->name('admin.settings.appearance');
Route::post('/settings/dashboard-appearance', [DashboardAppearanceController::class, 'update'])
    ->name('admin.settings.appearance.update');
Route::post('/settings/dashboard-appearance/reset', [DashboardAppearanceController::class, 'reset'])
    ->name('admin.settings.appearance.reset');
```

> Tambahkan use statement di atas routes/web.php jika belum ada:
> `use App\Http\Controllers\Admin\DashboardAppearanceController;`

### 3.4 Config File

Buat `config/admin_appearance_presets.php`:

```php
<?php

return [
    'command-center-dark' => [
        'label'         => 'Command Center Dark',
        'mode'          => 'dark',
        'primary_color' => '#7C3AED',
        'primary_hover' => '#6D28D9',
        'primary_text'  => '#FFFFFF',
        'accent_color'  => '#06B6D4',
        'gold_color'    => '#D4AF37',
        'show_gold'     => true,
        'sidebar_bg'    => '#020617',
        'sidebar_style' => 'dark',
        'bg_base'       => '#020617',
        'bg_card'       => '#1E293B',
        'bg_input'      => '#0F172A',
    ],
    'midnight-navy' => [
        'label'         => 'Midnight Navy',
        'mode'          => 'dark',
        'primary_color' => '#2563EB',
        'primary_hover' => '#1D4ED8',
        'primary_text'  => '#FFFFFF',
        'accent_color'  => '#06B6D4',
        'gold_color'    => '#D4AF37',
        'show_gold'     => true,
        'sidebar_bg'    => '#0C1120',
        'sidebar_style' => 'dark',
        'bg_base'       => '#0C1120',
        'bg_card'       => '#162033',
        'bg_input'      => '#0C1120',
    ],
    'hotel-luxury' => [
        'label'         => 'Hotel Luxury',
        'mode'          => 'light',
        'primary_color' => '#92400E',
        'primary_hover' => '#78350F',
        'primary_text'  => '#FFFFFF',
        'accent_color'  => '#D4AF37',
        'gold_color'    => '#D4AF37',
        'show_gold'     => true,
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
        'show_gold'     => true,
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
        'show_gold'     => true,
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
        'show_gold'     => true,
        'sidebar_bg'    => '#1C0A00',
        'sidebar_style' => 'dark',
        'bg_base'       => '#FFF7ED',
        'bg_card'       => '#FFFFFF',
        'bg_input'      => '#FFFFFF',
    ],
    'light-professional' => [
        'label'         => 'Light Professional',
        'mode'          => 'light',
        'primary_color' => '#4F46E5',
        'primary_hover' => '#4338CA',
        'primary_text'  => '#FFFFFF',
        'accent_color'  => '#0891B2',
        'gold_color'    => '#D4AF37',
        'show_gold'     => false,
        'sidebar_bg'    => '#1E293B',
        'sidebar_style' => 'dark',
        'bg_base'       => '#F8FAFC',
        'bg_card'       => '#FFFFFF',
        'bg_input'      => '#FFFFFF',
    ],
];
```

---

## Phase 4 — Verifikasi

```bash
# Test routes ada
php artisan route:list | grep appearance

# Cek tidak ada konflik nama route
php artisan route:list | grep "admin.settings"
```

**Browser test:**
- [ ] Buka `/admin/settings/dashboard-appearance` (sebagai super admin)
- [ ] Jika belum ada view (Step 15 belum) → 500 error di view adalah normal
- [ ] Yang harus tidak error: route resolution + controller instantiation
- [ ] Test dengan non-super-admin → harus 403

---

## Phase 5 — Buat Handoff

Buat `ai/reports/UIUX/step-14-handoff.md`. Catat:
- Route names yang ditambahkan
- Middleware yang digunakan
- isSuperAdmin() method yang diverifikasi

---

## STOP
