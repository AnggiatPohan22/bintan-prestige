@extends('layouts.admin')

@section('content')

@php
    $presetsJson = json_encode(collect($presets)->map(fn($p) => array_diff_key($p, array_flip(['label', 'description']))));
    $activePresetKey = collect($presets)->search(fn($p) => isset($p['preset_name']) && $p['preset_name'] === $appearance->preset_name) ?: '';
@endphp

<div x-data="appearanceEditor({{ $presetsJson }})" >

{{-- Page Header --}}
<div class="admin-page-header mb-6">
    <h1 class="admin-page-title">Customize Dashboard</h1>
    <p class="admin-page-subtitle">Sesuaikan tampilan panel admin — warna, tema, dan preset.</p>
</div>

{{-- Preset Selector --}}
<div class="admin-card mb-6">
    <div class="admin-card-header">
        <h2 class="text-sm font-bold uppercase tracking-wider" style="color: var(--admin-text-secondary);">
            <i class="fa-solid fa-swatchbook mr-2 text-violet-400" aria-hidden="true"></i>
            Pilih Preset
        </h2>
    </div>
    <div class="admin-card-body">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach($presets as $key => $preset)
            <button
                type="button"
                x-on:click="applyPreset('{{ $key }}')"
                x-bind:class="activePreset === '{{ $key }}' ? 'ring-2 ring-violet-500 border-violet-500' : 'border-[var(--admin-border)] hover:border-[var(--admin-border-md)]'"
                class="relative flex flex-col items-start gap-2 rounded-xl border p-4 text-left transition focus:outline-none focus:ring-2 focus:ring-violet-500"
                style="background: {{ $preset['bg_base'] }};"
            >
                <span class="flex gap-1.5">
                    <span class="h-4 w-4 rounded-full border border-white/20" style="background: {{ $preset['primary_color'] }};"></span>
                    <span class="h-4 w-4 rounded-full border border-white/20" style="background: {{ $preset['accent_color'] }};"></span>
                    <span class="h-4 w-4 rounded-full border border-white/20" style="background: {{ $preset['bg_card'] }};"></span>
                </span>
                <span class="text-xs font-semibold" style="color: {{ in_array($preset['mode'], ['light','light_classic']) ? '#0F172A' : '#F1F5F9' }};">
                    {{ $preset['label'] }}
                </span>
                <span class="text-[11px] leading-tight" style="color: {{ in_array($preset['mode'], ['light','light_classic']) ? '#64748B' : '#94A3B8' }};">
                    {{ $preset['description'] }}
                </span>
                <span
                    x-show="activePreset === '{{ $key }}'"
                    class="absolute right-2 top-2 text-violet-400"
                    aria-hidden="true"
                >
                    <i class="fa-solid fa-circle-check text-sm"></i>
                </span>
            </button>
            @endforeach
        </div>
    </div>
</div>

{{-- Color Form --}}
<form
    id="appearance-form"
    method="POST"
    action="{{ route('admin.settings.appearance.update') }}"
