<footer class="frontend-footer">
    <div class="frontend-footer__inner">
        <div class="frontend-footer__brand">
            <a href="{{ route('home') }}" class="frontend-brand">
                <span class="frontend-brand__mark">BP</span>
                <span>Bintan Prestige</span>
            </a>

            <p class="frontend-footer__text">
                Luxury Bintan tours, private transfers, and curated island experiences with simple WhatsApp booking.
            </p>
        </div>

        <div class="frontend-footer__grid">
            <div>
                <h3 class="frontend-footer__title">Explore</h3>
                <a href="{{ route('products.index') }}" class="frontend-footer__link">Packages</a>
                <a href="{{ route('home') }}#destinations" class="frontend-footer__link">Destinations</a>
                <a href="{{ route('home') }}#categories" class="frontend-footer__link">Categories</a>
            </div>

            <div>
                <h3 class="frontend-footer__title">Support</h3>
                <a href="{{ route('home') }}#why-choose-us" class="frontend-footer__link">Why Choose Us</a>
                <a href="{{ route('home') }}#faq-preview" class="frontend-footer__link">Travel Notes</a>
                <a href="{{ route('home') }}#whatsapp-cta" class="frontend-footer__link">WhatsApp</a>
            </div>
        </div>
    </div>

    <div class="frontend-footer__bottom">
        <span>© {{ date('Y') }} Bintan Prestige. All rights reserved.</span>
        <span>Designed for premium island travel.</span>
    </div>
</footer>
