

import Alpine from 'alpinejs';
import sort from '@alpinejs/sort';
import { initFrontend } from './frontend';

Alpine.plugin(sort);
window.Alpine = Alpine;

// Per-user admin UI mode toggle (auto -> dark -> light -> auto).
// Updates <html data-admin-mode> instantly + persists via POST.
Alpine.data('adminUiModeToggle', (initial) => ({
    mode: initial || 'auto',

    init() {
        this.applyDom();
    },

    get iconClass() {
        if (this.mode === 'dark')  return 'fa-moon';
        if (this.mode === 'light') return 'fa-sun';
        return 'fa-circle-half-stroke';
    },

    get label() {
        if (this.mode === 'dark')  return 'Theme: Dark (click for Light)';
        if (this.mode === 'light') return 'Theme: Light (click for Auto)';
        return 'Theme: Auto (click for Dark)';
    },

    cycle() {
        const next = this.mode === 'auto' ? 'dark' : this.mode === 'dark' ? 'light' : 'auto';
        this.mode = next;
        this.applyDom();
        this.persist(next);
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
