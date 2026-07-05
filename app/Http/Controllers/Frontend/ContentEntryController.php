<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\PageBlock;
use App\Support\ContentEntryTemplateRegistry;
use App\Support\ContentQueryResolver;
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
    public function __construct(private readonly ContentQueryResolver $contentQuery = new ContentQueryResolver()) {}

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

        // Resolve any content_query blocks (queries stay out of Blade).
        $this->resolveContentQueries($blocks);

        // Template resolution: per-entry `template` override → validated key → default.
        $templateKey       = ContentEntryTemplateRegistry::keyFor($entry->template);
        $templateContainer = ContentEntryTemplateRegistry::containerFor($templateKey);
        $schemaType        = ContentEntryTemplateRegistry::schemaTypeFor($templateKey);

        $meta           = $entry->seoMeta();
        $seoTitle       = $meta['title'];
        $seoDescription = $meta['description'];
        $canonicalUrl   = $meta['canonical'] ?? $entry->publicUrl();
        $seoRobots      = 'index, follow';

        return view('frontend.content-entries.single', compact(
            'type', 'entry', 'blocks',
            'templateKey', 'templateContainer', 'schemaType',
            'seoTitle', 'seoDescription', 'canonicalUrl', 'seoRobots'
        ));
    }

    /**
     * Resolve content_query blocks anywhere in the (already nested) tree,
     * setting $block->resolvedEntries before Blade renders.
     *
     * @param  Collection<int, PageBlock>  $blocks
     */
    private function resolveContentQueries(Collection $blocks): void
    {
        foreach ($blocks as $block) {
            if ($block->block_type === 'content_query') {
                $block->resolvedEntries = $this->contentQuery->resolve($block->data ?? []);
            }

            if ($block->relationLoaded('children')) {
                $this->resolveContentQueries($block->children);
            }
        }
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
