<header class="bg-white border-b">

    <div class="flex items-center justify-between px-6 py-4">

        <h2 class="text-xl font-semibold">
            Admin Dashboard
        </h2>

        <div class="flex items-center gap-4">

            <span class="text-gray-700">
                {{ auth()->user()->name }}
            </span>

            <form method="POST"
                  action="{{ route('logout') }}">
                @csrf

                <button class="px-4 py-2 bg-red-500 text-white rounded-lg">
                    Logout
                </button>
            </form>

        </div>

    </div>

</header>