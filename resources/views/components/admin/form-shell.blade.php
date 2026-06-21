@props([
    'title'       => null,
    'subtitle'    => null,
    'backRoute'   => null,
    'backLabel'   => 'Back',
    'formAction'  => null,
    'formMethod'  => 'POST',
    'enctype'     => null,
])

<div class="admin-page">

    {{-- Page header --}}
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                @if($title)
                    <h1 class="admin-page-title">{{ $title }}</h1>
                @endif
                @if($subtitle)
                    <p class="admin-page-subtitle">{{ $subtitle }}</p>
                @endif
            </div>

            @if($backRoute)
                <a href="{{ route($backRoute) }}" class="admin-btn-secondary w-full sm:w-auto">
                    {{ $backLabel }}
                </a>
            @endif
        </div>
    </div>

    {{-- Optionally wrap in a form tag --}}
    @if($formAction)
        <form
            action="{{ $formAction }}"
            method="{{ in_array(strtoupper($formMethod), ['GET', 'POST']) ? strtoupper($formMethod) : 'POST' }}"
            @if($enctype) enctype="{{ $enctype }}" @endif
        >
            @csrf
            @if(!in_array(strtoupper($formMethod), ['GET', 'POST']))
                @method(strtoupper($formMethod))
            @endif
    @endif

    {{-- Two-column layout --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_300px]">

        {{-- Main content (left) --}}
        <div class="admin-form-card">
            {{ $content ?? $slot }}
        </div>

        {{-- Sidebar (right) --}}
        @if(isset($sidebar))
            <div class="space-y-4">
                {{ $sidebar }}
            </div>
        @endif

    </div>

    @if($formAction)
        </form>
    @endif

</div>
