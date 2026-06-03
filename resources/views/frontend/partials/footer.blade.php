@php
    $footerCtaSection = $sections['home.footer_cta'] ?? null;
@endphp

<footer class="bp-footer" aria-labelledby="footer-title">
    <section id="home-footer-cta" class="bp-footer-cta" data-section-key="home.footer_cta" aria-labelledby="footer-cta-title">
        <span id="whatsapp-cta" class="sr-only" aria-hidden="true"></span>

        <div class="bp-footer-cta__content">
            <span class="bp-footer-cta__label">
                {{ $footerCtaSection->label ?? 'Explore Tour' }}
            </span>

            <h2 id="footer-cta-title" class="bp-footer-cta__title title-section">
                {{ $footerCtaSection->title ?? 'Plan Your Perfect Bintan Escape With Us' }}
            </h2>

            <p class="bp-footer-cta__text text-body">
                {{ $footerCtaSection->description ?? 'Tell us your arrival point, travel date, and preferred experience. Our team will help you choose the right package.' }}
            </p>

            <a
                href="{{ $footerCtaSection->button_url ?? 'https://wa.me/?text=' . urlencode('Hello Bintan Prestige, I want to plan a Bintan trip.') }}"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-whatsapp bp-footer-cta__button"
            >
                {{ $footerCtaSection->button_text ?? 'Chat via WhatsApp' }}
            </a>
        </div>

        <div class="bp-footer-cta__visual" aria-hidden="true">
            @if($footerCtaSection?->image_url)
                <img src="{{ $footerCtaSection->image_url }}" alt="" loading="lazy" decoding="async">
            @else
                <div class="bp-footer-cta__placeholder">
                    NO IMAGE
                </div>
            @endif
        </div>
    </section>

    <div class="bp-footer__body">
        <div class="bp-footer__inner">
            <div class="bp-footer__brand">
                <a href="{{ route('home') }}" class="bp-footer__brand-link" aria-label="Bintan Prestige home">
                    <span class="bp-footer__brand-mark">
                        BP
                    </span>

                    <span id="footer-title">
                        Bintan Prestige
                    </span>
                </a>

                <p class="bp-footer__text">
                    Luxury Bintan tours, private transfers, and curated island experiences arranged with comfort, quality, and simple WhatsApp booking.
                </p>

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

                <div class="bp-footer__socials" aria-label="Social links">
                    <a href="#" aria-label="Instagram">
                        <span>IG</span>
                    </a>

                    <a href="#" aria-label="Facebook">
                        <span>FB</span>
                    </a>

                    <a href="#" aria-label="TikTok">
                        <span>TT</span>
                    </a>
                </div>
            </div>

            <div class="bp-footer__columns">
                <div class="bp-footer__column">
                    <h3>Quick Links</h3>
                    <a href="{{ route('home') }}">Home</a>
                    <a href="{{ route('home') }}#why-choose-us">About Us</a>
                    <a href="{{ route('products.index') }}">Packages</a>
                    <a href="{{ route('products.index') }}">Destinations</a>
                    <a href="{{ route('home') }}#whatsapp-cta">Contact Us</a>
                </div>

                <div class="bp-footer__column">
                    <h3>Information</h3>
                    <span>Bintan Island, Indonesia</span>
                    <a href="https://wa.me/?text={{ urlencode('Hello Bintan Prestige, I want to ask about Bintan packages.') }}" target="_blank" rel="noopener noreferrer">
                        WhatsApp Contact
                    </a>
                    <span>Open Daily</span>
                </div>

                <div class="bp-footer__column">
                    <h3>Utility Pages</h3>
                    <a href="{{ route('home') }}#faq-preview">FAQs</a>
                    <a href="#">Blog</a>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms &amp; Conditions</a>
                    <a href="#">404 Page</a>
                </div>
            </div>
        </div>

        <div class="bp-footer__bottom">
            <span>&copy; 2026 Bintan Prestige. All rights reserved.</span>
            <span>Designed for premium island travel.</span>
        </div>
    </div>
</footer>
