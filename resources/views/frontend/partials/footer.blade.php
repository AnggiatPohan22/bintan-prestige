@php
    $footerSettings = $footerSettings ?? \App\Support\FooterSettings::valuesFromSettings(collect());
    $bookingCtaSettings = $bookingCtaSettings ?? \App\Support\BookingCtaSettings::valuesFromSettings(collect());
    $usesGlobalFooterCta = \App\Support\BookingCtaSettings::isEnabledFor($bookingCtaSettings, 'footer');
    $footerWhatsappUrl = $usesGlobalFooterCta
        ? \App\Support\BookingCtaSettings::whatsappUrl($bookingCtaSettings, [
            'site_name' => $businessIdentity['brand_name'] ?? config('app.name'),
            'page_url' => url()->current(),
        ], $contactInformation ?? [])
        : ($contactWhatsappUrl ?? 'https://wa.me/?text=' . urlencode('Hello Bintan Prestige, I want to plan a Bintan trip.'));
    $footerCtaLabel = $usesGlobalFooterCta ? ($bookingCtaSettings['footer_label'] ?? 'Chat via WhatsApp') : 'Chat via WhatsApp';
    $footerCtaSection = collect($sections ?? [])->get('home.footer_cta');
    $footerCtaVisual = $footerCtaSection?->mediaSlot('frame', 'main_visual');
    $footerCtaPlaceholder = \App\Support\DefaultMediaAssets::asset($siteAssets ?? collect(), 'section');
    $footerCtaPlaceholderFit = \App\Support\DefaultMediaAssets::fit($defaultMediaSettings ?? [], 'section');
    $footerCtaButtonLabel = filled($footerCtaSection?->button_text) ? $footerCtaSection->button_text : $footerCtaLabel;
    $footerCtaButtonUrl = \App\Support\PageSectionCta::safeUrl($footerCtaSection?->button_url, $footerWhatsappUrl);
@endphp

