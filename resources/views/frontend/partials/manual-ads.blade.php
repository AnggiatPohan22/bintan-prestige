@php
    $section = $sections['home.manual_ads'] ?? null;
    $extraData = $section?->extra_data ?? [];
@endphp

<section id="home-manual-ads" class="bp-manual-ads" data-section-key="home.manual_ads" aria-labelledby="manual-ads-title">
    <div class="home-container">
        <div class="bp-manual-ads__inner">
            <span class="bp-manual-ads__decor" aria-hidden="true"></span>
            <div class="bp-manual-ads__content">
                <span class="bp-manual-ads__label">{{ $section->label ?? 'Special Offer' }}</span>
                <h2 id="manual-ads-title" class="bp-manual-ads__title title-section">{{ $section->title ?? 'Plan Your Bintan Journey With Us' }}</h2>
                <a href="{{ $section->button_url ?? route('products.index') }}" class="btn btn-secondary bp-manual-ads__button">
                    {{ $section->button_text ?? 'SEE DETAILS' }}
                    <svg class="bp-manual-ads__button-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17L17 7"></path><path d="M9 7h8v8"></path></svg>
                </a>
            </div>
            <div class="bp-manual-ads__visual">
                @if($section?->image_url)
                    <img src="{{ $section->image_url }}" alt="{{ $section->title ?? 'Bintan Prestige promotion' }}" loading="lazy" decoding="async">
                @else
                    <div class="bp-manual-ads__placeholder">NO IMAGE</div>
                @endif
                <div class="bp-manual-ads__overlay-title">{{ $extraData['overlay_title'] ?? "Let's Discover The Whole World!" }}</div>
            </div>
        </div>
    </div>
</section>
