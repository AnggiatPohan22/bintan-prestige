# Step 17 Handoff — Per-User Dark/Light Mode Resolution
**Tanggal:** 2026-06-25
**Status:** ✅ Complete
**Branch:** feature/uiux-command-center-dark
**Risk:** 🟡 Medium (DB migration on users table)
**Phase:** E.1 — Per-User Theme System

---

## Tujuan

Sebelumnya theme mode (dark/light) global — 1 setting untuk seluruh dashboard di-set superadmin. Step 17 mengubah jadi **per-user preference** — tiap user (superadmin/admin/client) bisa simpan pilihan sendiri yang override global preset.

Mempersiapkan ground untuk topbar toggle (Step 18).

---

## Files Diubah

| File | Aksi | Lines |
|------|------|------|
| `database/migrations/2026_06_25_055530_add_ui_mode_to_users_table.php` | Baru | +24 |
| `app/Models/User.php` | + constants UI_MODE_*, fillable | +13 |
| `app/Services/AdminAppearanceService.php` | + `resolveModeForUser()` method | +16 |
| `app/View/Composers/AdminAppearanceComposer.php` | + inject `$adminUiMode` + skip CSS injection logic | +9 |
| `resources/views/layouts/admin.blade.php` | `data-admin-mode` driven by `$adminUiMode` (bukan global) + inline FOUC bg mode-aware | ~2 |
| `resources/css/admin.css` | Specificity bump `[data-admin-mode="light"]` → `html[data-admin-mode="light"]` (semua occurrence) | replace-all |
| `ai/reports/UIUX/grand-master-plan-admin-uiux.md` | + Phase E roadmap (Steps 17–21) appended | +120 |

---

## Schema Change

```sql
ALTER TABLE users
  ADD COLUMN ui_mode ENUM('auto', 'dark', 'light')
    NOT NULL DEFAULT 'auto'
    AFTER role;
```

- `auto` = ikut whatever superadmin set globally (default semua user existing)
- `dark` / `light` = explicit user override

---

## Logic Resolution (Service)

```php
public function resolveModeForUser(?User $user): string
{
    $global = $this->getCurrent()->mode ?? 'dark';

    if (! $user || ($user->ui_mode ?? User::UI_MODE_AUTO) === User::UI_MODE_AUTO) {
        return $global;   // guest or auto -> follow global
    }

    return $user->ui_mode;  // user override
}
```

Composer juga di-update: **kalau resolved mode ≠ global mode (user override)**, customizer CSS injection di-skip — supaya built-in `html[data-admin-mode="light"]` block di admin.css apply clean tanpa konflik dari `:root` injection.

---

## Critical Bug Discovered & Fixed (during verification)

**Cascade order bug:**
- `[data-admin-mode="light"]` di admin.css ter-compile di byte **33** (sangat awal CSS output)
- `:root` dengan dark vars ter-compile di byte **14696** (jauh setelahnya)
- Specificity sama (0,0,1,0 vs 0,0,1,0) → cascade order menang → `:root` selalu menang → light mode tidak pernah apply
- Sebab: Tailwind's `@layer base` mendeklarasi `:root` di layer akhir, tapi block `[data-admin-mode]` di luar layer ter-emit pertama

**Fix:** bump specificity ke `html[data-admin-mode="light"]` (0,0,1,1 > 0,0,1,0) — selalu menang regardless cascade order.

Verified via Python script reading compiled CSS positions before/after fix.

---

## Verification (Live Browser)

| Scenario | `users.ui_mode` | Resolved | `<html data-admin-mode>` | `--admin-bg-base` | Result |
|---|---|---|---|---|---|
| Default | `auto` | dark (global) | (none) | `#020617` | ✅ dark |
| Override light | `light` | light | `data-admin-mode=light` | `#F8FAFC` | ✅ light |
| Override dark | `dark` | dark | (none) | `#020617` | ✅ dark |
| Guest (null user) | n/a | dark (global) | (none) | `#020617` | ✅ dark |

Smoke test 5/5 pass via `php artisan tinker`.
Live verify 4/4 pass via preview browser at 904×768 viewport.

---

## Impact

- **DB:** migration added; rollback via `php artisan migrate:rollback --step=1`
- **Routes:** none changed
- **Frontend (public):** none — fully scoped to `/admin/*`
- **Security:** no new input vectors (resolution is purely server-side read)
- **Performance:** zero — `resolveModeForUser()` reuses cached `getCurrent()`

---

## Rollback

```bash
git revert 2fae1fa
php artisan migrate:rollback --step=1
npx vite build
```

---

## Known Limitations Carried Forward

1. **Inline FOUC body bg di admin.blade.php** — di-render server-side berdasarkan resolved mode. Pada optimistic toggle (no reload) body bg lag 1 frame. Full reload bersih. Ini di-address sebagian di Step 18 (toggle pakai reload-safe attribute swap).
2. **Customizer global mode** masih satu setting — di Phase E.6 (Step 19.6) akan dibuat per-mode customizer (dark palette + light palette terpisah).

---

## Next Step

→ **Step 18** — Topbar toggle button (sun/moon, instant flip, persist via AJAX)

---

## Commit

`2fae1fa` — feat(admin-ui): Step 17 — per-user dark/light mode resolution
