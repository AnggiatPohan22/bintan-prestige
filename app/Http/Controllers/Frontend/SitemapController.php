<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\SeoDefaultSettings;
use App\Services\GlobalSettingsService;

class SitemapController extends Controller
{
    public function __construct(private readonly GlobalSettingsService $globalSettings) {}

    public function index()
    {
        $seoDefaults = $this->globalSettings->viewData()['seoDefaultSettings'];

        $pages = Page::published()
            ->ordered()
            ->get(['slug', 'updated_at']);

        $urls = $pages->map(fn (Page $page) => [
            'loc'     => SeoDefaultSettings::canonicalUrl(
                route('pages.show', $page->slug, false),
                $seoDefaults,
            ),
            'lastmod' => $page->updated_at->toAtomString(),
        ]);

        return response()
            ->view('frontend.sitemap', compact('urls'))
            ->header('Content-Type', 'text/xml; charset=UTF-8');
    }
}
