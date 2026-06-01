<header
    class="frontend-header"
    data-frontend-header
>
    <div class="frontend-header__inner">
        <a href="{{ route('home') }}"
           class="frontend-brand"
           aria-label="Bintan Prestige home">
            <span class="frontend-brand__mark">BP</span>
            <span>Bintan Prestige</span>
        </a>

        <nav class="frontend-nav" aria-label="Main navigation">
            <a href="{{ route('home') }}"
               class="frontend-nav__link {{ request()->routeIs('home') ? 'frontend-nav__link--active' : '' }}">
                Home
            </a>

            <a href="{{ route('products.index') }}"
               class="frontend-nav__link {{ request()->routeIs('products.*') ? 'frontend-nav__link--active' : '' }}">
                Packages
            </a>

            <a href="{{ route('home') }}#destinations"
               class="frontend-nav__link">
                Destinations
            </a>

            <a href="{{ route('home') }}#whatsapp-cta"
               class="frontend-nav__link">
                Contact
            </a>
        </nav>

        <div class="frontend-header__actions">
            <a href="{{ route('home') }}#whatsapp-cta" class="frontend-header__cta">
                Plan Trip
            </a>

            <a
                href="{{ route('home') }}#whatsapp-cta"
                class="frontend-header__icon"
                aria-label="Plan trip"
            >
                <svg
                    class="frontend-header__icon-svg"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path d="M7 17L17 7"></path>
                    <path d="M9 7h8v8"></path>
                </svg>
            </a>
        </div>
    </div>
</header>
