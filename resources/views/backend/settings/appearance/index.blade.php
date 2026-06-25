@extends('layouts.admin')

@section('content')

@php
    // Build preset list for JS: {key: {label, mode, tokens: {...}}}
    $presetsJs = collect($presets)->map(fn($p) => [
        'label'  => $p['label'],
        'mode'   => $p['mode'],
        'tokens' => $p['tokens'],
    ])->toArray();

    // Seed dark / light tokens — fall back to first matching preset if empty
    $seedDark  = ! empty($darkTokens)  ? $darkTokens  : ($presets['command-center-dark']['tokens'] ?? []);
    $seedLight = ! empty($lightTokens) ? $lightTokens : ($presets['full-light']['tokens'] ?? []);
@endphp

<div x-data="customizerV2({
    initialMode:    @js($initialMode),
    darkTokens:     @js($seedDark),
    lightTokens:    @js($seedLight),
    sections:       @js($sections),
    tokenCatalogue: @js($tokenCatalogue),
    presets:        @js($presetsJs),
    saveUrl:        @js(route('admin.settings.appearance.palette.save')),
    resetUrl:       @js(route('admin.settings.appearance.reset')),
    csrfToken:      @js(csrf_token()),
})">

{{-- ═══════════════════════════════════════════════════════════════
     PAGE HEADER + MODE TABS
═══════════════════════════════════════════════════════════════ --}}
<div class="admin-page-header mb-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="admin-page-title">Customize Dashboard</h1>
            <p class="admin-page-subtitle">Atur warna per mode — perubahan tampil langsung di halaman ini.</p>
        </div>

        {{-- Mode tabs --}}
        <div class="flex overflow-hidden rounded-xl border border-admin bg-admin-card p-1">
            <button
                type="button"
                @click="setMode('dark')"
                :class="activeMode === 'dark' ? 'bg-indigo-600 text-white shadow' : 'text-admin-secondary hover:text-admin-primary'"
                class="flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-semibold transition"
            >
                <i class="fa-solid fa-moon text-xs" aria-hidden="true"></i> Night
            </button>
            <button
                type="button"
                @click="setMode('light')"
                :class="activeMode === 'light' ? 'bg-indigo-600 text-white shadow' : 'text-admin-secondary hover:text-admin-primary'"
                class="flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-semibold transition"
            >
                <i class="fa-solid fa-sun text-xs" aria-hidden="true"></i> Light
            </button>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="admin-alert-success mb-5" role="alert">
        <i class="fa-solid fa-circle-check mr-2" aria-hidden="true"></i>{{ session('success') }}
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     PRESET STRIP
═══════════════════════════════════════════════════════════════ --}}
<div class="admin-card mb-6 p-4">
    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-admin-secondary">
        <i class="fa-solid fa-swatchbook mr-1.5" aria-hidden="true"></i>
        Starter Presets
        <span class="ml-2 font-normal normal-case text-admin-muted">— klik untuk isi token mode aktif</span>
    </p>

    <div class="flex flex-wrap gap-3">
        <template x-for="[key, preset] in Object.entries(presets)" :key="key">
            <button
                type="button"
                x-show="preset.mode === activeMode"
                @click="applyPreset(key)"
                :class="activePreset === key
                    ? 'ring-2 ring-indigo-500 border-indigo-500 shadow-md'
                    : 'border-admin hover:border-indigo-400'"
                class="relative flex min-w-[140px] flex-col gap-2 rounded-xl border p-3 text-left transition focus:outline-none focus:ring-2 focus:ring-indigo-500"
                :style="`background: ${preset.tokens['bg-card'] ?? '#fff'}`"
            >
                <span class="flex gap-1">
                    <span class="h-4 w-4 rounded-full border border-white/20 shadow-sm"
                          :style="`background: ${preset.tokens['primary']}`"></span>
                    <span class="h-4 w-4 rounded-full border border-white/20 shadow-sm"
                          :style="`background: ${preset.tokens['bg-surface']}`"></span>
                    <span class="h-4 w-4 rounded-full border border-white/20 shadow-sm"
                          :style="`background: ${preset.tokens['success']}`"></span>
                </span>
                <span class="text-xs font-semibold"
                      :style="`color: ${preset.tokens['text-primary']}`"
                      x-text="preset.label"></span>
                <span x-show="activePreset === key"
                      class="absolute right-2 top-2 text-indigo-400"
                      aria-hidden="true">
                    <i class="fa-solid fa-circle-check text-sm"></i>
                </span>
            </button>
        </template>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     MAIN LAYOUT — section sidebar + editor panel
