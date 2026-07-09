@php
    use App\Support\Locales;
    $activeLocales = Locales::active();
    $currentLocale = Locales::current();
    // Layout variant: 'inline' (desktop header) or 'stacked' (mobile panel).
    $variant = $variant ?? 'inline';
@endphp

@if(count($activeLocales) > 1)
    <div
        class="frontend-locale-switcher frontend-locale-switcher--{{ $variant }}"
        role="group"
        aria-label="{{ __('frontend.language') }}"
        data-locale-switcher
    >
        @foreach($activeLocales as $code => $meta)
            @php $isCurrent = $currentLocale === $code; @endphp
            <a
                href="{{ Locales::localizedUrl($code) }}"
                class="frontend-nav__link frontend-locale-switcher__link {{ $isCurrent ? 'frontend-nav__link--active' : '' }}"
                hreflang="{{ $code }}"
                lang="{{ $code }}"
                title="{{ $meta['native'] ?? $meta['label'] ?? strtoupper($code) }}"
                @if($isCurrent) aria-current="true" @endif
            >
                {{ strtoupper($code) }}
            </a>
        @endforeach
    </div>
@endif
