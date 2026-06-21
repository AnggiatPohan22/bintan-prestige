<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Admin\PageBlockController;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageBlock;
use App\Services\GlobalSettingsService;
use App\Services\PageBlockService;
use App\Support\PageRenderData;
use App\Support\PageTemplateRegistry;
use App\Support\SeoDefaultSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PageController extends Controller
{
    public function __construct(
        private readonly PageRenderData $pageRenderData,
        private readonly GlobalSettingsService $globalSettings,
        private readonly PageBlockService $pageBlockService,
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
        $request->validate(['blocks' => ['nullable', 'array', 'max:200']]);
        $nodeCount = 0;
        $blocks = $this->transientTree($request->input('blocks', []), $nodeCount);

        return $this->renderPage($page, preview: true, injectedBlocks: $blocks);
    }

    private function renderPage(Page $page, bool $preview = false, ?Collection $injectedBlocks = null)
    {
        $page->load(['template']);

        if ($injectedBlocks !== null) {
            $page->setRelation('blocks', $injectedBlocks);
        } else {
            $flatBlocks = $page->blocks()->visible()->ordered()->get();
            $page->setRelation('blocks', $this->buildTree($flatBlocks));
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

    /** @param array<int, mixed> $nodes
     * @return Collection<int, PageBlock>
     */
    private function transientTree(array $nodes, int &$nodeCount, int $depth = 0, ?string $parentType = null): Collection
    {
        if ($depth > 5) {
            throw ValidationException::withMessages(['blocks' => 'Block nesting is limited to five levels.']);
        }

        return collect($nodes)
            ->map(function (mixed $node) use (&$nodeCount, $depth, $parentType): PageBlock {
                if (! is_array($node) || ++$nodeCount > 200) {
                    throw ValidationException::withMessages(['blocks' => 'The block tree must contain at most 200 valid nodes.']);
                }

                $validated = Validator::make($node, [
                    'block_type' => ['required', 'string', 'in:'.implode(',', PageBlockController::blockTypes())],
                    'label' => ['nullable', 'string', 'max:255'],
                    'data' => ['nullable', 'array'],
                    'sort_order' => ['nullable', 'integer'],
                    'is_visible' => ['nullable', 'boolean'],
                    'children' => ['nullable', 'array'],
                ])->validate();

                if ($parentType === 'columns' && $validated['block_type'] !== 'group') {
                    throw ValidationException::withMessages(['blocks' => 'Columns can contain Group blocks only.']);
                }

                if (! empty($validated['children']) && ! in_array($validated['block_type'], ['group', 'columns'], true)) {
                    throw ValidationException::withMessages(['blocks' => 'Only Group and Columns blocks can contain children.']);
                }

                $block = new PageBlock([
                    'block_type' => $validated['block_type'],
                    'label' => $validated['label'] ?? null,
                    'data' => $this->pageBlockService->validateAndSanitizeData($validated['block_type'], $validated['data'] ?? []),
                    'sort_order' => $validated['sort_order'] ?? 0,
                    'is_visible' => $validated['is_visible'] ?? true,
                ]);
                $block->id = -$nodeCount;
                $block->setRelation('children', $this->transientTree(
                    $validated['children'] ?? [],
                    $nodeCount,
                    $depth + 1,
                    $validated['block_type'],
                ));

                return $block;
            })
            ->filter(fn (PageBlock $block): bool => $block->is_visible)
            ->sortBy('sort_order')
            ->values();
    }

    /** @param Collection<int, PageBlock> $blocks
     * @return Collection<int, PageBlock>
     */
    private function buildTree(Collection $blocks): Collection
    {
        $byParent = $blocks->groupBy(fn (PageBlock $block): string => $block->parent_block_id === null ? 'root' : (string) $block->parent_block_id);

        $attach = function (?int $parentId, array $ancestors = []) use (&$attach, $byParent): Collection {
            $key = $parentId === null ? 'root' : (string) $parentId;

            return ($byParent->get($key) ?? collect())
                ->reject(fn (PageBlock $block): bool => in_array($block->id, $ancestors, true))
                ->each(function (PageBlock $block) use (&$attach, $ancestors): void {
                    $block->setRelation('children', $attach($block->id, [...$ancestors, $block->id]));
                })
                ->values();
        };

        return $attach(null);
    }
}