═══════════════════════════════════════════════════════════════ --}}
<div class="overflow-hidden rounded-2xl border border-admin bg-admin-card">
    <div class="flex min-h-[520px]">

        {{-- ── Section Sidebar ──────────────────────────────── --}}
        <aside class="flex w-48 shrink-0 flex-col border-r border-admin bg-admin-card p-2">

            <p class="mb-2 px-2 text-[10px] font-bold uppercase tracking-widest text-admin-muted">Sections</p>

            <nav class="flex-1 space-y-0.5" aria-label="Customizer sections">
                <template x-for="[sectionKey, section] in Object.entries(sections)" :key="sectionKey">
                    <button
                        type="button"
                        @click="activeSection = sectionKey"
                        :class="activeSection === sectionKey
                            ? 'bg-admin-surface text-admin-primary font-semibold'
                            : 'text-admin-secondary hover:opacity-75'"
                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition"
                        :aria-current="activeSection === sectionKey ? 'true' : 'false'"
                    >
                        <i :class="'fa-solid ' + section.icon + ' w-4 text-xs'" aria-hidden="true"></i>
                        <span x-text="section.label"></span>
                    </button>
                </template>
            </nav>

            {{-- Save + Reset --}}
            <div class="mt-4 space-y-2 border-t border-admin pt-3">
                <button
                    type="button"
                    @click="savePalette()"
                    :disabled="saving"
                    class="admin-btn-primary flex w-full items-center justify-center gap-1.5 py-2 text-xs"
                >
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true" x-show="!saving && !saved"></i>
                    <i class="fa-solid fa-spinner fa-spin" aria-hidden="true" x-show="saving" x-cloak></i>
                    <i class="fa-solid fa-check" aria-hidden="true" x-show="saved" x-cloak></i>
                    <span x-text="saving ? 'Menyimpan…' : (saved ? 'Tersimpan!' : 'Simpan')"></span>
                </button>

                <form method="POST" action="{{ route('admin.settings.appearance.reset') }}"
                      @submit.prevent="resetPalette($el)">
                    @csrf
                    <button type="submit" class="admin-btn-secondary flex w-full items-center justify-center gap-1.5 py-2 text-xs">
                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                        Reset Default
                    </button>
                </form>
            </div>
        </aside>

        {{-- ── Editor Panel ──────────────────────────────────── --}}
        <div class="min-w-0 flex-1 overflow-y-auto p-6">

            {{-- Section heading --}}
            <h2 class="mb-4 flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-admin-secondary">
                <i :class="'fa-solid ' + (sections[activeSection]?.icon ?? 'fa-circle')" aria-hidden="true"></i>
                <span x-text="sections[activeSection]?.label ?? activeSection"></span>
            </h2>

            {{-- ── LIVE PREVIEW ─────────────────────────────── --}}
            <div class="mb-6 rounded-xl border border-admin bg-admin-card p-4">
                <p class="mb-3 text-[10px] font-bold uppercase tracking-widest text-admin-muted">Preview</p>

                {{-- Surfaces --}}
                <template x-if="activeSection === 'surfaces'">
                    <div class="space-y-2 text-xs">
                        <div class="rounded-lg p-3" style="background: var(--admin-bg-base); border: 1px solid var(--admin-border)">
                            <span style="color: var(--admin-text-muted)">Base background</span>
                            <div class="mt-2 rounded-md p-2" style="background: var(--admin-bg-surface); border: 1px solid var(--admin-border)">
                                <span style="color: var(--admin-text-secondary)">Surface</span>
                                <div class="mt-1 rounded p-2" style="background: var(--admin-bg-card); border: 1px solid var(--admin-border)">
                                    <span style="color: var(--admin-text-primary)">Card</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 pt-1">
                            <span class="flex-1 rounded px-2 py-1 text-center text-[10px]" style="background: var(--admin-border); color: var(--admin-text-muted)">border</span>
                            <span class="flex-1 rounded px-2 py-1 text-center text-[10px]" style="background: var(--admin-border-md); color: var(--admin-text-secondary)">border-md</span>
                            <span class="flex-1 rounded px-2 py-1 text-center text-[10px]" style="background: var(--admin-border-strong); color: var(--admin-text-primary)">border-strong</span>
                        </div>
                    </div>
                </template>

                {{-- Text --}}
                <template x-if="activeSection === 'text'">
                    <div class="space-y-2">
                        <p class="text-base font-bold" style="color: var(--admin-text-primary)">Primary heading text</p>
                        <p class="text-sm" style="color: var(--admin-text-secondary)">Secondary — descriptions and meta hints</p>
                        <p class="text-xs" style="color: var(--admin-text-muted)">Muted — timestamps, tiny annotations</p>
                        <a href="#" class="text-xs underline" style="color: var(--admin-text-link)" tabindex="-1">Link text example</a>
                    </div>
                </template>

                {{-- Buttons --}}
                <template x-if="activeSection === 'buttons'">
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="admin-btn-primary text-xs py-1.5 px-3">Primary</button>
                        <button type="button" class="admin-btn-secondary text-xs py-1.5 px-3">Secondary</button>
                        <button type="button" class="admin-btn-danger text-xs py-1.5 px-3">Danger</button>
                        <button type="button" class="admin-btn-soft text-xs py-1.5 px-3">Soft</button>
                    </div>
                </template>

                {{-- Forms --}}
                <template x-if="activeSection === 'forms'">
                    <div class="max-w-xs space-y-3">
                        <div>
                            <label class="admin-form-label !text-xs" style="color: var(--admin-label-color)">Label</label>
                            <input type="text" class="admin-input text-sm" placeholder="Text input field…" readonly>
                        </div>
                        <div>
                            <label class="admin-form-label !text-xs" style="color: var(--admin-label-color)">Select</label>
                            <select class="admin-input text-sm">
                                <option>Option A</option>
                                <option>Option B</option>
                            </select>
                        </div>
                        <label class="flex cursor-pointer items-center gap-2 text-xs" style="color: var(--admin-label-color)">
                            <input type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500"> Checkbox label
                        </label>
                    </div>
                </template>

                {{-- Badges --}}
                <template x-if="activeSection === 'badges'">
                    <div class="flex flex-wrap gap-2">
                        <span class="admin-badge-success">Success</span>
                        <span class="admin-badge-warning">Warning</span>
                        <span class="admin-badge-danger">Danger</span>
                        <span class="admin-badge-info">Info</span>
                        <span class="admin-badge-neutral">Neutral</span>
                    </div>
                </template>

                {{-- Tables --}}
                <template x-if="activeSection === 'tables'">
                    <div class="admin-table-wrapper overflow-hidden rounded-lg">
                        <table class="admin-table w-full text-xs">
                            <thead class="admin-table-header">
                                <tr>
                                    <th class="px-3 py-2 text-left">Name</th>
                                    <th class="px-3 py-2 text-left">Status</th>
                                    <th class="px-3 py-2 text-right">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="admin-table-row">
                                    <td class="px-3 py-2">Tour Package</td>
                                    <td class="px-3 py-2"><span class="admin-badge-success">Active</span></td>
                                    <td class="px-3 py-2 text-right">12</td>
                                </tr>
                                <tr class="admin-table-row">
                                    <td class="px-3 py-2">Destination</td>
                                    <td class="px-3 py-2"><span class="admin-badge-warning">Draft</span></td>
                                    <td class="px-3 py-2 text-right">5</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                {{-- Alerts --}}
                <template x-if="activeSection === 'alerts'">
                    <div class="space-y-2 text-xs">
                        <div class="admin-alert-success">
                            <i class="fa-solid fa-circle-check mr-1.5" aria-hidden="true"></i> Operation completed successfully.
                        </div>
                        <div class="admin-alert-danger">
                            <i class="fa-solid fa-circle-xmark mr-1.5" aria-hidden="true"></i> Something went wrong. Please try again.
                        </div>
                        <div class="rounded-xl border px-4 py-3" style="background: var(--admin-alert-warning-bg); border-color: var(--admin-warning); color: var(--admin-warning)">
                            <i class="fa-solid fa-triangle-exclamation mr-1.5" aria-hidden="true"></i> Please review your input before saving.
                        </div>
                        <div class="rounded-xl border px-4 py-3" style="background: var(--admin-alert-info-bg); border-color: var(--admin-info); color: var(--admin-info)">
                            <i class="fa-solid fa-circle-info mr-1.5" aria-hidden="true"></i> Informational notice — no action needed.
                        </div>
                    </div>
                </template>

                {{-- Modal --}}
                <template x-if="activeSection === 'modal'">
                    <div class="relative overflow-hidden rounded-xl p-5" style="background: var(--admin-modal-overlay)">
                        <div class="admin-modal-content mx-auto max-w-xs p-4">
                            <h3 class="text-sm font-bold" style="color: var(--admin-text-primary)">Confirm Action</h3>
                            <p class="mt-1 text-xs" style="color: var(--admin-text-secondary)">This will permanently delete the selected item. Are you sure?</p>
                            <div class="mt-4 flex gap-2">
                                <button type="button" class="admin-btn-danger text-xs py-1.5 px-3">Delete</button>
                                <button type="button" class="admin-btn-secondary text-xs py-1.5 px-3">Cancel</button>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Topbar --}}
                <template x-if="activeSection === 'topbar'">
                    <div class="admin-topbar rounded-xl px-4 py-2.5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-xs" style="color: var(--admin-topbar-text)">
                                <span class="font-bold">Bintan Prestige</span>
                                <span class="opacity-40">/</span>
                                <span>Appearance</span>
                            </div>
                            <div class="flex items-center gap-3" style="color: var(--admin-topbar-text)">
                                <i class="fa-regular fa-moon text-sm" aria-hidden="true"></i>
                                <i class="fa-regular fa-bell text-sm" aria-hidden="true"></i>
                                <span class="text-xs font-medium">Admin</span>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Sidebar --}}
                <template x-if="activeSection === 'sidebar'">
                    <div class="w-44 rounded-xl border p-2 text-xs" style="background: var(--admin-sidebar-bg); border-color: var(--admin-sidebar-border)">
                        <p class="mb-2 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider" style="color: var(--admin-text-muted)">Content</p>
                        <div class="flex items-center gap-2 rounded-lg px-3 py-2" style="color: var(--admin-sidebar-text)">
                            <i class="fa-solid fa-file-lines w-3 text-[10px]" aria-hidden="true"></i> Pages
                        </div>
                        <div class="flex items-center gap-2 rounded-lg border-l-2 px-3 py-2"
                             style="background: var(--admin-sidebar-active-bg); color: var(--admin-sidebar-active-text); border-color: var(--admin-sidebar-active-border)">
                            <i class="fa-solid fa-palette w-3 text-[10px]" aria-hidden="true"></i> Appearance
                        </div>
                        <div class="flex items-center gap-2 rounded-lg px-3 py-2" style="color: var(--admin-sidebar-text)">
                            <i class="fa-solid fa-users w-3 text-[10px]" aria-hidden="true"></i> Users
                        </div>
                    </div>
                </template>
            </div>{{-- /preview --}}

            {{-- ── TOKEN COLOR PICKERS ──────────────────────── --}}
            <div class="space-y-3">
                <template
                    x-for="[tokenKey, meta] in Object.entries(tokenCatalogue).filter(([, m]) => m.section === activeSection)"
                    :key="tokenKey"
                >
                    <div class="flex items-center gap-3">
                        {{-- Color picker (hex only) --}}
                        <template x-if="isHex(currentTokens()[tokenKey])">
                            <input
                                type="color"
                                :value="currentTokens()[tokenKey] ?? '#000000'"
                                @input="updateToken(tokenKey, $event.target.value)"
                                class="h-9 w-10 shrink-0 cursor-pointer rounded-lg border border-admin bg-transparent p-0.5"
                                :aria-label="meta.label + ' picker'"
                            >
                        </template>
                        <template x-if="!isHex(currentTokens()[tokenKey])">
                            <div class="flex h-9 w-10 shrink-0 items-center justify-center rounded-lg border border-admin text-[9px] text-admin-muted">
                                rgba
                            </div>
                        </template>

                        {{-- Text hex / rgba input --}}
                        <input
                            type="text"
                            :value="currentTokens()[tokenKey] ?? ''"
                            @input="updateToken(tokenKey, $event.target.value)"
                            class="admin-input w-52 font-mono text-sm"
                            maxlength="28"
                            :aria-label="meta.label"
                        >

                        {{-- Label --}}
                        <span class="min-w-0 text-sm text-admin-secondary" x-text="meta.label"></span>
                    </div>
                </template>
            </div>

        </div>{{-- /editor panel --}}
    </div>{{-- /flex --}}
