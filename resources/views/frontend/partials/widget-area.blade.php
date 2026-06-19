@php
    $areaWidgets = app(\App\Services\ThemeService::class)->widgetsForArea($area);
@endphp

@foreach($areaWidgets as $widget)
    @if(view()->exists('frontend.widgets.' . $widget->widget_type))
        @include('frontend.widgets.' . $widget->widget_type, ['widget' => $widget])
    @endif
@endforeach
