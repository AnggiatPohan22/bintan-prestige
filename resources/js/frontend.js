const headerSelector = '[data-frontend-header]';
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
 * Bootstraps all custom frontend interactions.
 * Add future lightweight homepage, menu, or shared public-site behavior here.
 */
export function initFrontend() {
    initFrontendHeader();
}
