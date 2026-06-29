# STEP 15 — Settings UI: Customize Dashboard Page
# Bintan Prestige CMS — Admin UI/UX Redesign
# Paste prompt ini ke sesi Claude baru

---

## Konteks Sesi Ini

**Prerequisite:** Step 12 ✅ + Step 13 ✅ + Step 14 ✅

**Scope:** Buat `resources/views/admin/settings/appearance/index.blade.php`.
Halaman Settings > Customize Dashboard dengan:
- Preset brand theme cards
- Mode toggle (Dark / Light Classic / Light Full)
- Color pickers (primary, accent, gold, sidebar bg)
- Preview section (CSS inject live)
- Save + Reset buttons

**Referensi:**
- `ai/reports/UIUX/grand-master-plan-admin-uiux.md` — Section 21

---

## Risk Assessment

**Risk: 🟡 Medium**

Blade file baru — tidak mengubah existing views.
Alpine.js `x-data` component baru untuk live preview.
Form POST ke route yang sudah ada di Step 14.

---

## Phase 1 — Inspect

```bash
# Cek struktur views/admin/settings/ jika ada
ls resources/views/admin/settings/ 2>/dev/null || echo "belum ada"

# Cek layout yang dipakai
head -5 resources/views/admin/users/index.blade.php
```

Buat direktori jika perlu: `resources/views/admin/settings/appearance/`

---

## Phase 3 — Implementasi

Buat `resources/views/admin/settings/appearance/index.blade.php`:

