{{--
    Phase 7 (B10) — hreflang alternates + x-default.

    Emits one <link rel="alternate"> per locale for which a real translation URL is
    provided, plus an x-default that points at the default-locale URL. The caller
    provides `$localeAlternates` (map of locale => URL) — for pages/entries this is
    prepared by the frontend controllers; other routes get a generic path swap.
--}}
@php
    use App\Support\Locales;
    $active = Locales::active();
    if (count($active) < 2) {
        return; // hreflang has no meaning with only one locale
    }
    $default = Locales::default();
    // Fall back to a generic path swap when the caller didn't provide explicit
    // alternates (chrome pages like /, /products, /blog — where every locale
    // serves the same route, just under its own prefix).
    $alternates = $localeAlternates ?? collect(Locales::activeCodes())
        ->mapWithKeys(fn (string $code) => [$code => Locales::localizedUrl($code)])
        ->all();
@endphp

@foreach($active as $code => $meta)
    @php $url = $alternates[$code] ?? null; @endphp
    @if($url !== null)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $url }}">
    @endif
@endforeach
@if(isset($alternates[$default]))
    <link rel="alternate" hreflang="x-default" href="{{ $alternates[$default] }}">
@endif