<footer class="bp-footer" aria-labelledby="footer-title">
    @if($footerSettings['show_cta'] ?? true)
    <section class="bp-footer-cta" id="whatsapp-cta" data-section-key="home.footer_cta" aria-labelledby="footer-cta-title">
        <div class="bp-footer-cta__content">
            <span class="bp-footer-cta__label">
                {{ $footerCtaSection?->label ?? 'Explore Tour' }}
            </span>

            <h2 id="footer-cta-title" class="bp-footer-cta__title title-section">
                {{ $footerCtaSection?->title ?? 'Plan Your Perfect Bintan Escape With Us' }}
            </h2>

            <p class="bp-footer-cta__text text-body">
                {{ $footerCtaSection?->description ?? 'Tell us your arrival point, travel date, and preferred experience. Our team will help you choose the right package.' }}
            </p>

            <a
                href="{{ $footerCtaButtonUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-whatsapp bp-footer-cta__button"
                data-whatsapp-tracking="footer"
                data-tracking-label="{{ $footerCtaButtonLabel }}"
            >
                <span>{{ $footerCtaButtonLabel }}</span>
                <svg class="bp-footer-cta__button-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M7 17L17 7"></path>
                    <path d="M9 7h8v8"></path>
                </svg>
            </a>
        </div>

        <div class="bp-footer-cta__visual" aria-hidden="true">
            @if($footerCtaVisual?->url || $footerCtaPlaceholder?->url)
                <img src="{{ $footerCtaVisual?->url ?: $footerCtaPlaceholder->url }}" alt="{{ $footerCtaVisual?->alt ?: ($footerCtaPlaceholder?->alt ?: 'Section placeholder image') }}" style="{{ $footerCtaVisual?->image_style ?? 'object-fit: ' . $footerCtaPlaceholderFit }}" loading="lazy" decoding="async">
            @else
                <div class="bp-footer-cta__placeholder">
                    NO IMAGE
                </div>
            @endif
        </div>
    </section>
    @endif

    <div class="bp-footer__body">
        <div class="bp-footer__inner">
            <div class="bp-footer__brand">
                @php
                    $footerLogoKey = \App\Support\FooterSettings::logoAssetKey($footerSettings['logo_source'] ?? 'light');
                    $footerLogo = $footerLogoKey && isset($siteAssets) ? (($siteAssets[$footerLogoKey] ?? null) ?: ($siteAssets['site.logo'] ?? null)) : null;
                    $brandName = $businessIdentity['brand_name'] ?? 'Bintan Prestige';
                    $brandInitials = collect(explode(' ', $brandName))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('');
                    $shortDescription = $businessIdentity['short_description'] ?? 'Luxury Bintan tours, private transfers, and curated island experiences arranged with comfort, quality, and simple WhatsApp booking.';
                    $locationLabel = $contactInformation['address'] ?? ($businessIdentity['location_label'] ?? 'Bintan Island, Indonesia');
                    $openingHours = $contactInformation['opening_hours'] ?? 'Open Daily';
                    $email = $contactInformation['email'] ?? '';
                    $phone = $contactInformation['phone'] ?? '';
                    $mapsUrl = $contactInformation['google_maps_url'] ?? '';
                    $copyrightText = $businessIdentity['copyright_text'] ?? 'All rights reserved.';
                    $footerBottomNote = $footerSettings['bottom_note'] ?? 'Designed for premium island travel.';
                    $layoutBlocks = collect($footerSettings['layout_blocks'] ?? [])->filter(fn ($block) => $block['is_active'] ?? false);
                @endphp

                <a href="{{ route('home') }}" class="bp-footer__brand-link" aria-label="Bintan Prestige home">
                    <span class="bp-footer__brand-mark">
                        @if($footerLogo?->url)
                            <img src="{{ $footerLogo->url }}" alt="{{ $footerLogo->alt ?: $brandName . ' logo' }}">
                        @else
                            {{ $brandInitials ?: 'BP' }}
                        @endif
                    </span>

                    <span id="footer-title">
                        {{ $brandName }}
                    </span>
                </a>

                <p class="bp-footer__text">
                    {{ $shortDescription }}
                </p>

                @if($footerSettings['show_newsletter'] ?? true)
                    <form class="bp-footer__newsletter" action="#" method="GET">
                    <label for="footer-newsletter" class="sr-only">
                        Email address
                    </label>

                    <input
                        id="footer-newsletter"
                        type="email"
                        name="email"
                        placeholder="Your email"
                    >

                    <button type="submit" class="btn btn-primary btn-sm">
                        Join
                    </button>
                    </form>
                @endif

                @if(($footerSettings['show_social_links'] ?? true) && ! empty($activeSocialMediaLinks))
                    <div class="bp-footer__socials" aria-label="Social links">
                        @foreach($activeSocialMediaLinks as $socialLink)
                            <a href="{{ $socialLink['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $socialLink['label'] }}">
                                <span>{{ $socialLink['abbr'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bp-footer__columns">
                @foreach($layoutBlocks as $block)
                    @php
                        $blockWidthClass = match ($block['width'] ?? '1') {
                            '2' => 'bp-footer__column--span-2',
                            'full' => 'bp-footer__column--full',
                            default => '',
                        };
                        $blockType = $block['type'] ?? 'quick_links';
                    @endphp

                    @continue($blockType === 'contact_info' && ! ($footerSettings['show_contact_column'] ?? true))

                    <div class="bp-footer__column {{ $blockWidthClass }}">
                        <h3>{{ $block['title'] }}</h3>

                        @if($blockType === 'quick_links')
                            @foreach((! empty($footerQuickLinks) ? $footerQuickLinks : ($footerSettings['quick_links'] ?? [])) as $link)
                                @php
                                    $linkUrl = \App\Support\FooterSettings::resolveUrl($link['url'] ?? '#');
                                    $isExternal = (bool) ($link['is_external'] ?? false);
                                @endphp
                                <a href="{{ $linkUrl }}" @if($isExternal) target="_blank" rel="noopener noreferrer" @endif>{{ $link['label'] }}</a>
                            @endforeach
                        @elseif($blockType === 'contact_info')
                            @if($mapsUrl)
                                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer">{{ $locationLabel }}</a>
                            @else
                                <span>{{ $locationLabel }}</span>
                            @endif
                            @if($email)
                                <a href="mailto:{{ $email }}">{{ $email }}</a>
                            @endif
                            @if($phone)
                                <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a>
                            @endif
                            <a href="{{ $footerWhatsappUrl }}" target="_blank" rel="noopener noreferrer" data-whatsapp-tracking="footer" data-tracking-label="WhatsApp Contact">
                                WhatsApp Contact
                            </a>
                            <span>{{ $openingHours }}</span>
                        @elseif($blockType === 'utility_links')
                            @foreach((! empty($footerUtilityLinks) ? $footerUtilityLinks : ($footerSettings['utility_links'] ?? [])) as $link)
                                @php
                                    $linkUrl = \App\Support\FooterSettings::resolveUrl($link['url'] ?? '#');
                                    $isExternal = (bool) ($link['is_external'] ?? false);
                                @endphp
                                <a href="{{ $linkUrl }}" @if($isExternal) target="_blank" rel="noopener noreferrer" @endif>{{ $link['label'] }}</a>
                            @endforeach
                        @elseif($blockType === 'maps')
                            @php
                                $embedUrl = $block['settings']['maps_embed_url'] ?: $mapsUrl;
                            @endphp
                            @if($embedUrl)
                                <iframe class="bp-footer__map" src="{{ $embedUrl }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="{{ $block['title'] }}"></iframe>
                            @else
                                <span>Maps embed URL is not configured.</span>
                            @endif
                        @elseif($blockType === 'custom_text')
                            <p class="bp-footer__custom-text">{{ $block['settings']['custom_body'] ?: 'Custom footer text is not configured.' }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bp-footer__bottom">
            <span>&copy; 2026 {{ $brandName }}. {{ $copyrightText }}</span>
            <span>{{ $footerBottomNote }}</span>
        </div>
    </div>
</footer>
