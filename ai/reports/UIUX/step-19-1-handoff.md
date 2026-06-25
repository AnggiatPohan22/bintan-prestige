# Step 19.1 Handoff — Theme v2 Spec Lock
**Tanggal:** 2026-06-25
**Status:** ✅ Complete (doc-only)
**Branch:** feature/uiux-command-center-dark
**Risk:** 🟢 None — no code changes
**Phase:** E.19.1 — Owner sign-off on tone palette + section list + presets

---

## Tujuan

Lock spec sebelum migration + service rewrite (Step 19.2). Doc-only sub-step.

---

## Locked Decisions (Owner-Approved)

### 1. Tone Palettes (Final)

**🌙 Night (default preset: Command Center Dark)**
| Token | Value | Note |
|---|---|---|
| `--admin-bg-base` | `#020617` | slate-950 |
| `--admin-bg-surface` | `#0F172A` | slate-900 |
| `--admin-bg-card` | `#1E293B` | slate-800 |
| `--admin-bg-hover` | `#334155` | slate-700 |
| `--admin-border` | `rgba(255,255,255,0.08)` | subtle |
| `--admin-border-md` | `rgba(255,255,255,0.16)` | medium |
| `--admin-text-primary` | `#F1F5F9` | slate-100 |
| `--admin-text-secondary` | `#94A3B8` | slate-400 |
| `--admin-text-muted` | `#64748B` | slate-500 |
| `--admin-primary` | **`#166AE9`** | **cobalt blue** (was violet `#7C3AED`) |
| `--admin-primary-hover` | **`#1054BC`** | (was `#6D28D9`) |
| `--admin-primary-text` | `#FFFFFF` | contrast |
| `--admin-success` | `#10B981` | emerald-500 |
| `--admin-danger` | `#DC2626` | red-600 |
| `--admin-warning` | `#F59E0B` | amber-500 |
| `--admin-info` | `#0EA5E9` | sky-500 |

**☀ Light (default preset: Full Light — NEW DEFAULT for installs)**
| Token | Value | Note |
|---|---|---|
| `--admin-bg-base` | `#FFFFFF` | pure white (Full Light variant) |
| `--admin-bg-surface` | `#FAFAFA` | very subtle off-white |
| `--admin-bg-card` | `#FFFFFF` | cards = pure white |
| `--admin-bg-hover` | `#F3F4F6` | gray-100 |
| `--admin-border` | `#E5E7EB` | gray-200 |
| `--admin-border-md` | `#D1D5DB` | gray-300 |
| `--admin-text-primary` | `#0F172A` | slate-900 |
| `--admin-text-secondary` | `#475569` | slate-600 |
| `--admin-text-muted` | `#94A3B8` | slate-400 |
| `--admin-primary` | **`#8B2942`** | **deep wine maroon** (was violet `#6D28D9`) |
| `--admin-primary-hover` | **`#722F37`** | darker wine |
| `--admin-primary-text` | `#FFFFFF` | contrast |
| `--admin-success` | `#047857` | emerald-700 (deeper for light) |
| `--admin-danger` | `#B91C1C` | red-700 |
| `--admin-warning` | `#B45309` | amber-700 |
| `--admin-info` | `#0369A1` | sky-700 |

### 2. Section List (10 final)

1. Surfaces
2. Text
3. Buttons
4. Forms
5. Badges
6. Tables
7. Alerts
8. **Modal** (added)
9. **Topbar** (added)
10. Sidebar

### 3. Starter Presets (4, with revisions)

| Preset | Category | Mode |
|---|---|---|
| **Full Light** ⭐ DEFAULT new installs | Light starters | light |
| **Studio Light** | Light starters | light |
| **Maroon** (renamed from "Midnight Navy") | Dark starters | dark |
| **Command Center Dark** | Dark starters | dark |

Customizer tab `🌙 Night` shows Dark starters; tab `☀ Light` shows Light starters.

---

## Files Modified

- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — §E.19.1 updated with locked values, §E.19.3 sub-step count unchanged (7), §E.19.4 wireframe updated to 10 sections + cobalt blue tokens
- `ai/reports/UIUX/step-19-1-handoff.md` — this file (new)

---

## Open Items (deferred to 19.2)

- Exact JSON shape per palette key (Step 19.2 will lock)
- Migration column order / index strategy
- Seed file for 4 presets

---

## Next Step

→ **Step 19.2** — Migration (2 JSON columns) + seed 4 presets + service rewrite `toCssVars($mode)` mengambil dari palette aktif (dark_palette atau light_palette)
