@extends('layouts.admin')

@php
    /* Flatten schema → ['--css-var' => currentValue] for Alpine shared state. */
    $allTokens = [];
    foreach ($schema as $group) {
        foreach ($group['tokens'] ?? [] as $token) {
            $allTokens[$token['key']] = $overrides[$token['key']] ?? $token['default'] ?? '';
        }
    }
    $previewUrl = route('home');
@endphp

@section('content')

<div class="admin-page" x-data="themeCustomizer(@js($allTokens), @js($previewUrl))">

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.themes.index') }}"
               class="mb-1 inline-flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Back to Themes
            </a>
            <h1 class="text-lg font-extrabold text-slate-900">
                Customize: {{ $theme->name }}
            </h1>
            <p class="mt-0.5 text-sm text-slate-500">
                Override design tokens for this theme. Changes preview live on the right before you save.
            </p>
        </div>

        <div class="flex items-center gap-2 self-center shrink-0">
            @if($theme->is_active)
                <span class="admin-badge-success">Active Theme</span>
            @else
                <span class="admin-badge-neutral">Inactive</span>
            @endif
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <i class="fa-solid fa-circle-check mr-1.5" aria-hidden="true"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(empty($schema))

        {{-- No schema defined --}}
        <div class="admin-empty-state py-10">
            <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-2xl text-slate-400">
                <i class="fa-solid fa-code" aria-hidden="true"></i>
            </div>
            <p class="font-semibold text-slate-700">No customizable tokens defined</p>
            <p class="mt-1 max-w-xs text-center text-sm text-slate-400">
                Add a <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">customization_schema</code>
                section to this theme's <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">theme.json</code>
                to expose design tokens here.
            </p>
        </div>

    @else

        {{-- Two-column: form left, live preview right --}}
        <div class="flex flex-col gap-6 xl:flex-row xl:items-start">

            {{-- ── Left column: token form ────────────────────────────────── --}}
            <div class="w-full xl:w-[420px] shrink-0">

                <form method="POST" action="{{ route('admin.themes.customization.update', $theme) }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">

                        @foreach($schema as $groupKey => $group)
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                                <h2 class="mb-4 text-xs font-bold uppercase tracking-wider text-slate-500">
                                    {{ $group['label'] ?? ucfirst($groupKey) }}
                                </h2>

                                <div class="space-y-4">
                                    @foreach($group['tokens'] ?? [] as $token)
                                        @php
                                            $key  = $token['key'];
                                            $type = $token['type'] ?? 'text';
                                        @endphp

                                        <div>
                                            <label class="mb-1.5 block text-xs font-medium text-slate-600">
                                                {{ $token['label'] ?? $key }}
                                                <span class="ml-1 font-mono text-xs font-normal text-slate-400">{{ $key }}</span>
                                            </label>

                                            @if($type === 'color')
                                                <div class="flex items-center gap-2">
                                                    <input
                                                        type="color"
                                                        x-model="tokens[@js($key)]"
                                                        class="h-9 w-9 cursor-pointer rounded-lg border border-slate-200 p-0.5"
                                                        aria-label="Color picker for {{ $token['label'] ?? $key }}"
                                                    >
                                                    <input
                                                        type="text"
                                                        name="tokens[{{ $key }}]"
                                                        x-model="tokens[@js($key)]"
                                                        class="admin-input flex-1 font-mono text-sm"
                                                        placeholder="{{ $token['default'] ?? '#000000' }}"
                                                        maxlength="30"
                                                    >
                                                </div>
                                            @else
                                                <input
                                                    type="text"
                                                    name="tokens[{{ $key }}]"
                                                    x-model="tokens[@js($key)]"
                                                    class="admin-input w-full font-mono text-sm"
                                                    placeholder="{{ $token['default'] ?? '' }}"
                                                    maxlength="200"
                                                >
                                            @endif

                                            @if(isset($token['default']))
                                                <p class="mt-1 text-xs text-slate-400">
                                                    Default: <span class="font-mono">{{ $token['default'] }}</span>
                                                </p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                            </div>
                        @endforeach

                    </div>

                    {{-- Save / Reset --}}
                    <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                        <button type="submit" class="admin-btn-primary">
                            <i class="fa-solid fa-floppy-disk mr-1.5" aria-hidden="true"></i>
                            Save Customization
                        </button>

                        <a
                            href="{{ route('admin.themes.index') }}"
                            class="admin-btn-secondary"
                        >
                            Cancel
                        </a>

                        <form
                            method="POST"
                            action="{{ route('admin.themes.customization.destroy', $theme) }}"
                            onsubmit="return confirm('Reset all customization for {{ addslashes($theme->name) }} to schema defaults?')"
                            class="ml-auto"
                        >
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-btn-danger-ghost text-sm">
                                <i class="fa-solid fa-rotate-left mr-1.5" aria-hidden="true"></i>
                                Reset to Defaults
                            </button>
                        </form>
                    </div>

                </form>
            </div>

            {{-- ── Right column: live preview pane ───────────────────────── --}}
            <div class="min-w-0 flex-1">
                <div class="sticky top-4">

                    {{-- Preview toolbar --}}
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">
                            Live Preview
                        </span>

                        <div class="flex items-center gap-2">
                            {{-- Preview URL input --}}
                            <input
                                type="url"
                                x-model="previewUrl"
                                @change="reloadPreview()"
                                class="admin-input h-7 w-64 text-xs font-mono"
                                placeholder="{{ route('home') }}"
                                aria-label="Preview URL"
                            >

                            <button
                                type="button"
                                @click="reloadPreview()"
                                class="admin-btn-secondary h-7 px-3 text-xs"
                                title="Reload preview"
                            >
                                <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Viewport size switcher --}}
                    <div class="mb-2 flex items-center gap-1">
                        <button type="button"
                                @click="setViewport('desktop')"
                                :class="viewport === 'desktop' ? 'bg-slate-800 text-white' : 'bg-white text-slate-500 hover:bg-slate-100'"
                                class="rounded-lg border border-slate-200 px-2 py-1 text-xs transition"
                                title="Desktop view">
                            <i class="fa-solid fa-desktop" aria-hidden="true"></i>
                        </button>
                        <button type="button"
                                @click="setViewport('tablet')"
                                :class="viewport === 'tablet' ? 'bg-slate-800 text-white' : 'bg-white text-slate-500 hover:bg-slate-100'"
                                class="rounded-lg border border-slate-200 px-2 py-1 text-xs transition"
                                title="Tablet view">
                            <i class="fa-solid fa-tablet-screen-button" aria-hidden="true"></i>
                        </button>
                        <button type="button"
                                @click="setViewport('mobile')"
                                :class="viewport === 'mobile' ? 'bg-slate-800 text-white' : 'bg-white text-slate-500 hover:bg-slate-100'"
                                class="rounded-lg border border-slate-200 px-2 py-1 text-xs transition"
                                title="Mobile view">
                            <i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i>
                        </button>

                        <span class="ml-auto text-xs text-slate-400" x-show="!previewReady">
                            Loading preview…
                        </span>
                        <span class="ml-auto text-xs text-emerald-600" x-show="previewReady">
                            <i class="fa-solid fa-circle-check mr-1" aria-hidden="true"></i>
                            Preview live
                        </span>
                    </div>

                    {{-- Iframe wrapper --}}
                    <div
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-sm transition-all duration-200"
                        :style="viewportStyle"
                    >
                        <iframe
                            id="theme-preview-frame"
                            :src="previewUrl"
                            class="h-[600px] w-full origin-top-left bg-white transition-all duration-200"
                            :style="iframeStyle"
                            @load="onPreviewLoad()"
                            title="Theme live preview"
                            sandbox="allow-same-origin allow-scripts allow-forms"
                        ></iframe>
                    </div>

                    <p class="mt-2 text-xs text-slate-400">
                        Changes reflect in the preview as you type. Click <strong>Save</strong> to persist.
                    </p>
                </div>
            </div>

        </div>

    @endif

