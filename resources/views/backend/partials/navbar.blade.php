@php
    $routeName = request()->route()?->getName() ?? 'admin.dashboard';
    $pageTitle = match (true) {
        request()->routeIs('admin.products.*') => 'Products',
        request()->routeIs('admin.page-sections.*') => 'Page Sections',
        request()->routeIs('admin.settings.*') => 'Global Assets',
        request()->routeIs('admin.users.*') => 'Admin Users',
        request()->routeIs('admin.faqs.*') => 'FAQs',
        request()->routeIs('admin.categories.*') => 'Categories',
        request()->routeIs('admin.destinations.*') => 'Destinations',
        request()->routeIs('admin.themes.*') => 'Themes',
        default => 'Admin Dashboard',
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
            <div class="admin-topbar__search" role="search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    aria-label="Search admin content"
                    placeholder="Search admin..."
                    disabled
                >
                <span>UI only</span>
            </div>

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
