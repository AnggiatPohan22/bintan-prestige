@props([
    'title'        => null,
    'count'        => null,
    'countBadge'   => 'info',
    'countLabel'   => 'item(s)',
    'createRoute'  => null,
    'createLabel'  => 'Create',
])

<div class="admin-card">
    {{-- Card header --}}
    <div class="admin-card-header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            @if($title)
                <h2 class="text-lg font-extrabold text-admin-primary">{{ $title }}</h2>
            @endif

            <div class="flex flex-wrap items-center gap-2">
                @if($count !== null)
                    <span class="admin-badge-{{ $countBadge }}">{{ $count }} {{ $countLabel }}</span>
                @endif
                @if($createRoute)
                    <a href="{{ route($createRoute) }}" class="admin-btn-primary">
                        {{ $createLabel }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="admin-card-body">
        {{-- Optional filters slot --}}
        @if(isset($filters))
            <div class="mb-5">
                {{ $filters }}
            </div>
        @endif

        {{-- Table --}}
        <div class="admin-table-wrapper">
            <table class="admin-table">
                @if(isset($thead))
                    <thead>
                        <tr class="admin-table-header">
                            {{ $thead }}
                        </tr>
                    </thead>
                @endif

                <tbody>
                    {{ $tbody ?? $slot }}
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(isset($pagination))
            <div class="mt-4">
                {{ $pagination }}
            </div>
        @endif
    </div>
</div>
