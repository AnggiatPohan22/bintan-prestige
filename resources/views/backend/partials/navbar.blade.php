@php
    $routeName = request()->route()?->getName() ?? 'admin.dashboard';
    $pageTitle = match (true) {
        request()->routeIs('admin.products.*') => 'Products',
        request()->routeIs('admin.page-sections.*') => 'Page Sections',
        request()->routeIs('admin.settings.*') => 'Settings',
        request()->routeIs('admin.users.*') => 'Admin Users',
        request()->routeIs('admin.faqs.*') => 'FAQs',
        request()->routeIs('admin.categories.*') => 'Categories',
        request()->routeIs('admin.destinations.*') => 'Destinations',
        request()->routeIs('admin.themes.*') => 'Themes',
        request()->routeIs('admin.pages.*') => 'Pages',
        request()->routeIs('admin.media.*') => 'Media Library',
        request()->routeIs('admin.menus.*', 'admin.menu-items.*') => 'Menus',
        request()->routeIs('admin.forms.*', 'admin.form-submissions.*') => 'Forms',
        request()->routeIs('admin.seo.*') => 'SEO',
        request()->routeIs('admin.analytics.*') => 'Analytics',
        request()->routeIs('admin.plugins.*') => 'Plugins',
        request()->routeIs('admin.audit-logs.*') => 'Audit Log',
        request()->routeIs('admin.dashboard') => 'Dashboard',
        default => 'Admin',
    };
    $pageEyebrow = str($routeName)
        ->replace('admin.', '')
        ->replace('.', ' / ')
        ->title();
    $user = auth()->user();
    $userInitials = collect(explode(' ', $user?->name ?? 'Admin'))
        ->filter()
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
@endphp

<header class="admin-topbar">
    <div class="admin-topbar__main">
        <div class="admin-topbar__title-group">
            <p class="admin-topbar__breadcrumb">
                Admin
                <span aria-hidden="true">/</span>
                {{ $pageEyebrow }}
            </p>

            <h2 class="admin-topbar__title">
                {{ $pageTitle }}
            </h2>
        </div>

        <div class="admin-topbar__actions">
            <button
                type="button"
                class="admin-topbar__search cursor-pointer"
                role="search"
                aria-label="Open command palette"
                x-on:click="$dispatch('keydown', { ctrlKey: true, key: 'k', preventDefault: () => {} })"
                x-data
                @click.prevent="document.dispatchEvent(new KeyboardEvent('keydown', { ctrlKey: true, key: 'k', bubbles: true }))"
            >
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <span class="flex-1 text-left text-sm font-semibold text-slate-400">Search admin...</span>
                <span>Ctrl K</span>
            </button>

            <button
                type="button"
                class="admin-topbar__icon-button"
                aria-label="Notifications"
            >
                <i class="fa-regular fa-bell" aria-hidden="true"></i>
                <span class="admin-topbar__notification-dot" aria-hidden="true"></span>
            </button>

            <div class="admin-user-menu" x-data="{ open: false }" x-on:keydown.escape.window="open = false">
                <button
                    type="button"
                    class="admin-user-menu__trigger"
                    aria-haspopup="menu"
                    x-bind:aria-expanded="open.toString()"
                    x-on:click="open = ! open"
                    x-on:click.outside="open = false"
                >
                    <span class="admin-user-menu__avatar">
                        {{ $userInitials ?: 'A' }}
                    </span>

                    <span class="admin-user-menu__identity">
                        <span>{{ $user?->name ?? 'Admin' }}</span>
                        <small>{{ $user?->isSuperAdmin() ? 'Super Admin' : 'Administrator' }}</small>
                    </span>

                    <i class="fa-solid fa-chevron-down admin-user-menu__chevron" aria-hidden="true"></i>
                </button>

                <div
                    class="admin-user-menu__dropdown"
                    x-cloak
                    x-show="open"
                    x-transition.origin.top.right
                    role="menu"
                >
                    <div class="admin-user-menu__summary">
                        <span class="admin-user-menu__avatar">
                            {{ $userInitials ?: 'A' }}
                        </span>

                        <div class="min-w-0">
                            <p>{{ $user?->name ?? 'Admin' }}</p>
                            <small>{{ $user?->email ?? 'admin@example.com' }}</small>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit" class="admin-user-menu__logout" role="menuitem">
                            <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
