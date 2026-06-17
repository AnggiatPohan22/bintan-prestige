@php
    $tracking = $trackingIntegrationSettings ?? \App\Support\TrackingIntegrationSettings::valuesFromSettings(collect());
    $shouldRenderTracking = \App\Support\TrackingIntegrationSettings::shouldRender($tracking);
    $whatsappTrackingEnabled = $shouldRenderTracking && ($tracking['whatsapp_enabled'] ?? false);
@endphp

@if($whatsappTrackingEnabled)
    <script>
        window.BP_TRACKING_INTEGRATIONS = {
            whatsapp: {
                enabled: true,
                ga4EventName: @json($tracking['whatsapp_ga4_event_name'] ?: 'whatsapp_cta_click'),
                metaEventName: @json($tracking['whatsapp_meta_event_name'] ?: 'Lead'),
                trackHeader: @json((bool) ($tracking['whatsapp_track_header'] ?? true)),
                trackFooter: @json((bool) ($tracking['whatsapp_track_footer'] ?? true)),
                trackProduct: @json((bool) ($tracking['whatsapp_track_product'] ?? true))
            }
        };
    </script>
@endif

@if($shouldRenderTracking && ($tracking['custom_body_end_enabled'] ?? false) && ! empty($tracking['custom_body_end_script']))
    {!! $tracking['custom_body_end_script'] !!}
@endif
