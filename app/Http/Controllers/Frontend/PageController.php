<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\PageBlockController;
use App\Models\Page;
use App\Models\PageBlock;
use App\Services\GlobalSettingsService;
use App\Support\PageRenderData;
use App\Support\PageTemplateRegistry;
use App\Support\SeoDefaultSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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

    /**
     * Admin-only: render page with a transient block tree from the request body.
     * Used by the visual builder to preview unsaved changes without persisting them.
     */
    public function previewPayload(Request $request, Page $page): mixed
    {
        $request->validate([
            'blocks'              => ['nullable', 'array'],
            'blocks.*.block_type' => ['required', 'string', 'in:'.implode(',', PageBlockController::blockTypes())],
            'blocks.*.label'      => ['nullable', 'string', 'max:255'],
            'blocks.*.data'       => ['nullable', 'array'],
            'blocks.*.sort_order' => ['nullable', 'integer'],
            'blocks.*.is_visible' => ['nullable', 'boolean'],
        ]);

        $blocks = collect($request->input('blocks', []))
            ->filter(fn (array $b): bool => (bool) ($b['is_visible'] ?? true))
            ->sortBy('sort_order')
            ->values()
            ->map(fn (array $b): PageBlock => new PageBlock([
                'block_type' => $b['block_type'],
                'label'      => $b['label'] ?? null,
                'data'       => $b['data'] ?? [],
                'sort_order' => $b['sort_order'] ?? 0,
                'is_visible' => true,
            ]));

        return $this->renderPage($page, preview: true, injectedBlocks: $blocks);
    }

    private function renderPage(Page $page, bool $preview = false, ?Collection $injectedBlocks = null)
    {
        $page->load(['template']);

        if ($injectedBlocks !== null) {
            $page->setRelation('blocks', $injectedBlocks);
        } else {
            $page->load(['blocks' => fn ($q) => $q->visible()->ordered()]);
        }

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