</div>{{-- /main layout --}}

</div>{{-- /x-data --}}

@endsection

@push('scripts')
<script>
function customizerV2(config) {
    return {
        // ── State ──────────────────────────────────────────
        activeMode:    config.initialMode === 'light' ? 'light' : 'dark',
        activeSection: 'surfaces',
        activePreset:  null,
        saving: false,
        saved:  false,

        // ── Config (server-rendered) ───────────────────────
        sections:       config.sections,
        tokenCatalogue: config.tokenCatalogue,
        presets:        config.presets,
        saveUrl:        config.saveUrl,
        resetUrl:       config.resetUrl,
        csrfToken:      config.csrfToken,

        // ── Mutable editing state (both modes in memory) ───
        editing: {
            dark:  { ...config.darkTokens },
            light: { ...config.lightTokens },
        },

        // ── Init ───────────────────────────────────────────
        init() {
            this.applyLivePreview();
        },

        // ── Helpers ────────────────────────────────────────
        currentTokens() {
            return this.editing[this.activeMode];
        },

        isHex(val) {
            return typeof val === 'string' && val.trim().startsWith('#');
        },

        // ── Mode switch ────────────────────────────────────
        setMode(mode) {
            this.activeMode   = mode;
            this.activePreset = null;
            this.applyLivePreview();
        },

        // ── Apply preset to active mode ────────────────────
        applyPreset(key) {
            const preset = this.presets[key];
            if (!preset || preset.mode !== this.activeMode) return;
            this.editing[this.activeMode] = { ...preset.tokens };
            this.activePreset = key;
            this.applyLivePreview();
        },

        // ── Update single token ────────────────────────────
        updateToken(key, value) {
            this.editing[this.activeMode][key] = value;
            this.activePreset = null;
            this.applyLivePreview();
        },

        // ── Apply CSS vars + mode attr live ───────────────
        applyLivePreview() {
            const tokens = this.editing[this.activeMode];
            const root   = document.documentElement;

            if (this.activeMode === 'light') {
                root.setAttribute('data-admin-mode', 'light');
            } else {
                root.removeAttribute('data-admin-mode');
            }

            Object.entries(tokens).forEach(([key, value]) => {
                if (value) root.style.setProperty(`--admin-${key}`, value);
            });
        },

        // ── AJAX save ──────────────────────────────────────
        async savePalette() {
            if (this.saving) return;
            this.saving = true;
            this.saved  = false;

            try {
                const res = await fetch(this.saveUrl, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({
                        mode:   this.activeMode,
                        tokens: this.editing[this.activeMode],
                    }),
                });

                if (res.ok) {
                    this.saved = true;
                    setTimeout(() => { this.saved = false; }, 2500);
                } else {
                    alert('Gagal menyimpan. Silakan coba lagi.');
                }
            } catch (_) {
                alert('Network error. Periksa koneksi dan coba lagi.');
            } finally {
                this.saving = false;
            }
        },

        // ── Reset via form submit ──────────────────────────
        resetPalette(formEl) {
            if (!confirm('Reset semua pengaturan tampilan ke default?\n\nKedua mode (Night & Light) dikembalikan ke preset bawaan.')) {
                return;
            }
            formEl.submit();
        },
    };
}
</script>
@endpush