>
@csrf
<input type="hidden" name="preset_name" x-bind:value="fields.preset_name">

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    {{-- Mode & Sidebar --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="text-sm font-bold uppercase tracking-wider" style="color: var(--admin-text-secondary);">
                <i class="fa-solid fa-moon mr-2" aria-hidden="true"></i>
                Mode & Sidebar
            </h2>
        </div>
        <div class="admin-card-body space-y-4">

            <div>
                <label class="admin-form-label" for="mode">Mode</label>
                <select id="mode" name="mode" class="admin-input" x-model="fields.mode">
                    <option value="dark">Dark</option>
                    <option value="light">Light</option>
                    <option value="light_classic">Light Classic (sidebar gelap)</option>
                </select>
                @error('mode')
                    <p class="mt-1.5 text-xs" style="color: var(--admin-danger);">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="admin-form-label" for="sidebar_style">Sidebar Style</label>
                <select id="sidebar_style" name="sidebar_style" class="admin-input" x-model="fields.sidebar_style">
                    <option value="dark">Dark</option>
                    <option value="light">Light</option>
                </select>
                @error('sidebar_style')
                    <p class="mt-1.5 text-xs" style="color: var(--admin-danger);">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="admin-form-label" for="sidebar_bg">Sidebar Background</label>
                <div class="flex items-center gap-3">
                    <input
                        type="color"
                        class="h-10 w-12 cursor-pointer rounded-lg border p-1"
                        style="border-color: var(--admin-border); background: transparent;"
                        x-model="fields.sidebar_bg"
                        aria-label="Pilih warna sidebar background"
                    >
                    <input
                        type="text"
                        id="sidebar_bg"
                        name="sidebar_bg"
                        class="admin-input font-mono"
                        x-model="fields.sidebar_bg"
                        placeholder="#020617"
                        maxlength="7"
                    >
                </div>
                @error('sidebar_bg')
                    <p class="mt-1.5 text-xs" style="color: var(--admin-danger);">{{ $message }}</p>
                @enderror
            </div>

        </div>
    </div>

    {{-- Primary Color --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="text-sm font-bold uppercase tracking-wider" style="color: var(--admin-text-secondary);">
                <i class="fa-solid fa-circle-half-stroke mr-2 text-violet-400" aria-hidden="true"></i>
                Primary Color
            </h2>
        </div>
        <div class="admin-card-body space-y-4">

            @foreach([
                ['id' => 'primary_color', 'label' => 'Primary', 'placeholder' => '#7C3AED'],
                ['id' => 'primary_hover', 'label' => 'Primary Hover', 'placeholder' => '#6D28D9'],
                ['id' => 'primary_text',  'label' => 'Text di atas Primary', 'placeholder' => '#FFFFFF'],
            ] as $field)
            <div>
                <label class="admin-form-label" for="{{ $field['id'] }}">{{ $field['label'] }}</label>
                <div class="flex items-center gap-3">
                    <input type="color"
                        class="h-10 w-12 cursor-pointer rounded-lg border p-1"
                        style="border-color: var(--admin-border); background: transparent;"
                        x-model="fields.{{ $field['id'] }}"
                        aria-label="{{ $field['label'] }}">
                    <input type="text"
                        id="{{ $field['id'] }}"
                        name="{{ $field['id'] }}"
                        class="admin-input font-mono"
                        x-model="fields.{{ $field['id'] }}"
                        placeholder="{{ $field['placeholder'] }}"
                        maxlength="7">
                </div>
                @error($field['id'])
                    <p class="mt-1.5 text-xs" style="color: var(--admin-danger);">{{ $message }}</p>
                @enderror
            </div>
            @endforeach

        </div>
    </div>

    {{-- Accent & Brand Gold --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="text-sm font-bold uppercase tracking-wider" style="color: var(--admin-text-secondary);">
                <i class="fa-solid fa-star mr-2 text-yellow-400" aria-hidden="true"></i>
                Accent & Brand
            </h2>
        </div>
        <div class="admin-card-body space-y-4">

            @foreach([
                ['id' => 'accent_color', 'label' => 'Accent (Cyan)', 'placeholder' => '#06B6D4'],
                ['id' => 'gold_color',   'label' => 'Brand Gold',    'placeholder' => '#D4AF37'],
            ] as $field)
            <div>
                <label class="admin-form-label" for="{{ $field['id'] }}">{{ $field['label'] }}</label>
                <div class="flex items-center gap-3">
                    <input type="color"
                        class="h-10 w-12 cursor-pointer rounded-lg border p-1"
                        style="border-color: var(--admin-border); background: transparent;"
                        x-model="fields.{{ $field['id'] }}"
                        aria-label="{{ $field['label'] }}">
                    <input type="text"
                        id="{{ $field['id'] }}"
                        name="{{ $field['id'] }}"
                        class="admin-input font-mono"
                        x-model="fields.{{ $field['id'] }}"
                        placeholder="{{ $field['placeholder'] }}"
                        maxlength="7">
                </div>
                @error($field['id'])
                    <p class="mt-1.5 text-xs" style="color: var(--admin-danger);">{{ $message }}</p>
                @enderror
            </div>
            @endforeach

            <div class="flex items-center gap-3 pt-1">
                <input
                    type="checkbox"
                    id="show_gold"
                    name="show_gold"
                    value="1"
                    class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500"
                    x-model="fields.show_gold"
                >
                <label for="show_gold" class="admin-form-label mb-0 cursor-pointer">
                    Tampilkan gold hints di UI
                </label>
            </div>

        </div>
    </div>

    {{-- Background System --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="text-sm font-bold uppercase tracking-wider" style="color: var(--admin-text-secondary);">
                <i class="fa-solid fa-layer-group mr-2" aria-hidden="true"></i>
                Background System
            </h2>
        </div>
        <div class="admin-card-body space-y-4">

            @foreach([
                ['id' => 'bg_base',  'label' => 'Page Base',       'placeholder' => '#020617'],
                ['id' => 'bg_card',  'label' => 'Card Background',  'placeholder' => '#1E293B'],
                ['id' => 'bg_input', 'label' => 'Input Background', 'placeholder' => '#0F172A'],
            ] as $field)
            <div>
                <label class="admin-form-label" for="{{ $field['id'] }}">{{ $field['label'] }}</label>
                <div class="flex items-center gap-3">
                    <input type="color"
                        class="h-10 w-12 cursor-pointer rounded-lg border p-1"
                        style="border-color: var(--admin-border); background: transparent;"
                        x-model="fields.{{ $field['id'] }}"
                        aria-label="{{ $field['label'] }}">
                    <input type="text"
                        id="{{ $field['id'] }}"
                        name="{{ $field['id'] }}"
                        class="admin-input font-mono"
                        x-model="fields.{{ $field['id'] }}"
                        placeholder="{{ $field['placeholder'] }}"
                        maxlength="7">
                </div>
                @error($field['id'])
                    <p class="mt-1.5 text-xs" style="color: var(--admin-danger);">{{ $message }}</p>
                @enderror
            </div>
            @endforeach

        </div>
    </div>

</div>{{-- /grid --}}

</form>{{-- /appearance-form — ditutup di sini agar reset form tidak nested di dalamnya --}}

{{-- Actions — di luar #appearance-form; save button pakai form="appearance-form" (HTML5) --}}
<div class="mt-6 flex flex-wrap items-center justify-between gap-4">

    {{-- Reset — audit fix: POST form + CSRF (bukan window.location.href GET) --}}
    <form
        method="POST"
        action="{{ route('admin.settings.appearance.reset') }}"
        onsubmit="return confirm('Reset semua pengaturan tampilan ke default Command Center Dark?')"
    >
        @csrf
        <button type="submit" class="admin-btn-secondary inline-flex items-center gap-2">
            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
            Reset ke Default
        </button>
    </form>

    <button type="submit" form="appearance-form" class="admin-btn-primary inline-flex items-center gap-2">
        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
        Simpan Perubahan
    </button>

</div>

</div>{{-- /x-data --}}

@endsection

@push('scripts')
<script>
function appearanceEditor(presets) {
    return {
        presets: presets,
        activePreset: @json($activePresetKey),

        fields: {
            mode:          @json($appearance->mode),
            sidebar_style: @json($appearance->sidebar_style),
            sidebar_bg:    @json($appearance->sidebar_bg),
            primary_color: @json($appearance->primary_color),
            primary_hover: @json($appearance->primary_hover),
            primary_text:  @json($appearance->primary_text),
            accent_color:  @json($appearance->accent_color),
            gold_color:    @json($appearance->gold_color),
            show_gold:     @json((bool) $appearance->show_gold),
            bg_base:       @json($appearance->bg_base),
            bg_card:       @json($appearance->bg_card),
            bg_input:      @json($appearance->bg_input),
            preset_name:   @json($appearance->preset_name),
        },

        init() {
            this.$watch('fields', () => this.livePreview());
        },

        applyPreset(key) {
            const p = this.presets[key];
            if (!p) return;
            this.activePreset = key;
            Object.assign(this.fields, p);
            // livePreview triggered by $watch
        },

        livePreview() {
            const root = document.documentElement.style;
            const f    = this.fields;
            const hex  = (v) => (v || '').replace('#', '');

            root.setProperty('--admin-bg-base',               f.bg_base);
            root.setProperty('--admin-bg-card',               f.bg_card);
            root.setProperty('--admin-bg-input',              f.bg_input);
            root.setProperty('--admin-sidebar-bg',            f.sidebar_bg);
            root.setProperty('--admin-primary',               f.primary_color);
            root.setProperty('--admin-primary-hover',         f.primary_hover);
            root.setProperty('--admin-primary-text',          f.primary_text);
            root.setProperty('--admin-primary-soft',          '#' + hex(f.primary_color) + '26');
            root.setProperty('--admin-primary-glow',          '#' + hex(f.primary_color) + '66');
            root.setProperty('--admin-accent',                f.accent_color);
            root.setProperty('--admin-accent-soft',           '#' + hex(f.accent_color) + '26');
            root.setProperty('--admin-gold',                  f.gold_color);
            root.setProperty('--admin-gold-soft',             '#' + hex(f.gold_color) + '1A');
            root.setProperty('--admin-sidebar-active-bg',     '#' + hex(f.primary_color) + '33');
            root.setProperty('--admin-sidebar-active-text',   f.primary_color);
            root.setProperty('--admin-sidebar-active-border', f.primary_color);

            // Mode HTML attribute (activates Step 10 CSS overrides)
            const html = document.documentElement;
            if (f.mode !== 'dark') {
                html.setAttribute('data-admin-mode', 'light');
            } else {
                html.removeAttribute('data-admin-mode');
            }

            // Sidebar light attribute
            if (f.sidebar_style === 'light') {
                html.setAttribute('data-admin-sidebar', 'light');
            } else {
                html.removeAttribute('data-admin-sidebar');
            }
        },
    };
}
</script>
@endpush
