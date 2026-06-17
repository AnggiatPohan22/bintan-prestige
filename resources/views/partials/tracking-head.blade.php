@php
    $tracking = $trackingIntegrationSettings ?? \App\Support\TrackingIntegrationSettings::valuesFromSettings(collect());
    $shouldRenderTracking = \App\Support\TrackingIntegrationSettings::shouldRender($tracking);
@endphp

@if($shouldRenderTracking)
    @if(! empty($tracking['google_verification']))
        <meta name="google-site-verification" content="{{ $tracking['google_verification'] }}">
    @endif

    @if(($tracking['ga4_enabled'] ?? false) && ! empty($tracking['ga4_measurement_id']))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $tracking['ga4_measurement_id'] }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($tracking['ga4_measurement_id']));
        </script>
    @endif

    @if(($tracking['gtm_enabled'] ?? false) && ! empty($tracking['gtm_container_id']))
        <script>
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer',@json($tracking['gtm_container_id']));
        </script>
    @endif

    @if(($tracking['meta_pixel_enabled'] ?? false) && ! empty($tracking['meta_pixel_id']))
        <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', @json($tracking['meta_pixel_id']));
            fbq('track', 'PageView');
        </script>
    @endif

    @if(($tracking['clarity_enabled'] ?? false) && ! empty($tracking['clarity_project_id']))
        <script>
            (function(c,l,a,r,i,t,y){
                c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
                t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
                y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
            })(window, document, "clarity", "script", @json($tracking['clarity_project_id']));
        </script>
    @endif

    @if(($tracking['custom_head_enabled'] ?? false) && ! empty($tracking['custom_head_script']))
        {!! $tracking['custom_head_script'] !!}
    @endif
@endif
