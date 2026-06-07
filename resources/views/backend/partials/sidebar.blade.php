<aside class="admin-sidebar-shell" x-data="{ sidebarOpen: false }">

    <button
        type="button"
        class="admin-sidebar-toggle"
        aria-label="Open admin navigation"
        x-on:click="sidebarOpen = true"
    >
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
    </button>

    <div
        class="admin-sidebar-backdrop"
        x-cloak
        x-show="sidebarOpen"
        x-transition.opacity
        x-on:click="sidebarOpen = false"
    ></div>

    <div
        class="admin-sidebar"
        x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
        <div class="admin-sidebar__brand">
            <div class="admin-sidebar__brand-mark">
                BP
            </div>

            <div class="min-w-0">
                <h1 class="admin-sidebar__title">
                    Travel Admin
                </h1>

                <p class="admin-sidebar__subtitle">
                    Bintan Prestige
                </p>
            </div>

            <button
                type="button"
                class="admin-sidebar__close"
                aria-label="Close admin navigation"
                x-on:click="sidebarOpen = false"
            >
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="admin-sidebar__nav" aria-label="Admin navigation">
            <p class="admin-sidebar__section-label">
                Workspace
            </p>

            <a href="{{ route('admin.dashboard') }}"
               class="group admin-sidebar__link {{ request()->routeIs('admin.dashboard') ? 'admin-sidebar__link--active' : '' }}">
                <span class="admin-sidebar__icon">
                    <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
                </span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('admin.products.index') }}"
               class="group admin-sidebar__link {{ request()->routeIs('admin.products.*') ? 'admin-sidebar__link--active' : '' }}">
                <span class="admin-sidebar__icon">
                    <i class="fa-solid fa-suitcase-rolling" aria-hidden="true"></i>
                </span>
                <span>Products</span>
            </a>

            <a href="{{ route('admin.page-sections.index') }}"
               class="group admin-sidebar__link {{ request()->routeIs('admin.page-sections.*') ? 'admin-sidebar__link--active' : '' }}">
                <span class="admin-sidebar__icon">
                    <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                </span>
                <span>Page Sections</span>
            </a>

            <a href="{{ route('admin.settings.global-assets.edit') }}"
               class="group admin-sidebar__link {{ request()->routeIs('admin.settings.*') ? 'admin-sidebar__link--active' : '' }}">
                <span class="admin-sidebar__icon">
                    <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                </span>
                <span>Global Assets</span>
            </a>

            <p class="admin-sidebar__section-label">
                Content
            </p>

            <a href="{{ route('admin.faqs.index') }}"
               class="group admin-sidebar__link {{ request()->routeIs('admin.faqs.*') ? 'admin-sidebar__link--active' : '' }}">
                <span class="admin-sidebar__icon">
                    <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                </span>
                <span>FAQs</span>
            </a>

            <a href="{{ route('admin.categories.index') }}"
               class="group admin-sidebar__link {{ request()->routeIs('admin.categories.*') ? 'admin-sidebar__link--active' : '' }}">
                <span class="admin-sidebar__icon">
                    <i class="fa-solid fa-tags" aria-hidden="true"></i>
                </span>
                <span>Categories</span>
            </a>

            <a href="{{ route('admin.destinations.index') }}"
               class="group admin-sidebar__link {{ request()->routeIs('admin.destinations.*') ? 'admin-sidebar__link--active' : '' }}">
                <span class="admin-sidebar__icon">
                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                </span>
                <span>Destinations</span>
            </a>
        </nav>
    </div>

</aside>
