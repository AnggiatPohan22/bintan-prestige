@php
    $tracking = $trackingIntegrationSettings ?? \App\Support\TrackingIntegrationSettings::valuesFromSettings(collect());
    $shouldRenderTracking = \App\Support\TrackingIntegrationSettings::shouldRender($tracking);
@endphp

@if($shouldRenderTracking)
    @if(($tracking['gtm_enabled'] ?? false) && ! empty($tracking['gtm_container_id']))
        <noscript>
            <iframe src="https://www.googletagmanager.com/ns.html?id={{ $tracking['gtm_container_id'] }}"
                    height="0"
                    width="0"
                    style="display:none;visibility:hidden"></iframe>
        </noscript>
    @endif

    @if(($tracking['custom_body_start_enabled'] ?? false) && ! empty($tracking['custom_body_start_script']))
        {!! $tracking['custom_body_start_script'] !!}
    @endif
@endif
