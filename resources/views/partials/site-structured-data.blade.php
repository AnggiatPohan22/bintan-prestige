@php
    $structuredDataJson = \App\Support\StructuredDataBuilder::jsonLd([
        'structuredDataSettings' => $structuredDataSettings ?? [],
        'seoDefaultSettings' => $seoDefaultSettings ?? [],
        'businessIdentity' => $businessIdentity ?? [],
        'contactInformation' => $contactInformation ?? [],
        'activeSocialMediaLinks' => $activeSocialMediaLinks ?? [],
        'siteAssets' => $siteAssets ?? collect(),
        'product' => $product ?? null,
        'listingProducts' => $structuredDataListingProducts ?? null,
        'listingName' => $seoTitle ?? $title ?? null,
        'canonicalUrl' => $canonicalUrl ?? null,
    ]);
@endphp

@if($structuredDataJson)
    <script type="application/ld+json">{!! $structuredDataJson !!}</script>
@endif
