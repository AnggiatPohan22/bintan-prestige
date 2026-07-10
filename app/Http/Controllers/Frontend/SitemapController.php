<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ContentEntry;
use App\Models\Page;
use App\Services\GlobalSettingsService;
use App\Support\Locales;
use App\Support\SeoDefaultSettings;
use Illuminate\Support\Collection;

class SitemapController extends Controller
{
    public function __construct(private readonly GlobalSettingsService $globalSettings) {}

    public function index()
    {
        $seoDefaults = $this->globalSettings->viewData()['seoDefaultSettings'];

        $urls = $this->pageEntries($seoDefaults)
            ->concat($this->contentEntries($seoDefaults))
            ->values();

        return response()
            ->view('frontend.sitemap', compact('urls'))
            ->header('Content-Type', 'text/xml; charset=UTF-8');
    }

    /**
     * @param  array<string, mixed>  $seoDefaults
     * @return Collection<int, array{loc: string, lastmod: string, alternates: array<string, string>}>
     */
    private function pageEntries(array $seoDefaults): Collection
    {
        $pages = Page::published()
            ->ordered()
            ->get(['id', 'slug', 'locale', 'translation_group_id', 'updated_at']);

        // Precompute the URL per row, then group by translation_group_id so every
        // sibling knows about the others (xhtml:link hreflang alternates).
        $rows = $pages->map(fn (Page $p): array => [
            'row' => $p,
            'url' => SeoDefaultSettings::canonicalUrl(
                $p->locale === Locales::default()
                    ? route('pages.show', $p->slug, false)
                    : route($p->locale.'.pages.show', $p->slug, false),
                $seoDefaults,
            ),
        ]);

        return $this->emitLocalized($rows->all());
    }

    /**
     * @param  array<string, mixed>  $seoDefaults
     * @return Collection<int, array{loc: string, lastmod: string, alternates: array<string, string>}>
     */
    private function contentEntries(array $seoDefaults): Collection
    {
        $entries = ContentEntry::query()
            ->published()
            ->whereHas('contentType', fn ($q) => $q
                ->where('is_public', true)
                ->where('is_active', true)
                ->whereNotNull('route_base'))
            ->with('contentType:id,route_base')
            ->get(['id', 'slug', 'locale', 'translation_group_id', 'updated_at', 'content_type_id']);

        $rows = $entries
            ->map(function (ContentEntry $e) use ($seoDefaults): ?array {
                $base = $e->contentType?->route_base;
                if ($base === null || $base === '' || ! $e->slug) {
                    return null;
                }
                $path = $e->locale === Locales::default()
                    ? '/'.$base.'/'.$e->slug
                    : '/'.$e->locale.'/'.$base.'/'.$e->slug;

                return [
                    'row' => $e,
                    'url' => SeoDefaultSettings::canonicalUrl($path, $seoDefaults),
                ];
            })
            ->filter()
            ->values();

        return $this->emitLocalized($rows->all());
    }

    /**
     * Turn a row+url list into sitemap entries, grouping siblings by their
     * translation_group_id so every entry lists ALL locales as xhtml:link
     * alternates.
     *
     * @param  list<array{row: \Illuminate\Database\Eloquent\Model, url: string}>  $rows
     * @return Collection<int, array{loc: string, lastmod: string, alternates: array<string, string>}>
     */
    private function emitLocalized(array $rows): Collection
    {
        /** @var array<string, array<string, string>> $alternatesByGroup */
        $alternatesByGroup = [];
        foreach ($rows as $pair) {
            $groupId = (string) $pair['row']->getAttribute('translation_group_id');
            $locale  = (string) $pair['row']->getAttribute('locale');
            $alternatesByGroup[$groupId][$locale] = $pair['url'];
        }

        return collect($rows)->map(function (array $pair) use ($alternatesByGroup): array {
            $groupId = (string) $pair['row']->getAttribute('translation_group_id');
            $updated = $pair['row']->getAttribute('updated_at');
            $lastmod = ($updated instanceof \Illuminate\Support\Carbon || $updated instanceof \DateTimeInterface)
                ? \Illuminate\Support\Carbon::instance($updated)->toAtomString()
                : now()->toAtomString();

            return [
                'loc'        => $pair['url'],
                'lastmod'    => $lastmod,
                'alternates' => $alternatesByGroup[$groupId] ?? [],
            ];
        });
    }
}
