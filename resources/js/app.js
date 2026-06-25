

import Alpine from 'alpinejs';
import sort from '@alpinejs/sort';
import { initFrontend } from './frontend';

Alpine.plugin(sort);
window.Alpine = Alpine;

// Per-user admin UI mode toggle (dark <-> light).
// Updates <html data-admin-mode> instantly + persists via POST.
Alpine.data('adminUiModeToggle', (initial) => ({
    // Anything other than explicit 'light' (including legacy 'auto') starts as dark.
    mode: initial === 'light' ? 'light' : 'dark',

    init() {
        this.applyDom();
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
        this.applyDom();
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
