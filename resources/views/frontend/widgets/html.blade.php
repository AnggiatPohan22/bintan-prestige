@php $code = $widget->data['code'] ?? ''; @endphp

@if($code)
    <div class="widget widget--html">{!! $code !!}</div>
@endif
