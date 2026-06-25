# Step 12 Handoff — Migration & Model
**Tanggal:** 2026-06-25
**Status:** ✅ Complete
**Branch:** feature/uiux-command-center-dark
**Risk:** 🔴 High (DB migration — sudah dijalankan di local dev)

---

## Files Baru

- `database/migrations/2026_06_25_021432_create_admin_dashboard_appearances_table.php`
- `app/Models/AdminDashboardAppearance.php`
- `database/seeders/AdminDashboardAppearanceSeeder.php`
- `database/seeders/DatabaseSeeder.php` — tambah seeder baru

---

## Schema: `admin_dashboard_appearances`

| Kolom | Type | Default | Keterangan |
|-------|------|---------|------------|
| `id` | bigint PK | — | |
| `mode` | enum(dark,light,light_classic) | `dark` | Maps ke `data-admin-mode` HTML attr |
| `sidebar_bg` | varchar(20) | `#020617` | Background sidebar |
| `sidebar_style` | enum(dark,light) | `dark` | Maps ke `data-admin-sidebar` HTML attr |
| `primary_color` | varchar(20) | `#7C3AED` | Buttons, active state, focus ring |
| `primary_hover` | varchar(20) | `#6D28D9` | Hover state primary |
| `primary_text` | varchar(20) | `#FFFFFF` | Text di atas primary bg |
| `accent_color` | varchar(20) | `#06B6D4` | Info badge, links |
| `gold_color` | varchar(20) | `#D4AF37` | Brand gold |
| `show_gold` | boolean | `true` | Toggle gold hints |
| `bg_base` | varchar(20) | `#020617` | Page background |
| `bg_card` | varchar(20) | `#1E293B` | Card background |
| `bg_input` | varchar(20) | `#0F172A` | Input background |
| `custom_vars` | JSON nullable | `null` | **Extra: future CSS vars tanpa migration baru** |
| `preset_name` | varchar(100) nullable | `null` | Nama preset config yang terakhir dipilih |
| `is_default` | boolean | `false` | Flag default record |
| `created_by` | FK users nullable | `null` | |
| `updated_by` | FK users nullable | `null` | |
| `created_at` / `updated_at` | timestamps | | |

### Tambahan dari Prompt Asli (Audit Fix)

**`custom_vars JSON nullable`** — ditambahkan untuk future-proofing. Memungkinkan
extension CSS vars baru (font size, border radius, compact mode, dll.) tanpa
membuat migration baru. Contoh penggunaan:
```json
{"--admin-radius-lg": "8px", "--admin-font-size-base": "13px"}
```

---

## Model: `AdminDashboardAppearance`

**Key methods:**

```php
// Ambil record aktif — fallback ke in-memory default jika DB kosong
AdminDashboardAppearance::getCurrent(): static

// Buat instance default tanpa persist ke DB
AdminDashboardAppearance::makeDefault(): static
```

**Casts:** `show_gold` (boolean), `is_default` (boolean), `custom_vars` (array)

---

## Seeder: `AdminDashboardAppearanceSeeder`

- **Idempotent** — tidak insert jika sudah ada record (`count() > 0` check)
- Seed satu record: "Command Center Dark" (semua default values)
- Ditambahkan ke `DatabaseSeeder::run()`

---

## Verifikasi

```bash
php artisan migrate
# ✅ 2026_06_25_021432_create_admin_dashboard_appearances_table .. DONE

php artisan db:seed --class=AdminDashboardAppearanceSeeder
# ✅ Seeding database.

php artisan tinker --execute="dd(App\Models\AdminDashboardAppearance::getCurrent()->toArray());"
# ✅ Returns: mode=dark, primary_color=#7C3AED, bg_base=#020617, preset_name="Command Center Dark"
```

---

## Rollback

```bash
php artisan migrate:rollback
# Drops admin_dashboard_appearances table

# Atau rollback semua Fase D:
git checkout HEAD -- database/migrations/2026_06_25_021432_create_admin_dashboard_appearances_table.php
git checkout HEAD -- app/Models/AdminDashboardAppearance.php
git checkout HEAD -- database/seeders/AdminDashboardAppearanceSeeder.php
git checkout HEAD -- database/seeders/DatabaseSeeder.php
```

---

## Perbaikan dari Audit (Pre-applied untuk Steps Berikutnya)

Temuan dari audit Fase D yang akan di-fix di step masing-masing:

| Issue | Fix di Step |
|-------|-------------|
| `toCssVars()` map `sidebar_bg` → `--admin-bg-surface` (salah) | Step 13 — gunakan `bg_base` |
| Light mode HTML attr tidak di-inject | Step 13 — inject `data-admin-mode` di admin.blade.php |
| `resetToDefault()` JS pakai GET ke route POST | Step 15 — gunakan form POST + CSRF |
| View path `admin.settings.*` vs `backend.settings.*` | Step 15 — gunakan `backend.settings.appearance` |

---

## Next

**Step 13** — `AdminAppearanceService` + `AdminAppearanceComposer`
— Prompt: `ai/promt/uiux/step-13-service-composer.md`
— Note: Apply 2 audit fixes: `toCssVars` mapping + HTML attr injection
