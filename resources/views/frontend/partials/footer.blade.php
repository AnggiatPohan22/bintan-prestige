<footer class="bp-footer" aria-labelledby="footer-title">
    <section class="bp-footer-cta" id="whatsapp-cta" aria-labelledby="footer-cta-title">
        <div class="bp-footer-cta__content">
            <span class="bp-footer-cta__label">
                Explore Tour
            </span>

            <h2 id="footer-cta-title" class="bp-footer-cta__title title-section">
                Plan Your Perfect Bintan Escape With Us
            </h2>

            <p class="bp-footer-cta__text text-body">
                Tell us your arrival point, travel date, and preferred experience. Our team will help you choose the right package.
            </p>

            <a
                href="https://wa.me/?text={{ urlencode('Hello Bintan Prestige, I want to plan a Bintan trip.') }}"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-whatsapp bp-footer-cta__button"
            >
                <span>Chat via WhatsApp</span>
                <svg class="bp-footer-cta__button-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M7 17L17 7"></path>
                    <path d="M9 7h8v8"></path>
                </svg>
            </a>
        </div>

        <div class="bp-footer-cta__visual" aria-hidden="true">
            <div class="bp-footer-cta__placeholder">
                NO IMAGE
            </div>
        </div>
    </section>

    <div class="bp-footer__body">
        <div class="bp-footer__inner">
            <div class="bp-footer__brand">
                @php
                    $footerLogo = isset($siteAssets) ? ($siteAssets['site.logo'] ?? null) : null;
                @endphp

                <a href="{{ route('home') }}" class="bp-footer__brand-link" aria-label="Bintan Prestige home">
                    <span class="bp-footer__brand-mark">
                        @if($footerLogo?->url)
                            <img src="{{ $footerLogo->url }}" alt="{{ $footerLogo->alt ?: 'Bintan Prestige logo' }}">
                        @else
                            BP
                        @endif
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