```blade
@extends('layouts.admin')

@section('content')
<div class="admin-page" x-data="appearanceEditor(@json($appearance->toArray()), @json($presets))">

    {{-- Page Header --}}
    <div class="admin-page-header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="admin-page-title">Customize Dashboard</h1>
                <p class="admin-page-subtitle">
                    Ubah tampilan admin dashboard. Perubahan berlaku untuk semua admin user.
                </p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="admin-btn-secondary admin-btn-sm shrink-0">
                <i class="fa-solid fa-arrow-left"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- === PANEL KIRI: EDITOR === --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Preset Themes --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 class="text-xs font-black uppercase tracking-wider" style="color: var(--admin-text-secondary)">
                        Brand Presets
                    </h2>
                </div>
                <div class="admin-card-body">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                        @foreach($presets as $key => $preset)
                        <button
                            type="button"
                            class="group relative flex flex-col items-center gap-2 rounded-lg border px-3 py-3 text-center transition duration-150"
                            :class="currentPreset === '{{ $key }}'
                                ? 'border-[var(--admin-primary)] bg-[var(--admin-primary-soft)]'
                                : 'border-[var(--admin-border)] hover:border-[var(--admin-border-md)]'"
                            x-on:click="applyPreset('{{ $key }}')"
                        >
                            {{-- Color Swatches --}}
                            <div class="flex gap-1">
                                <span class="h-5 w-5 rounded-md ring-1 ring-white/10"
                                      :style="`background: ${presets['{{ $key }}'].primary_color}`"></span>
                                <span class="h-5 w-5 rounded-md ring-1 ring-white/10"
                                      :style="`background: ${presets['{{ $key }}'].accent_color}`"></span>
                                <span class="h-5 w-5 rounded-md ring-1 ring-white/10"
                                      :style="`background: ${presets['{{ $key }}'].sidebar_bg}`"></span>
                            </div>
                            <span class="text-xs font-semibold" style="color: var(--admin-text-secondary)">
                                {{ $preset['label'] }}
                            </span>
                            {{-- Active dot --}}
                            <span x-show="currentPreset === '{{ $key }}'"
                                  class="absolute right-2 top-2 h-2 w-2 rounded-full"
                                  style="background: var(--admin-primary)">
                            </span>
                        </button>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs" style="color: var(--admin-text-muted)">
                        Pilih preset untuk mengisi warna otomatis, lalu kustomisasi lebih lanjut di bawah.
                    </p>
                </div>
            </div>

            {{-- Custom Colors Form --}}
            <form method="POST" action="{{ route('admin.settings.appearance.update') }}">
                @csrf

                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2 class="text-xs font-black uppercase tracking-wider" style="color: var(--admin-text-secondary)">
                            Custom Colors
                        </h2>
                    </div>
                    <div class="admin-card-body space-y-6">

                        {{-- Mode Toggle --}}
                        <div>
                            <label class="admin-form-label">Display Mode</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach(['dark' => ['label' => 'Dark', 'desc' => 'Full dark (default)'],
                                          'light_classic' => ['label' => 'Light Classic', 'desc' => 'Dark sidebar + light content'],
                                          'light' => ['label' => 'Light Full', 'desc' => 'Fully light']] as $val => $item)
                                <label class="flex cursor-pointer flex-col gap-0.5 rounded-lg border px-4 py-2.5 transition"
                                       :class="formData.mode === '{{ $val }}'
                                           ? 'border-[var(--admin-primary)] bg-[var(--admin-primary-soft)]'
                                           : 'border-[var(--admin-border)] hover:border-[var(--admin-border-md)]'">
                                    <input type="radio" name="mode" value="{{ $val }}"
                                           x-model="formData.mode"
                                           x-on:change="updateLivePreview()"
                                           class="sr-only">
                                    <span class="text-sm font-bold" style="color: var(--admin-text-primary)">{{ $item['label'] }}</span>
                                    <span class="text-xs" style="color: var(--admin-text-muted)">{{ $item['desc'] }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Primary Color --}}
                        <div>
                            <label class="admin-form-label">
                                Primary Color
                                <span class="normal-case font-normal ml-1" style="color: var(--admin-text-muted)">— buttons, active state, focus ring</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="primary_color_picker"
                                       x-model="formData.primary_color"
                                       x-on:input="syncHover(); updateLivePreview()"
                                       class="h-10 w-10 shrink-0 cursor-pointer rounded-lg border-0 bg-transparent p-0.5">
                                <input type="text" name="primary_color"
                                       x-model="formData.primary_color"
                                       x-on:input="syncHover(); updateLivePreview()"
                                       class="admin-input w-32 font-mono text-sm uppercase"
                                       maxlength="7" placeholder="#7C3AED"
                                       pattern="^#[0-9A-Fa-f]{6}$">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs" style="color: var(--admin-text-muted)">Preview:</span>
                                    <button type="button" class="rounded-lg px-3 py-1.5 text-xs font-bold text-white transition"
                                            :style="`background: ${formData.primary_color}`">
                                        Save Button
                                    </button>
                                </div>
                            </div>
                            <input type="hidden" name="primary_hover" x-model="formData.primary_hover">
                            <input type="hidden" name="primary_text" value="#FFFFFF">
                        </div>

                        {{-- Accent Color --}}
                        <div>
                            <label class="admin-form-label">
                                Accent Color
                                <span class="normal-case font-normal ml-1" style="color: var(--admin-text-muted)">— info badge, link, chart</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="accent_color_picker"
                                       x-model="formData.accent_color"
                                       x-on:input="updateLivePreview()"
                                       class="h-10 w-10 shrink-0 cursor-pointer rounded-lg border-0 bg-transparent p-0.5">
                                <input type="text" name="accent_color"
                                       x-model="formData.accent_color"
                                       x-on:input="updateLivePreview()"
                                       class="admin-input w-32 font-mono text-sm uppercase"
                                       maxlength="7" placeholder="#06B6D4"
                                       pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                        </div>

                        {{-- Sidebar Background --}}
                        <div>
                            <label class="admin-form-label">
                                Sidebar Background
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="sidebar_bg_picker"
                                       x-model="formData.sidebar_bg"
                                       x-on:input="updateLivePreview()"
                                       class="h-10 w-10 shrink-0 cursor-pointer rounded-lg border-0 bg-transparent p-0.5">
                                <input type="text" name="sidebar_bg"
                                       x-model="formData.sidebar_bg"
                                       x-on:input="updateLivePreview()"
                                       class="admin-input w-32 font-mono text-sm uppercase"
                                       maxlength="7" placeholder="#020617"
                                       pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                        </div>

                        {{-- Sidebar Style --}}
                        <div>
                            <label class="admin-form-label">Sidebar Style</label>
                            <div class="flex gap-3">
                                @foreach(['dark' => 'Dark Sidebar', 'light' => 'Light Sidebar'] as $val => $label)
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border px-4 py-2.5 transition"
                                       :class="formData.sidebar_style === '{{ $val }}'
                                           ? 'border-[var(--admin-primary)] bg-[var(--admin-primary-soft)]'
                                           : 'border-[var(--admin-border)] hover:border-[var(--admin-border-md)]'">
                                    <input type="radio" name="sidebar_style" value="{{ $val }}"
                                           x-model="formData.sidebar_style"
                                           x-on:change="updateLivePreview()"
                                           class="sr-only">
                                    <span class="h-4 w-4 rounded {{ $val === 'dark' ? 'bg-slate-900' : 'bg-white border border-slate-200' }}"></span>
                                    <span class="text-sm font-semibold" style="color: var(--admin-text-primary)">{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Background & Card Colors --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            @foreach(['bg_base' => 'Page Background', 'bg_card' => 'Card Background', 'bg_input' => 'Input Background'] as $field => $label)
                            <div>
                                <label class="admin-form-label">{{ $label }}</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" name="{{ $field }}_picker"
                                           x-model="formData.{{ $field }}"
                                           x-on:input="updateLivePreview()"
                                           class="h-9 w-9 shrink-0 cursor-pointer rounded-lg border-0 bg-transparent p-0.5">
                                    <input type="text" name="{{ $field }}"
                                           x-model="formData.{{ $field }}"
                                           x-on:input="updateLivePreview()"
                                           class="admin-input w-full font-mono text-xs uppercase"
                                           maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
                                </div>
                            </div>
                            @endforeach
                        </div>

                        {{-- Brand Gold --}}
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-3">
                                <input type="color" name="gold_color_picker"
                                       x-model="formData.gold_color"
                                       x-on:input="updateLivePreview()"
                                       class="h-9 w-9 shrink-0 cursor-pointer rounded-lg border-0 bg-transparent p-0.5">
                                <div>
                                    <label class="admin-form-label">Brand Gold</label>
                                    <input type="text" name="gold_color"
                                           x-model="formData.gold_color"
                                           class="admin-input w-32 font-mono text-xs uppercase"
                                           maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
                                </div>
                            </div>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" name="show_gold" value="1"
                                       x-model="formData.show_gold"
                                       class="rounded" style="accent-color: var(--admin-primary)">
                                <span class="text-sm font-semibold" style="color: var(--admin-text-secondary)">Tampilkan gold hint</span>
                            </label>
                            <input type="hidden" name="show_gold" value="0" x-show="!formData.show_gold">
                        </div>

                        <input type="hidden" name="preset_name" x-model="currentPreset">

                    </div>{{-- /card-body --}}

                    {{-- Card Footer --}}
                    <div class="flex items-center justify-between px-6 py-4"
                         style="border-top: 1px solid var(--admin-border)">
                        <button type="button"
                                class="admin-btn-secondary admin-btn-sm"
                                x-on:click="resetToDefault()"
                                :disabled="saving">
                            <i class="fa-solid fa-rotate-left"></i>
                            Reset ke Default
                        </button>
                        <div class="flex gap-3">
                            <a href="{{ route('admin.dashboard') }}" class="admin-btn-secondary admin-btn-sm">
                                Batal
                            </a>
                            <button type="submit" class="admin-btn-primary admin-btn-sm" :disabled="saving">
                                <i class="fa-solid fa-floppy-disk" x-show="!saving"></i>
                                <i class="fa-solid fa-spinner fa-spin" x-show="saving" x-cloak></i>
                                <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                            </button>
                        </div>
                    </div>

                </div>{{-- /admin-card --}}
            </form>

        </div>{{-- /col-span-2 --}}

        {{-- === PANEL KANAN: LIVE PREVIEW === --}}
        <div class="lg:col-span-1">
            <div class="admin-card sticky top-20">
                <div class="admin-card-header">
                    <h2 class="text-xs font-black uppercase tracking-wider" style="color: var(--admin-text-secondary)">
                        Live Preview
                    </h2>
                </div>
                <div class="admin-card-body p-4">
                    {{-- Mini preview mockup --}}
                    <div class="overflow-hidden rounded-lg border" style="border-color: var(--admin-border)">
                        {{-- Sidebar strip --}}
                        <div class="flex h-64">
                            <div class="w-16 shrink-0 p-2 space-y-2"
                                 :style="`background: ${formData.sidebar_bg}`">
                                {{-- Brand mark --}}
                                <div class="mx-auto h-6 w-6 rounded"
                                     :style="`background: ${formData.primary_color}`"></div>
                                {{-- Nav items --}}
                                @for($i = 0; $i < 5; $i++)
                                <div class="h-2 rounded"
                                     :class="'{{ $i }}' === '1' ? '' : ''"
                                     :style="'{{ $i }}' === '1'
                                         ? `background: ${formData.primary_color}33`
                                         : 'background: rgba(255,255,255,0.08)'">
                                </div>
                                @endfor
                            </div>
                            {{-- Content area --}}
                            <div class="flex-1 p-3 space-y-2"
                                 :style="`background: ${formData.bg_base}`">
                                {{-- Topbar --}}
                                <div class="h-6 rounded"
                                     :style="`background: ${formData.bg_card}; border: 1px solid rgba(255,255,255,0.08)`">
                                </div>
                                {{-- Card --}}
                                <div class="rounded p-2 space-y-1.5"
                                     :style="`background: ${formData.bg_card}; border: 1px solid rgba(255,255,255,0.08)`">
                                    <div class="h-1.5 w-2/3 rounded" style="background: rgba(255,255,255,0.15)"></div>
                                    <div class="h-1.5 w-full rounded" style="background: rgba(255,255,255,0.08)"></div>
                                    <div class="h-1.5 w-4/5 rounded" style="background: rgba(255,255,255,0.08)"></div>
                                    {{-- Button preview --}}
                                    <div class="flex gap-1.5 pt-1">
                                        <div class="h-4 w-10 rounded"
                                             :style="`background: ${formData.primary_color}`"></div>
                                        <div class="h-4 w-10 rounded"
                                             style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12)"></div>
                                    </div>
                                </div>
                                {{-- Second card --}}
                                <div class="rounded p-2"
                                     :style="`background: ${formData.bg_card}; border: 1px solid rgba(255,255,255,0.08)`">
                                    <div class="flex gap-1.5 items-center">
                                        <div class="h-4 w-4 rounded"
                                             :style="`background: ${formData.accent_color}22`"></div>
                                        <div class="h-1.5 w-1/2 rounded" style="background: rgba(255,255,255,0.12)"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="mt-3 text-xs text-center" style="color: var(--admin-text-muted)">
                        Preview diperbarui real-time
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function appearanceEditor(initial, presets) {
    return {
        formData: {
            mode:          initial.mode          ?? 'dark',
            primary_color: initial.primary_color ?? '#7C3AED',
            primary_hover: initial.primary_hover ?? '#6D28D9',
            accent_color:  initial.accent_color  ?? '#06B6D4',
            gold_color:    initial.gold_color    ?? '#D4AF37',
            show_gold:     initial.show_gold     ?? true,
            sidebar_bg:    initial.sidebar_bg    ?? '#020617',
            sidebar_style: initial.sidebar_style ?? 'dark',
            bg_base:       initial.bg_base       ?? '#020617',
            bg_card:       initial.bg_card       ?? '#1E293B',
            bg_input:      initial.bg_input      ?? '#0F172A',
        },
        presets: presets,
        currentPreset: initial.preset_name ?? null,
        saving: false,

        applyPreset(key) {
            const p = this.presets[key];
            if (!p) return;
            this.formData.mode          = p.mode;
            this.formData.primary_color = p.primary_color;
            this.formData.primary_hover = p.primary_hover ?? this.darken(p.primary_color);
            this.formData.accent_color  = p.accent_color;
            this.formData.gold_color    = p.gold_color;
            this.formData.show_gold     = p.show_gold ?? true;
            this.formData.sidebar_bg    = p.sidebar_bg;
            this.formData.sidebar_style = p.sidebar_style ?? 'dark';
            this.formData.bg_base       = p.bg_base;
            this.formData.bg_card       = p.bg_card;
            this.formData.bg_input      = p.bg_input;
            this.currentPreset          = key;
            this.updateLivePreview();
        },

        syncHover() {
            // Auto-darken primary untuk hover: tambah ~15% gelap
            this.formData.primary_hover = this.darken(this.formData.primary_color);
        },

        darken(hex) {
            // Sederhana: return same hex (bisa diimprove)
            return hex;
        },

        updateLivePreview() {
            const style = document.getElementById('admin-appearance-vars');
            if (!style) return;

            const p = this.formData.primary_color.replace('#', '');
            const a = this.formData.accent_color.replace('#', '');

            const darkVars = `
                --admin-border: rgba(255,255,255,0.08);
                --admin-border-md: rgba(255,255,255,0.12);
                --admin-text-primary: #F1F5F9;
                --admin-text-secondary: #94A3B8;
                --admin-text-muted: #64748B;
                --admin-bg-hover: #334155;
            `;
            const lightVars = `
                --admin-border: #E2E8F0;
                --admin-border-md: #CBD5E1;
                --admin-text-primary: #0F172A;
                --admin-text-secondary: #475569;
                --admin-text-muted: #94A3B8;
                --admin-bg-hover: #F1F5F9;
            `;

            style.textContent = `:root {
                --admin-bg-base: ${this.formData.bg_base};
                --admin-bg-card: ${this.formData.bg_card};
                --admin-bg-input: ${this.formData.bg_input};
                --admin-sidebar-bg: ${this.formData.sidebar_bg};
                --admin-primary: ${this.formData.primary_color};
                --admin-primary-hover: ${this.formData.primary_hover};
                --admin-primary-soft: #${p}26;
                --admin-primary-glow: #${p}66;
                --admin-accent: ${this.formData.accent_color};
                --admin-accent-soft: #${a}26;
                --admin-gold: ${this.formData.gold_color};
                --admin-sidebar-active-bg: #${p}33;
                --admin-sidebar-active-text: ${this.formData.primary_color};
                --admin-sidebar-active-border: ${this.formData.primary_color};
                ${this.formData.mode === 'light' ? lightVars : darkVars}
            }`;
        },

        resetToDefault() {
            if (!confirm('Reset ke "Command Center Dark"? Perubahan yang belum disimpan akan hilang.')) return;
            window.location.href = '{{ route("admin.settings.appearance.reset") }}';
            // POST via form jika perlu CSRF:
            // const form = document.createElement('form');
            // form.method = 'POST';
            // form.action = '{{ route("admin.settings.appearance.reset") }}';
            // form.innerHTML = '@csrf';
            // document.body.appendChild(form);
            // form.submit();
        }
    }
}
</script>
@endpush
```

---

## Phase 4 — Verifikasi

- [ ] Halaman `/admin/settings/dashboard-appearance` load tanpa error
- [ ] Preset cards tampil (warna swatches visible)
- [ ] Klik preset → warna terisi di form
- [ ] Ubah primary color picker → live preview update
- [ ] Mode toggle berfungsi
- [ ] Save button → POST, redirect, flash success
- [ ] Reset button → kembali ke default
- [ ] Halaman accessible oleh super admin
- [ ] Non-super-admin: 403

---

## Phase 5 — Buat Handoff

Buat `ai/reports/UIUX/step-15-handoff.md`.

---

## STOP
