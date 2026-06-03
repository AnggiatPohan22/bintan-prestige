const headerSelector = '[data-frontend-header]';
const heroBackgroundSelector = '[data-hero-background]';
const sectionSliderSelector = '[data-section-slider]';
const sectionSlideSelector = '[data-section-slide]';
const packageCarouselSelector = '[data-package-carousel]';
const productGridSelector = '[data-product-grid]';
const scrolledClass = 'frontend-header--scrolled';
const scrollThreshold = 24;

/**
 * Applies the scrolled header class based on the current page scroll position.
 * Used by the shared frontend header to switch from transparent mode to the
 * soft-gold blurred background after the visitor scrolls down.
 */
function syncHeaderState(header) {
    header.classList.toggle(scrolledClass, window.scrollY > scrollThreshold);
}

/**
 * Initializes the global frontend header scroll behavior.
 * Applied to every header that has the `data-frontend-header` attribute.
 * The scroll listener is throttled with `requestAnimationFrame` to keep it light.
 */
export function initFrontendHeader() {
    const headers = document.querySelectorAll(headerSelector);

    if (!headers.length) {
        return;
    }

    let ticking = false;

    headers.forEach(syncHeaderState);

    window.addEventListener(
        'scroll',
        () => {
            if (ticking) {
                return;
            }

            ticking = true;

            window.requestAnimationFrame(() => {
                headers.forEach(syncHeaderState);
                ticking = false;
            });
        },
        { passive: true }
    );
}

/**
 * Applies backend-provided hero background images without inline Blade styles.
 * Used on the homepage hero through the `data-hero-background` attribute, then
 * forwarded to the CSS variable consumed by `frontend-home.css`.
 */
export function initDynamicHeroBackground() {
    const heroSections = document.querySelectorAll(heroBackgroundSelector);

    heroSections.forEach((section) => {
        const backgroundUrl = section.dataset.heroBackground;

        if (!backgroundUrl) {
            return;
        }

        section.style.setProperty('--home-hero-image', `url("${backgroundUrl}")`);
    });
}

/**
 * Initializes reusable section image sliders.
 * Any future page section can opt in by rendering `[data-section-slider]`
 * with child slides marked as `[data-section-slide]`.
 */
export function initSectionSliders() {
    document.querySelectorAll(sectionSliderSelector).forEach((section) => {
        const slides = Array.from(section.querySelectorAll(sectionSlideSelector));

        if (slides.length < 2) {
            return;
        }

        const interval = Number.parseInt(section.dataset.sectionSliderInterval || '6500', 10);
        let activeIndex = slides.findIndex((slide) => slide.classList.contains('is-active'));

        if (activeIndex < 0) {
            activeIndex = 0;
            slides[activeIndex].classList.add('is-active');
        }

        window.setInterval(() => {
            slides[activeIndex].classList.remove('is-active');
            activeIndex = (activeIndex + 1) % slides.length;
            slides[activeIndex].classList.add('is-active');
        }, interval);
    });
}

/**
 * Returns the dynamic scroll distance for a carousel.
 * The value is based on the first visible card width plus the computed track gap,
 * so desktop, tablet, and mobile all move exactly one card per arrow click.
 */
function getCarouselScrollAmount(track) {
    const firstCard = track.firstElementChild;

    if (!firstCard) {
        return track.clientWidth;
    }

    const trackStyle = window.getComputedStyle(track);
    const gap = parseFloat(trackStyle.columnGap || trackStyle.gap || '0');

    return firstCard.getBoundingClientRect().width + gap;
}

/**
 * Updates arrow disabled states based on the carousel scroll position.
 * This keeps keyboard and screen-reader users from activating unavailable moves.
 */
function syncCarouselArrowState(track, previousButton, nextButton) {
    const maxScrollLeft = track.scrollWidth - track.clientWidth;
    const currentScrollLeft = track.scrollLeft;
    const tolerance = 2;

    if (previousButton) {
        previousButton.disabled = currentScrollLeft <= tolerance;
    }

    if (nextButton) {
        nextButton.disabled = currentScrollLeft >= maxScrollLeft - tolerance;
    }
}

/**
 * Initializes one responsive package carousel.
 * It preserves native touch swipe while adding lightweight arrow navigation.
 */
