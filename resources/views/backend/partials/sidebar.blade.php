<aside class="w-64 bg-white shadow-lg">

    <div class="p-6 border-b">
        <h1 class="text-2xl font-bold">
            Travel Admin
        </h1>
    </div>

    <nav class="p-4 space-y-2">

        <a href="{{ route('admin.dashboard') }}"
           class="block px-4 py-3 rounded-lg hover:bg-gray-100
           {{ request()->routeIs('admin.dashboard') ? 'bg-gray-200 font-semibold' : '' }}">
            Dashboard
        </a>

        <a href="{{ route('admin.products.index') }}"
           class="block px-4 py-3 rounded-lg hover:bg-gray-100
           {{ request()->routeIs('admin.products.*') ? 'bg-gray-200 font-semibold' : '' }}">
            Products
        </a>

        <a href="{{ route('admin.categories.index') }}"
           class="block px-4 py-3 rounded-lg hover:bg-gray-100
           {{ request()->routeIs('admin.categories.*') ? 'bg-gray-200 font-semibold' : '' }}">
            Categories
        </a>

        <a href="{{ route('admin.destinations.index') }}"
           class="block px-4 py-3 rounded-lg hover:bg-gray-100
           {{ request()->routeIs('admin.destinations.*') ? 'bg-gray-200 font-semibold' : '' }}">
            Destinations
        </a>

    </nav>

</aside>