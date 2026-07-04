<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\PageBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Public routing for content entries.
 *
 * Wired through Route::fallback() so it never shadows an explicit route (home,
 * products, pages, forms, sitemap) or the admin routes — the fallback is always
 * the lowest-priority match. Content-type route_base values are unique and
 * guarded against RESERVED_PREFIXES, so resolution is deterministic and collision-free.
 *
 *   /{route_base}          → archive (list published entries; requires has_archive)
 *   /{route_base}/{slug}   → single published entry
 */
class ContentEntryController extends Controller
{
    public function resolve(Request $request): mixed
    {
        $segments = array_values(array_filter(explode('/', trim($request->path(), '/')), fn ($s): bool => $s !== ''));

        return match (count($segments)) {
            1       => $this->archive($segments[0]),
            2       => $this->single($segments[0], $segments[1]),
            default => abort(404),
        };
    }

    private function archive(string $routeBase): mixed
    {
        $type = ContentType::query()
            ->public()
            ->where('route_base', $routeBase)
            ->where('has_archive', true)
            ->first();

        if ($type === null) {
            abort(404);
        }

        $entries = $type->entries()
            ->published()
            ->ordered()
            ->paginate(12);

        $seoTitle       = $type->label_plural;
        $seoDescription = $type->description;
        $canonicalUrl   = url($type->route_base);
        $seoRobots      = 'index, follow';

        return view('frontend.content-entries.archive', compact(
            'type', 'entries', 'seoTitle', 'seoDescription', 'canonicalUrl', 'seoRobots'
        ));
    }

    private function single(string $routeBase, string $slug): mixed
    {
        $type = ContentType::query()
            ->public()
            ->where('route_base', $routeBase)
            ->first();

        if ($type === null) {
            abort(404);
        }

        $entry = $type->entries()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $blocks = $type->supports('editor')
            ? $this->buildTree($entry->blocks()->visible()->ordered()->get())
            : collect();

        $meta           = $entry->seoMeta();
        $seoTitle       = $meta['title'];
        $seoDescription = $meta['description'];
        $canonicalUrl   = $meta['canonical'] ?? $entry->publicUrl();
        $seoRobots      = 'index, follow';

        return view('frontend.content-entries.single', compact(
            'type', 'entry', 'blocks', 'seoTitle', 'seoDescription', 'canonicalUrl', 'seoRobots'
        ));
    }

    /**
     * Nest flat, ordered blocks into a parent/child tree for rendering.
     *
     * @param  Collection<int, PageBlock>  $blocks
     * @return Collection<int, PageBlock>
     */
    private function buildTree(Collection $blocks): Collection
    {
        $byParent = $blocks->groupBy(
            fn (PageBlock $b): string => $b->parent_block_id === null ? 'root' : (string) $b->parent_block_id
        );

        $attach = function (?int $parentId) use (&$attach, $byParent): Collection {
            $key = $parentId === null ? 'root' : (string) $parentId;

            return ($byParent->get($key) ?? collect())
                ->each(function (PageBlock $b) use (&$attach): void {
                    $b->setRelation('children', $attach($b->id));
                })
                ->values();
        };

        return $attach(null);
    }
}
