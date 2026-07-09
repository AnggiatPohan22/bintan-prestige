@extends('layouts.admin')

@section('content')

<div class="min-w-0 rounded-xl bg-admin-card p-6">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-admin-secondary">Global Text Translations</h1>
            <p class="mt-1 text-sm text-admin-secondary">
                Translate the site chrome (navigation, footer, CTA, identity, SEO defaults) per language.
                The default language ({{ strtoupper($defaultLocale) }}) is edited in the normal Global Assets forms; anything left blank here falls back to it.
            </p>
        </div>
        <a href="{{ route('admin.settings.global-assets.edit') }}"
           class="shrink-0 rounded-lg border border-admin bg-admin-card px-4 py-2 text-sm font-semibold text-admin-secondary transition hover:opacity-75">
            ← Back to Global Assets
        </a>
    </div>

    @if(session('success'))
        <div class="mb-5 rounded-lg border border-emerald-500/40 bg-emerald-900/20 px-4 py-3 text-sm font-semibold text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 rounded-lg border border-rose-500/40 bg-rose-900/20 px-4 py-3 text-sm font-semibold text-rose-300">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Locale selector --}}
    <div class="mb-6 max-w-full overflow-x-auto border-b border-admin pb-4">
        <div class="flex min-w-max gap-2 whitespace-nowrap">
            @foreach($targetLocales as $code)
                <a href="{{ route('admin.settings.global-assets.translations', ['locale' => $code]) }}"
                   class="shrink-0 rounded-lg border px-4 py-3 text-sm font-semibold transition {{ $locale === $code ? 'border-violet-500 bg-violet-900/20 text-violet-300' : 'border-admin bg-admin-card text-admin-secondary hover:opacity-75' }}">
                    {{ \App\Support\Locales::label($code) ?? strtoupper($code) }} ({{ strtoupper($code) }})
                </a>
            @endforeach
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.global-assets.translations.update') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="locale" value="{{ $locale }}">

        <div class="grid min-w-0 grid-cols-1 gap-6">
            @foreach($sections as $sectionLabel => $fields)
                <div class="rounded-xl border border-admin bg-admin-card p-5">
                    <h2 class="text-lg font-bold text-admin-secondary">{{ $sectionLabel }}</h2>

                    <div class="mt-4 grid grid-cols-1 gap-5">
                        @foreach($fields as $field)
                            @php
                                $setting = $settings->get($field['key']);
                                $baseValue = $setting?->value ?? '';
                                $translated = $setting?->rawTranslation('value', $locale) ?? '';
                                $inputName = 'translations[' . $field['key'] . ']';
                            @endphp

                            <div>
                                <label class="form-label" for="tr-{{ $loop->parent->index }}-{{ $loop->index }}">
                                    {{ $field['label'] }}
                                    <span class="ml-1 text-xs font-normal text-admin-secondary opacity-70">({{ strtoupper($defaultLocale) }} → {{ strtoupper($locale) }})</span>
                                </label>

                                {{-- Default-locale reference (read-only) --}}
                                <p class="mb-2 rounded-lg border border-admin bg-admin-card/60 px-3 py-2 text-sm text-admin-secondary opacity-80">
                                    {{ $baseValue !== '' ? $baseValue : '— (empty)' }}
                                </p>

                                @if($field['type'] === 'textarea')
                                    <textarea
                                        id="tr-{{ $loop->parent->index }}-{{ $loop->index }}"
                                        name="{{ $inputName }}"
                                        rows="3"
                                        class="admin-input"
                                        placeholder="{{ strtoupper($locale) }} translation…"
                                    >{{ old('translations.' . $field['key'], $translated) }}</textarea>
                                @else
                                    <input
                                        type="text"
                                        id="tr-{{ $loop->parent->index }}-{{ $loop->index }}"
                                        name="{{ $inputName }}"
                                        value="{{ old('translations.' . $field['key'], $translated) }}"
                                        class="admin-input"
                                        placeholder="{{ strtoupper($locale) }} translation…"
                                    >
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="admin-btn-primary">Save {{ strtoupper($locale) }} Translations</button>
        </div>
    </form>
</div>

@endsection
