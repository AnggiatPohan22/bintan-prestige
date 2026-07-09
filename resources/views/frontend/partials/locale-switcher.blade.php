@php
    use App\Support\Locales;
    $activeLocales = Locales::active();
    $currentLocale = Locales::current();
    // Layout variant: 'inline' (desktop header) or 'stacked' (mobile panel).
    $variant = $variant ?? 'inline';
    // When a page provides per-locale counterpart URLs (B4), use them and hide any
    // locale without a published translation. Otherwise fall back to a path swap.
    $alternates = $localeAlternates ?? null;
@endphp

@if(count($activeLocales) > 1)
    <div
        class="frontend-locale-switcher frontend-locale-switcher--{{ $variant }}"
        role="group"
        aria-label="{{ __('frontend.language') }}"
        data-locale-switcher
    >
        @foreach($activeLocales as $code => $meta)
            @php
                $isCurrent = $currentLocale === $code;
                $url = $alternates !== null ? ($alternates[$code] ?? null) : Locales::localizedUrl($code);
            @endphp
            @if($url !== null)
                <a
                    href="{{ $url }}"
                    class="frontend-nav__link frontend-locale-switcher__link {{ $isCurrent ? 'frontend-nav__link--active' : '' }}"
                    hreflang="{{ $code }}"
                    lang="{{ $code }}"
                    title="{{ $meta['native'] ?? $meta['label'] ?? strtoupper($code) }}"
                    @if($isCurrent) aria-current="true" @endif
                >
                    {{ strtoupper($code) }}
                </a>
            @endif
        @endforeach
    </div>
@endif
