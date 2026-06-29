

import Alpine from 'alpinejs';
import sort from '@alpinejs/sort';
import { initFrontend } from './frontend';

Alpine.plugin(sort);
window.Alpine = Alpine;

// Per-user admin UI mode toggle (dark <-> light).
// Updates <html data-admin-mode> instantly + persists via POST.
//
// When the Customizer v2 page is active (window._customizerActive = true),
// this component defers all DOM manipulation to the customizer so the two
// don't fight over data-admin-mode / inline CSS vars.  The customizer sets
// the flag via an inline <script> that runs before Alpine starts.
Alpine.data('adminUiModeToggle', (initial) => ({
    mode: initial === 'light' ? 'light' : 'dark',

    init() {
        // Customizer owns data-admin-mode on that page — skip here.
        if (!window._customizerActive) {
            this.applyDom();
        }
    },

    get iconClass() {
        return this.mode === 'light' ? 'fa-sun' : 'fa-moon';
    },

    get label() {
        return this.mode === 'light'
            ? 'Theme: Light (click for Night)'
            : 'Theme: Night (click for Light)';
    },

    toggle() {
        this.mode = this.mode === 'light' ? 'dark' : 'light';

        if (window._customizerActive) {
            // Delegate DOM change to customizer so it can apply inline vars
            // for the correct mode and avoid stale-var conflicts.
            window.dispatchEvent(
                new CustomEvent('customizer:set-mode', { detail: this.mode })
            );
        } else {
            this.applyDom();
        }

        this.persist(this.mode);
    },

    applyDom() {
        if (this.mode === 'light') {
            document.documentElement.setAttribute('data-admin-mode', 'light');
        } else {
            document.documentElement.removeAttribute('data-admin-mode');
        }
    },

    async persist(mode) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        try {
            await fetch('/admin/settings/ui-mode', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ ui_mode: mode }),
            });
        } catch (_) { /* UI already updated optimistically */ }
    },
}));

Alpine.start();
initFrontend();
