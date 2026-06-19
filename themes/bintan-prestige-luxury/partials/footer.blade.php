{{--
    Theme partial: footer — Bintan Prestige Luxury
    Renders widget areas declared in theme.json, then delegates the main
    footer markup to the Phase 2 partial so brand/CTA/links stay in sync.

    Widget areas:
      before-footer  — full-width zone rendered above the footer shell
      footer-col-1   — first widget column (rendered as a widget band above footer body)
      footer-col-2   — second widget column
      footer-col-3   — third widget column
--}}
@php
    $themeService = app(\App\Services\ThemeService::class);
    $hasBeforeFooter = $themeService->widgetsForArea('before-footer')->isNotEmpty();
    $hasFooterCol1   = $themeService->widgetsForArea('footer-col-1')->isNotEmpty();
    $hasFooterCol2   = $themeService->widgetsForArea('footer-col-2')->isNotEmpty();
    $hasFooterCol3   = $themeService->widgetsForArea('footer-col-3')->isNotEmpty();
    $hasWidgetCols   = $hasFooterCol1 || $hasFooterCol2 || $hasFooterCol3;
@endphp

@if($hasBeforeFooter)
    <div
        class="theme-before-footer"
        style="background: var(--frontend-surface-dark, #111111); color: var(--frontend-white, #ffffff);"
    >
        <div class="container mx-auto px-4 py-8">
            @include('frontend.partials.widget-area', ['area' => 'before-footer'])
        </div>
    </div>
@endif

@if($hasWidgetCols)
    <div
        class="theme-footer-widgets"
        style="background: var(--frontend-black, #1a1a1a); color: var(--frontend-white, #ffffff); border-top: 1px solid var(--frontend-gold, #B8924A);"
    >
        <div class="container mx-auto px-4 py-10 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="theme-footer-widgets__col">
                @include('frontend.partials.widget-area', ['area' => 'footer-col-1'])
            </div>
            <div class="theme-footer-widgets__col">
                @include('frontend.partials.widget-area', ['area' => 'footer-col-2'])
            </div>
            <div class="theme-footer-widgets__col">
                @include('frontend.partials.widget-area', ['area' => 'footer-col-3'])
            </div>
        </div>
    </div>
@endif

@include('frontend.partials.footer')
