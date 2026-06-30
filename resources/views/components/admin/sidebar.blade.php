@php
use Illuminate\Support\Facades\Request;

$activeGroup = match (true) {
    Request::routeIs('admin.pages.*', 'admin.page-blocks.*', 'admin.products.*', 'admin.categories.*', 'admin.destinations.*', 'admin.media.*', 'admin.faqs.*', 'admin.content-types.*') => 'content',
    Request::routeIs('admin.themes.*', 'admin.menus.*', 'admin.menu-items.*', 'admin.page-sections.*') => 'design',
    Request::routeIs('admin.forms.*', 'admin.form-submissions.*') => 'forms',
    Request::routeIs('admin.seo.*') => 'seo',
    Request::routeIs('admin.analytics.*') => 'analytics',
    Request::routeIs('admin.plugins.*', 'admin.audit-logs.*') => 'system',
    Request::routeIs('admin.settings.*') => 'settings',
    Request::routeIs('admin.users.*') => 'users',
    default => null,
};

$groups = [
    'content'   => 'content',
    'design'    => 'design',
    'forms'     => 'forms',
    'seo'       => 'seo',
    'analytics' => 'analytics',
    'system'    => 'system',
    'settings'  => 'settings',
    'users'     => 'users',
];
@endphp

<aside class="admin-sidebar-shell" x-data="adminSidebar('{{ $activeGroup }}')">

    <button
        type="button"
        class="admin-sidebar-toggle"
        aria-label="Open admin navigation"
        x-on:click="drawerOpen = true"
    >
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
    </button>

    <div
        class="admin-sidebar-backdrop"
        x-cloak
        x-show="drawerOpen"
        x-transition.opacity
        x-on:click="drawerOpen = false"
    ></div>

    <div
        class="admin-sidebar"
        x-bind:class="drawerOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
        {{-- Brand --}}
        <div class="admin-sidebar__brand">
            <div class="admin-sidebar__brand-mark">{{ $adminAppearance->brand_abbr ?? 'BP' }}</div>
            <div class="min-w-0">
                <h1 class="admin-sidebar__title">{{ $adminAppearance->brand_name ?? 'Travel Admin' }}</h1>
                <p class="admin-sidebar__subtitle">{{ $adminAppearance->brand_tagline ?? 'Bintan Prestige' }}</p>
            </div>
            <button
                type="button"
                class="admin-sidebar__close"
                aria-label="Close admin navigation"
                x-on:click="drawerOpen = false"
            >
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="admin-sidebar__nav" aria-label="Admin navigation">

            {{-- Dashboard (standalone) --}}
            <a href="{{ route('admin.dashboard') }}"
               class="group admin-sidebar__link {{ Request::routeIs('admin.dashboard') ? 'admin-sidebar__link--active' : '' }}">
                <span class="admin-sidebar__icon">
                    <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
                </span>
                <span>Dashboard</span>
            </a>

            <div class="admin-sidebar__divider"></div>

            <p class="admin-sidebar__section-label">Content</p>

            {{-- Content --}}
            <div>
                <button
                    type="button"
                    class="group admin-sidebar__group-btn {{ $activeGroup === 'content' ? 'admin-sidebar__group-btn--active' : '' }}"
                    x-on:click="toggle('content')"
                    aria-expanded="true"
                >
                    <span class="admin-sidebar__icon">
                        <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                    </span>
                    <span class="flex-1 text-left">Content</span>
                    <i class="fa-solid fa-chevron-down admin-sidebar__chevron"
                       :class="isOpen('content') ? 'rotate-180' : ''"
                       aria-hidden="true"></i>
                </button>
                <div x-show="isOpen('content')" x-cloak class="admin-sidebar__children">
                    <a href="{{ route('admin.pages.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.pages.*', 'admin.page-blocks.*') ? 'admin-sidebar__child--active' : '' }}">
                        Pages
                    </a>
                    <a href="{{ route('admin.products.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.products.*') ? 'admin-sidebar__child--active' : '' }}">
                        Products
                    </a>
                    <a href="{{ route('admin.categories.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.categories.*') ? 'admin-sidebar__child--active' : '' }}">
                        Categories
                    </a>
                    <a href="{{ route('admin.destinations.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.destinations.*') ? 'admin-sidebar__child--active' : '' }}">
                        Destinations
                    </a>
                    <a href="{{ route('admin.media.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.media.*') ? 'admin-sidebar__child--active' : '' }}">
                        Media Library
                    </a>
                    <a href="{{ route('admin.faqs.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.faqs.*') ? 'admin-sidebar__child--active' : '' }}">
                        FAQs
                    </a>
                    <a href="{{ route('admin.content-types.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.content-types.*') ? 'admin-sidebar__child--active' : '' }}">
                        Content Types
                    </a>
                </div>
            </div>

            {{-- Design --}}
            <div>
                <button
                    type="button"
                    class="group admin-sidebar__group-btn {{ $activeGroup === 'design' ? 'admin-sidebar__group-btn--active' : '' }}"
                    x-on:click="toggle('design')"
                >
                    <span class="admin-sidebar__icon">
                        <i class="fa-solid fa-palette" aria-hidden="true"></i>
                    </span>
                    <span class="flex-1 text-left">Design</span>
                    <i class="fa-solid fa-chevron-down admin-sidebar__chevron"
                       :class="isOpen('design') ? 'rotate-180' : ''"
                       aria-hidden="true"></i>
                </button>
                <div x-show="isOpen('design')" x-cloak class="admin-sidebar__children">
                    <a href="{{ route('admin.themes.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.themes.*') ? 'admin-sidebar__child--active' : '' }}">
                        Themes
                    </a>
                    <a href="{{ route('admin.menus.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.menus.*', 'admin.menu-items.*') ? 'admin-sidebar__child--active' : '' }}">
                        Menus
                    </a>
                    <a href="{{ route('admin.page-sections.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.page-sections.*') ? 'admin-sidebar__child--active' : '' }}">
                        Page Sections
                    </a>
                </div>
            </div>

            {{-- Forms --}}
            <div>
                <button
                    type="button"
                    class="group admin-sidebar__group-btn {{ $activeGroup === 'forms' ? 'admin-sidebar__group-btn--active' : '' }}"
                    x-on:click="toggle('forms')"
                >
                    <span class="admin-sidebar__icon">
                        <i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i>
                    </span>
                    <span class="flex-1 text-left">Forms</span>
                    <i class="fa-solid fa-chevron-down admin-sidebar__chevron"
                       :class="isOpen('forms') ? 'rotate-180' : ''"
                       aria-hidden="true"></i>
                </button>
                <div x-show="isOpen('forms')" x-cloak class="admin-sidebar__children">
                    <a href="{{ route('admin.forms.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.forms.*', 'admin.form-submissions.*') ? 'admin-sidebar__child--active' : '' }}">
                        Contact Forms
                    </a>
                </div>
            </div>

            {{-- SEO --}}
            <div>
                <button
                    type="button"
                    class="group admin-sidebar__group-btn {{ $activeGroup === 'seo' ? 'admin-sidebar__group-btn--active' : '' }}"
                    x-on:click="toggle('seo')"
                >
                    <span class="admin-sidebar__icon">
                        <i class="fa-solid fa-magnifying-glass-chart" aria-hidden="true"></i>
                    </span>
                    <span class="flex-1 text-left">SEO</span>
                    <i class="fa-solid fa-chevron-down admin-sidebar__chevron"
                       :class="isOpen('seo') ? 'rotate-180' : ''"
                       aria-hidden="true"></i>
                </button>
                <div x-show="isOpen('seo')" x-cloak class="admin-sidebar__children">
                    <a href="{{ route('admin.seo.redirects.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.seo.redirects.*') ? 'admin-sidebar__child--active' : '' }}">
                        Redirects
                    </a>
                    <a href="{{ route('admin.seo.robots.edit') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.seo.robots.*') ? 'admin-sidebar__child--active' : '' }}">
                        Robots.txt
                    </a>
                    <a href="{{ route('sitemap') }}" target="_blank" rel="noopener"
                       class="group admin-sidebar__child">
                        Sitemap
                        <i class="fa-solid fa-arrow-up-right-from-square ml-auto text-[10px] text-admin-secondary" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <div class="admin-sidebar__divider"></div>

            <p class="admin-sidebar__section-label">Visibility</p>

            {{-- Analytics --}}
            <div>
                <button
                    type="button"
                    class="group admin-sidebar__group-btn {{ $activeGroup === 'analytics' ? 'admin-sidebar__group-btn--active' : '' }}"
                    x-on:click="toggle('analytics')"
                >
                    <span class="admin-sidebar__icon">
                        <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
                    </span>
                    <span class="flex-1 text-left">Analytics</span>
                    <i class="fa-solid fa-chevron-down admin-sidebar__chevron"
                       :class="isOpen('analytics') ? 'rotate-180' : ''"
                       aria-hidden="true"></i>
                </button>
                <div x-show="isOpen('analytics')" x-cloak class="admin-sidebar__children">
                    <a href="{{ route('admin.analytics.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.analytics.*') ? 'admin-sidebar__child--active' : '' }}">
                        Dashboard
                    </a>
                </div>
            </div>

            <div class="admin-sidebar__divider"></div>

            <p class="admin-sidebar__section-label">Admin</p>

            {{-- System --}}
            <div>
                <button
                    type="button"
                    class="group admin-sidebar__group-btn {{ $activeGroup === 'system' ? 'admin-sidebar__group-btn--active' : '' }}"
                    x-on:click="toggle('system')"
                >
                    <span class="admin-sidebar__icon">
                        <i class="fa-solid fa-gears" aria-hidden="true"></i>
                    </span>
                    <span class="flex-1 text-left">System</span>
                    <i class="fa-solid fa-chevron-down admin-sidebar__chevron"
                       :class="isOpen('system') ? 'rotate-180' : ''"
                       aria-hidden="true"></i>
                </button>
                <div x-show="isOpen('system')" x-cloak class="admin-sidebar__children">
                    <a href="{{ route('admin.plugins.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.plugins.*') ? 'admin-sidebar__child--active' : '' }}">
                        Plugins
                    </a>
                    <a href="{{ route('admin.audit-logs.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.audit-logs.*') ? 'admin-sidebar__child--active' : '' }}">
                        Audit Log
                    </a>
                </div>
            </div>

            {{-- Settings --}}
            <div>
                <button
                    type="button"
                    class="group admin-sidebar__group-btn {{ $activeGroup === 'settings' ? 'admin-sidebar__group-btn--active' : '' }}"
                    x-on:click="toggle('settings')"
                >
                    <span class="admin-sidebar__icon">
                        <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                    </span>
                    <span class="flex-1 text-left">Settings</span>
                    <i class="fa-solid fa-chevron-down admin-sidebar__chevron"
                       :class="isOpen('settings') ? 'rotate-180' : ''"
                       aria-hidden="true"></i>
                </button>
                <div x-show="isOpen('settings')" x-cloak class="admin-sidebar__children">
                    <a href="{{ route('admin.settings.global-assets.edit') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.settings.global-assets.*') ? 'admin-sidebar__child--active' : '' }}">
                        Global Settings
                    </a>
                    @can('manage-users')
                    <a href="{{ route('admin.settings.appearance.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.settings.appearance.*') ? 'admin-sidebar__child--active' : '' }}">
                        Customize Dashboard
                    </a>
                    @endcan
                </div>
            </div>

            {{-- Users --}}
            @can('manage-users')
            <div>
                <button
                    type="button"
                    class="group admin-sidebar__group-btn {{ $activeGroup === 'users' ? 'admin-sidebar__group-btn--active' : '' }}"
                    x-on:click="toggle('users')"
                >
                    <span class="admin-sidebar__icon">
                        <i class="fa-solid fa-users-gear" aria-hidden="true"></i>
                    </span>
                    <span class="flex-1 text-left">Users</span>
                    <i class="fa-solid fa-chevron-down admin-sidebar__chevron"
                       :class="isOpen('users') ? 'rotate-180' : ''"
                       aria-hidden="true"></i>
                </button>
                <div x-show="isOpen('users')" x-cloak class="admin-sidebar__children">
                    <a href="{{ route('admin.users.index') }}"
                       class="group admin-sidebar__child {{ Request::routeIs('admin.users.*') ? 'admin-sidebar__child--active' : '' }}">
                        Admin Users
                    </a>
                </div>
            </div>
            @endcan

        </nav>
    </div>

</aside>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('adminSidebar', (activeGroup) => ({
        drawerOpen: false,
        activeGroup: activeGroup,
        state: {},

        init() {
            try {
                this.state = JSON.parse(localStorage.getItem('adminSidebarGroups') || '{}');
            } catch (e) {
                this.state = {};
            }
        },

        isOpen(group) {
            if (group === this.activeGroup) return true;
            return this.state[group] ?? false;
        },

        toggle(group) {
            if (group === this.activeGroup) return;
            this.state[group] = !this.isOpen(group);
            try {
                localStorage.setItem('adminSidebarGroups', JSON.stringify(this.state));
            } catch (e) { /* storage unavailable */ }
        },
    }));
});
</script>