function initPackageCarousel(carousel) {
    const track = carousel.querySelector('[data-carousel-track]');
    const header = carousel.parentElement?.querySelector('.home-section__header--carousel');
    const previousButton = header?.querySelector('[data-carousel-prev]');
    const nextButton = header?.querySelector('[data-carousel-next]');

    if (!track) {
        return;
    }

    const scrollToDirection = (direction) => {
        track.scrollBy({
            left: getCarouselScrollAmount(track) * direction,
            behavior: 'smooth',
        });
    };

    previousButton?.addEventListener('click', () => scrollToDirection(-1));
    nextButton?.addEventListener('click', () => scrollToDirection(1));

    let ticking = false;
    const requestSync = () => {
        if (ticking) {
            return;
        }

        ticking = true;

        window.requestAnimationFrame(() => {
            syncCarouselArrowState(track, previousButton, nextButton);
            ticking = false;
        });
    };

    track.addEventListener('scroll', requestSync, { passive: true });
    window.addEventListener('resize', requestSync);
    syncCarouselArrowState(track, previousButton, nextButton);
}

/**
 * Initializes every package carousel on the public frontend.
 * Multiple sections can reuse the same data attributes and CSS classes.
 */
export function initPackageCarousels() {
    document.querySelectorAll(packageCarouselSelector).forEach(initPackageCarousel);
}

/**
 * Initializes homepage product category filters.
 * Filtering and pagination are scoped to rendered homepage products, keeping
 * the interaction lightweight while letting each active category show 8 cards
 * per page before the visitor moves through the next set.
 */
export function initProductFilters() {
    document.querySelectorAll(productGridSelector).forEach((grid) => {
        const section = grid.closest('.bp-product-section');
        const buttons = Array.from(section?.querySelectorAll('[data-product-filter]') || []);
        const cards = Array.from(grid.querySelectorAll('[data-product-card]'));
        const pagination = section?.querySelector('[data-product-pagination]');
        const previousButton = section?.querySelector('[data-product-prev]');
        const nextButton = section?.querySelector('[data-product-next]');
        const pageStatus = section?.querySelector('[data-product-page-status]');
        const perPage = Number.parseInt(grid.dataset.productsPerPage || '8', 10);
        let activeFilter = 'all';
        let currentPage = 0;

        if (!section || !buttons.length || !cards.length || !perPage) {
            return;
        }

        const getMatchingCards = () => cards.filter((card) => {
            return activeFilter === 'all' || card.dataset.categoryId === activeFilter;
        });

        const renderProducts = () => {
            const matchingCards = getMatchingCards();
            const visibleSet = new Set(matchingCards);
            const totalPages = Math.max(1, Math.ceil(matchingCards.length / perPage));
            const pageStart = Math.min(currentPage, totalPages - 1) * perPage;
            const pageEnd = pageStart + perPage;

            currentPage = Math.min(currentPage, totalPages - 1);

            buttons.forEach((button) => {
                const isActive = button.dataset.productFilter === activeFilter;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            cards.forEach((card) => {
                const matchIndex = matchingCards.indexOf(card);
                const shouldShow = visibleSet.has(card)
                    && matchIndex >= pageStart
                    && matchIndex < pageEnd;

                card.hidden = !shouldShow;
            });

            if (pagination) {
                const hasMultiplePages = matchingCards.length > perPage;
                pagination.hidden = !hasMultiplePages;
            }

            if (previousButton) {
                previousButton.disabled = currentPage === 0;
            }

            if (nextButton) {
                nextButton.disabled = currentPage >= totalPages - 1;
            }

            if (pageStatus) {
                pageStatus.textContent = `${currentPage + 1} / ${totalPages}`;
            }
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                activeFilter = button.dataset.productFilter || 'all';
                currentPage = 0;
                renderProducts();
            });
        });

        previousButton?.addEventListener('click', () => {
            currentPage = Math.max(0, currentPage - 1);
            renderProducts();
        });

        nextButton?.addEventListener('click', () => {
            const totalPages = Math.max(1, Math.ceil(getMatchingCards().length / perPage));
            currentPage = Math.min(totalPages - 1, currentPage + 1);
            renderProducts();
        });

        renderProducts();
    });
}

/**
 * Bootstraps all custom frontend interactions.
 * Add future lightweight homepage, menu, or shared public-site behavior here.
 */
export function initFrontend() {
    initFrontendHeader();
    initDynamicHeroBackground();
    initSectionSliders();
    initPackageCarousels();
    initProductFilters();
}
