@php
    $data = $block->data ?? [];
    $heading = trim((string) ($data['heading'] ?? ''));
    $intro = trim((string) ($data['intro'] ?? ''));
    $plans = collect($data['plans'] ?? [])->filter(fn ($plan) => ! empty($plan['name']) || ! empty($plan['price']));
    $bg = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) { $bgStyle .= 'background-color:' . e($bg['color']) . ';'; }
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if($heading !== '' || $intro !== '' || $plans->isNotEmpty())
<section class="px-6 py-12 sm:py-16" style="{{ $bgStyle }}" @if($heading !== '') aria-labelledby="pricing-heading-{{ $block->id }}" @else aria-label="Pricing plans" @endif>
    <div class="mx-auto max-w-7xl">
        @if($heading !== '')<h2 id="pricing-heading-{{ $block->id }}" class="text-center text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $heading }}</h2>@endif
        @if($intro !== '')<p class="mx-auto mt-4 max-w-3xl text-center text-lg text-slate-600">{{ $intro }}</p>@endif
        @if($plans->isNotEmpty())
            <div class="mt-10 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($plans as $plan)
                    @php $featured = filter_var($plan['featured'] ?? false, FILTER_VALIDATE_BOOLEAN); @endphp
                    <article class="flex flex-col rounded-2xl border bg-white p-6 shadow-sm {{ $featured ? 'border-[var(--frontend-gold,#D4AF37)] ring-2 ring-[var(--frontend-gold,#D4AF37)]' : 'border-slate-200' }}">
                        @if($featured)<p class="mb-3 text-xs font-bold uppercase tracking-widest text-[var(--frontend-gold,#B28B22)]">Featured</p>@endif
                        @if(! empty($plan['name']))<h3 class="text-2xl font-bold text-slate-900">{{ $plan['name'] }}</h3>@endif
                        @if(! empty($plan['price']))
                            <p class="mt-4 text-slate-900"><span class="text-sm font-semibold">{{ $plan['currency'] ?? '' }}</span> <span class="text-4xl font-bold">{{ $plan['price'] }}</span> @if(! empty($plan['period']))<span class="text-sm text-slate-500">/ {{ $plan['period'] }}</span>@endif</p>
                        @endif
                        @php $features = array_filter($plan['features'] ?? [], fn ($feature) => filled($feature)); @endphp
                        @if($features)
                            <ul class="mt-6 flex-1 space-y-3 text-sm text-slate-700">
                                @foreach($features as $feature)<li class="flex gap-2"><span class="text-[var(--frontend-gold,#B28B22)]" aria-hidden="true">✓</span><span>{{ $feature }}</span></li>@endforeach
                            </ul>
                        @endif
                        @if(! empty($plan['button_text']) && ! empty($plan['button_url']))
                            <a href="{{ $plan['button_url'] }}" class="mt-8 inline-flex min-h-11 items-center justify-center rounded-full bg-[var(--frontend-gold,#D4AF37)] px-6 py-3 text-sm font-bold text-[var(--frontend-black,#0f0f0f)] transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-[var(--frontend-gold,#D4AF37)] focus:ring-offset-2">{{ $plan['button_text'] }}</a>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endif
