# STEP 12 — Appearance Customizer: Migration & Model
# Bintan Prestige CMS — Admin UI/UX Redesign
# Paste prompt ini ke sesi Claude baru

---

## ⚠️ PERHATIAN KRITIS

**STEP INI MEMERLUKAN PERSETUJUAN EKSPLISIT OWNER SEBELUM DIJALANKAN.**

Sesuai `AGENTS.md §8-9`:
> "Never do these without explicit approval: Change database schema or existing migrations"
> "Ask for explicit approval before: Any database schema change (new table, new column, index)"

Jika owner belum berikan approval secara eksplisit untuk migration `admin_dashboard_appearances`,
**STOP di sini dan tunggu.**

---

## Konteks Sesi Ini

**Prerequisite:**
- Step 01–10 ✅ (Fase A + B selesai, CSS vars tersedia)
- ✅ **Owner approval eksplisit** untuk migration tabel baru

**Scope:**
1. Migration: buat tabel `admin_dashboard_appearances`
2. Model: `AdminDashboardAppearance`
3. Seeder: record default "Command Center Dark"

**Referensi:**
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — Section 18

---

## Risk Assessment

**Risk: 🔴 HIGH**

Database migration adalah perubahan permanen. Jika migration dijalankan di production
dan ada error → butuh rollback manual. Mitigasi:
1. Test di local dev dulu
2. Pastikan migration reversible (down() method lengkap)
3. Rollback: `php artisan migrate:rollback`

---

## Phase 1 — Baca Konteks

1. `AGENTS.md`
2. `ai/reports/UIUX/grand-master-plan-admin-uiux.md` Section 18
3. Cek migration existing untuk tahu naming convention:
```bash
ls database/migrations/ | tail -10
```
4. Cek apakah model sudah ada:
```bash
ls app/Models/ | grep -i appearance
```

---

## Phase 2 — Inspect Database Existing

```bash
# Cek tabel existing untuk memastikan tidak ada konflik
php artisan tinker --execute="DB::connection()->getSchemaBuilder()->getTableListing()"
```

Pastikan `admin_dashboard_appearances` BELUM ada.

---

## Phase 3 — Implementasi

### 3.1 Migration

```bash
php artisan make:migration create_admin_dashboard_appearances_table
```

Isi migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_dashboard_appearances', function (Blueprint $table) {
            $table->id();

            // Display mode
            $table->enum('mode', ['dark', 'light', 'light_classic'])->default('dark');

            // Sidebar
            $table->string('sidebar_bg', 20)->default('#020617');
            $table->enum('sidebar_style', ['dark', 'light'])->default('dark');

            // Primary color (buttons, active state, focus)
            $table->string('primary_color', 20)->default('#7C3AED');
            $table->string('primary_hover', 20)->default('#6D28D9');
            $table->string('primary_text', 20)->default('#FFFFFF');

            // Accent color
            $table->string('accent_color', 20)->default('#06B6D4');

            // Brand gold
            $table->string('gold_color', 20)->default('#D4AF37');
            $table->boolean('show_gold')->default(true);

            // Background overrides
            $table->string('bg_base', 20)->default('#020617');
            $table->string('bg_card', 20)->default('#1E293B');
            $table->string('bg_input', 20)->default('#0F172A');

            // Preset info
            $table->string('preset_name', 100)->nullable();
            $table->boolean('is_default')->default(false);

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_dashboard_appearances');
    }
};
```

### 3.2 Model

Buat `app/Models/AdminDashboardAppearance.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Ambil record appearance aktif.
     * Jika belum ada record di DB, return default.
     */
    public static function getCurrent(): static
    {
        return static::first() ?? static::makeDefault();
    }

    /**
     * Return default "Command Center Dark" tanpa menyimpan ke DB.
     */
    public static function makeDefault(): static
    {
        return new static([
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

### 3.3 Seeder

Buat `database/seeders/AdminDashboardAppearanceSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\AdminDashboardAppearance;
use Illuminate\Database\Seeder;

class AdminDashboardAppearanceSeeder extends Seeder
{
    public function run(): void
    {
        // Hanya seed jika belum ada record
        if (AdminDashboardAppearance::count() > 0) {
            return;
        }

        AdminDashboardAppearance::create([
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
            'is_default'    => true,
        ]);
    }
}
```

Tambahkan ke `DatabaseSeeder.php`:
```php
$this->call(AdminDashboardAppearanceSeeder::class);
```

### 3.4 Jalankan Migration

```bash
php artisan migrate
php artisan db:seed --class=AdminDashboardAppearanceSeeder
```

---

## Phase 4 — Verifikasi

```bash
# Cek tabel terbuat
php artisan tinker --execute="DB::table('admin_dashboard_appearances')->first()"

# Cek model
php artisan tinker --execute="App\Models\AdminDashboardAppearance::getCurrent()"
```

Output harus:
- Tabel ada dengan satu record (Command Center Dark)
- `getCurrent()` return record tersebut

---

## Phase 5 — Buat Handoff

Buat `ai/reports/UIUX/step-12-handoff.md`. Catat:
- Nama migration file yang dibuat
- Timestamp migration
- Record yang di-seed

```markdown
# Step 12 Handoff — Migration & Model
**Status:** ✅ Complete
**Migration:** database/migrations/[timestamp]_create_admin_dashboard_appearances_table.php
**Model:** app/Models/AdminDashboardAppearance.php
**Seeder:** database/seeders/AdminDashboardAppearanceSeeder.php

## Rollback
php artisan migrate:rollback

## Verifikasi
- DB table: ✅
- Seeder record: ✅ (Command Center Dark)
- getCurrent() works: ✅

## Next
Step 13 — AdminAppearanceService + ViewComposer
```

---

## STOP
