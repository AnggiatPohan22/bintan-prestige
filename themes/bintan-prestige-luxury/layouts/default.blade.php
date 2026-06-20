{{--
    Theme layout: default — Bintan Prestige Luxury
    Content template used via ThemeService::resolveLayout('default').
    Falls back to frontend.templates.default when this file is absent.
--}}
<div
    class="cms-page min-h-screen"
    style="background: var(--frontend-surface-body, #f9f9f9); color: var(--frontend-text-body, #3d3d3d);"
>
    @include('frontend.pages._blocks', ['page' => $page])
</div>
