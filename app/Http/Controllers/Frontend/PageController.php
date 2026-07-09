<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Admin\PageBlockController;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageTemplate;
use App\Services\GlobalSettingsService;
use App\Services\PageBlockService;
use App\Support\Locales;
use App\Support\PageRenderData;
use App\Support\PageTemplateRegistry;
use App\Support\SeoDefaultSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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

        return $this->renderPage($page, localeAlternates: $this->localeAlternates($page));
    }

    /**
     * Published translation-group siblings as [locale => url], used by the locale
     * switcher so switching language on a page lands on its counterpart (and hides
     * locales with no published translation).
     *
     * @return array<string, string>
     */
    private function localeAlternates(Page $page): array
    {
        $alternates = [];

        foreach (Locales::activeCodes() as $code) {
            $sibling = $page->translationIn($code);

            if ($sibling !== null && $sibling->isPublished()) {
                $alternates[$code] = $sibling->publicUrl();
            }
        }

        return $alternates;
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
        $validated = $request->validate([
            'blocks' => ['nullable', 'array', 'max:200'],
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('page_templates', 'id')->where(
                    fn ($query) => $query
                        ->where('is_active', true)
                        ->whereIn('blade_file', PageTemplateRegistry::keys())
                ),
            ],
        ]);
        $nodeCount = 0;
        $rawBlocks = $validated['blocks'] ?? [];
        $blocks = $this->transientTree(is_array($rawBlocks) ? $rawBlocks : [], $nodeCount);

        if ($request->exists('template_id')) {
            $template = isset($validated['template_id'])
                ? PageTemplate::query()->find($validated['template_id'])
                : null;
            $page->setRelation('template', $template);
        }

        return $this->renderPage($page, preview: true, injectedBlocks: $blocks, builderCanvas: true);
    }

    private function renderPage(Page $page, bool $preview = false, ?Collection $injectedBlocks = null, bool $builderCanvas = false, ?array $localeAlternates = null)
    {
        if (! $page->relationLoaded('template')) {
            $page->load(['template']);
        }

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
        $pagePath = $page->locale === Locales::default()
            ? route('pages.show', $page->slug, false)
            : route($page->locale.'.pages.show', $page->slug, false);
        $canonicalUrl = SeoDefaultSettings::canonicalUrl($pagePath, $seoDefaults);
        $socialShareTitle = $seoTitle;
        $socialShareDescription = $page->meta_description ?: null;
        $seoRobots = $preview ? 'noindex, nofollow' : ($page->seo_robots ?: null);
        $pageFaqItems = $renderData['faqItems'];

        return view('frontend.pages.show', compact(
            'page',
            'templateView',
            'preview',
            'builderCanvas',
            'seoTitle',
            'seoDescription',
            'seoImage',
            'canonicalUrl',
            'socialShareTitle',
            'socialShareDescription',
            'seoRobots',
            'pageSchemaType',
            'pageFaqItems',
            'localeAlternates',
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

                $rawData = $validated['data'] ?? [];
                try {
                    $blockData = $this->pageBlockService->validateAndSanitizeData($validated['block_type'], $rawData);
                } catch (ValidationException) {
                    $blockData = $rawData;
                }

                $block = new PageBlock([
                    'block_type' => $validated['block_type'],
                    'label' => $validated['label'] ?? null,
                    'data' => $blockData,
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