</div>

@push('scripts')
<script>
function themeCustomizer(initialTokens, initialUrl) {
    return {
        tokens: initialTokens,
        previewUrl: initialUrl,
        previewReady: false,
        viewport: 'desktop',

        /* Viewport dimensions for the container and iframe scaling. */
        viewportSizes: {
            desktop: { width: null,  scale: 1 },
            tablet:  { width: 768,   scale: 0.75 },
            mobile:  { width: 390,   scale: 0.65 },
        },

        get viewportStyle() {
            const { width, scale } = this.viewportSizes[this.viewport];
            if (!width) return '';
            /* Outer container is narrowed; iframe is scaled down inside. */
            return `max-width: ${Math.round(width * scale)}px; margin: 0 auto;`;
        },

        get iframeStyle() {
            const { width, scale } = this.viewportSizes[this.viewport];
            if (!width) return 'transform: none; width: 100%;';
            return `width: ${width}px; transform: scale(${scale}); transform-origin: top left; height: ${Math.round(600 / scale)}px;`;
        },

        init() {
            /* Deep-watch all token values and push changes to the iframe. */
            this.$watch('tokens', () => this.pushTokensToPreview(), { deep: true });
        },

        onPreviewLoad() {
            this.previewReady = true;
            this.pushTokensToPreview();
        },

        pushTokensToPreview() {
            const frame = document.getElementById('theme-preview-frame');
            if (!frame || !frame.contentDocument) return;

            try {
                const root = frame.contentDocument.documentElement;
                Object.entries(this.tokens).forEach(([key, value]) => {
                    if (value) root.style.setProperty(key, value);
                });
            } catch (_) {
                /* Cross-origin guard — no-op if preview URL is external. */
            }
        },

        reloadPreview() {
            this.previewReady = false;
            const frame = document.getElementById('theme-preview-frame');
            if (frame) frame.src = this.previewUrl;
        },

        setViewport(size) {
            this.viewport = size;
        },
    };
}
</script>
@endpush

@endsection
