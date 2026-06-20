<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\GlobalSettingsService;
use App\Support\PageRenderData;
use App\Support\PageTemplateRegistry;
use App\Support\SeoDefaultSettings;

class PageController extends Controller
{
    public function __construct(
        private readonly PageRenderData $pageRenderData,
        private readonly GlobalSettingsService $globalSettings,
    ) {}

    /** Public route — published pages only. */
    public function show(Page $page)
    {
        abort_if(! $page->isPublished(), 404);

        return $this->renderPage($page);
    }

    /** Admin-only preview (route is behind auth+admin) — renders any status. */
    public function preview(Page $page)
    {
        return $this->renderPage($page, preview: true);
    }

    private function renderPage(Page $page, bool $preview = false)
    {
        $page->load([
            'template',
            'blocks' => fn ($q) => $q->visible()->ordered(),
        ]);

        $renderData = $this->pageRenderData->prepare($page);
        $templateKey = PageTemplateRegistry::keyFor($page->template?->blade_file);
        $templateView = PageTemplateRegistry::viewFor($templateKey);
        $pageSchemaType = PageTemplateRegistry::schemaTypeFor($templateKey);

        $globalViewData = $this->globalSettings->viewData();
        $seoDefaults = $globalViewData['seoDefaultSettings'];
        $siteAssets = $globalViewData['siteAssets'];
        $seoTitle = $page->meta_title ?: $page->title;
        $seoDescription = $page->meta_description ?: ($seoDefaults['meta_description'] ?: null);
        $seoImage = $page->og_image
            ? asset('storage/'.ltrim($page->og_image, '/'))
            : (($siteAssets[SeoDefaultSettings::OG_IMAGE_KEY] ?? null)?->url
                ?: ($siteAssets['site.social_share.default_image'] ?? null)?->url);
        $canonicalUrl = SeoDefaultSettings::canonicalUrl(
            route('pages.show', $page->slug, false),
            $seoDefaults,
        );
        $socialShareTitle = $seoTitle;
        $socialShareDescription = $page->meta_description ?: null;
        $seoRobots = $preview ? 'noindex, nofollow' : ($page->seo_robots ?: null);
        $pageFaqItems = $renderData['faqItems'];

        return view('frontend.pages.show', compact(
            'page',
            'templateView',
            'preview',
            'seoTitle',
            'seoDescription',
            'seoImage',
            'canonicalUrl',
            'socialShareTitle',
            'socialShareDescription',
            'seoRobots',
            'pageSchemaType',
            'pageFaqItems',
        ));
    }
}
