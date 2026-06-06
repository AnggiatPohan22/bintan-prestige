@php
    $structuredDataJson = \App\Support\StructuredDataBuilder::jsonLd([
        'structuredDataSettings' => $structuredDataSettings ?? [],
        'seoDefaultSettings' => $seoDefaultSettings ?? [],
        'businessIdentity' => $businessIdentity ?? [],
        'contactInformation' => $contactInformation ?? [],
        'activeSocialMediaLinks' => $activeSocialMediaLinks ?? [],
        'siteAssets' => $siteAssets ?? collect(),
        'product' => $product ?? null,
    ]);
@endphp

@if($structuredDataJson)
    <script type="application/ld+json">{!! $structuredDataJson !!}</script>
@endif
