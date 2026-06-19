@php
    $structuredDataJson = \App\Support\StructuredDataBuilder::jsonLd([
        'structuredDataSettings' => $structuredDataSettings ?? [],
        'seoDefaultSettings' => $seoDefaultSettings ?? [],
        'businessIdentity' => $businessIdentity ?? [],
        'contactInformation' => $contactInformation ?? [],
        'activeSocialMediaLinks' => $activeSocialMediaLinks ?? [],
        'siteAssets' => $siteAssets ?? collect(),
        'product' => $product ?? null,
        'page' => $page ?? null,
        'pageSchemaType' => $pageSchemaType ?? null,
        'priceState' => $priceState ?? null,
        'mediaState' => $mediaState ?? null,
        'descriptionState' => $descriptionState ?? null,
        'breadcrumbState' => $breadcrumbState ?? null,
        'faqItems' => $pageFaqItems ?? $faqItems ?? null,
        'metadataState' => $metadataState ?? null,
        'listingProducts' => $structuredDataListingProducts ?? null,
        'listingName' => $seoTitle ?? $title ?? null,
        'seoDescription' => $seoDescription ?? null,
        'seoImage' => $seoImage ?? null,
        'canonicalUrl' => $canonicalUrl ?? null,
    ]);
@endphp

@if($structuredDataJson)
    <script type="application/ld+json">{!! $structuredDataJson !!}</script>
@endif
