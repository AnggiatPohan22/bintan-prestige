<?php

namespace App\Support;

use App\Models\Faq;
use App\Models\FormDefinition;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Product;
use Illuminate\Support\Collection;

final class PageRenderData
{
    /**
     * Prepare relation-backed block data once before Blade rendering.
     *
     * @return array{faqItems: array<int, array<string, mixed>>}
     */
    public function __construct(
        private readonly ContentQueryResolver $contentQuery = new ContentQueryResolver(),
        private readonly ContentFieldResolver $contentField = new ContentFieldResolver(),
    ) {}

    public function prepare(Page $page): array
    {
        $blocks = $this->flattenBlocks($page->blocks);
        $faqItems = $this->prepareFaqBlocks($blocks);
        $this->prepareProductBlocks($blocks);
        $this->prepareContactFormBlocks($blocks);
        $this->prepareContentQueryBlocks($blocks);
        $this->prepareContentFieldBlocks($blocks);

        return ['faqItems' => $faqItems];
    }

    /**
     * Resolve the entry list for every content_query block once, before Blade.
     *
     * @param  Collection<int, PageBlock>  $blocks
     */
    private function prepareContentQueryBlocks(Collection $blocks): void
    {
        foreach ($blocks->where('block_type', 'content_query') as $block) {
            $block->resolvedEntries = $this->contentQuery->resolve($block->data ?? []);
        }
    }

    /**
     * Resolve every content_field block. On a page there is no "current entry",
     * so only blocks that name a specific entry_id resolve to a value.
     *
     * @param  Collection<int, PageBlock>  $blocks
     */
    private function prepareContentFieldBlocks(Collection $blocks): void
    {
        foreach ($blocks->where('block_type', 'content_field') as $block) {
            $block->resolvedField = $this->contentField->resolve($block->data ?? [], null);
        }
    }

    /**
     * Resolve the FormDefinition for every contact_form block once, before
     * Blade rendering — keeps the model query out of the view (no queries in
     * Blade). The render partial reads $block->resolvedFormDefinition.
     */
    private function prepareContactFormBlocks(Collection $blocks): void
    {
        $contactBlocks = $blocks->where('block_type', 'contact_form');

        if ($contactBlocks->isEmpty()) {
            return;
        }

        $formIds = $contactBlocks
            ->map(fn (PageBlock $block): mixed => $block->data['form_definition_id'] ?? null)
            ->filter(fn (mixed $id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $formsById = $formIds->isEmpty()
            ? collect()
            : FormDefinition::whereIn('id', $formIds)->get()->keyBy('id');

        foreach ($contactBlocks as $block) {
            $id = $block->data['form_definition_id'] ?? null;
            $block->resolvedFormDefinition = $id !== null ? $formsById->get((int) $id) : null;
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function prepareFaqBlocks(Collection $blocks): array
    {
        $faqBlocks = $blocks->where('block_type', 'faq');
        $faqIds = $faqBlocks
            ->filter(fn (PageBlock $block): bool => ($block->data['source'] ?? 'inline') === 'ids')
            ->flatMap(function (PageBlock $block): array {
                $ids = $block->data['faq_ids'] ?? [];

                return is_array($ids) ? $ids : explode(',', $ids);
            })
            ->filter(fn (mixed $id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $faqsById = $faqIds->isEmpty()
            ? collect()
            : Faq::active()->whereIn('id', $faqIds)->get()->keyBy('id');

        $schemaItems = collect();

        foreach ($faqBlocks as $block) {
            $data = $block->data ?? [];

            if (($data['source'] ?? 'inline') === 'ids') {
                $ids = is_array($data['faq_ids'] ?? null)
                    ? $data['faq_ids']
                    : explode(',', (string) ($data['faq_ids'] ?? ''));

                $items = collect($ids)
                    ->map(fn (mixed $id) => $faqsById->get((int) $id))
                    ->filter()
                    ->map(fn (Faq $faq): array => [
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                    ])
                    ->values();
            } else {
                $items = collect($data['items'] ?? [])
                    ->filter(fn (mixed $item): bool => is_array($item) && filled($item['question'] ?? null))
                    ->map(fn (array $item): array => [
                        'question' => $item['question'],
                        'answer' => $item['answer'] ?? '',
                    ])
                    ->values();
            }

            $block->resolvedFaqItems = $items->all();
            $schemaItems->push(...$items);
        }

        return $schemaItems
            ->filter(fn (array $item): bool => filled($item['question'] ?? null) && filled($item['answer'] ?? null))
            ->map(fn (array $item): array => [
                'question' => $item['question'],
                'answer' => $item['answer'],
                'has_question' => true,
                'has_answer' => true,
            ])
            ->values()
            ->all();
    }

    private function prepareProductBlocks(Collection $blocks): void
    {
        $resolvedByConfiguration = [];

        foreach ($blocks->where('block_type', 'products_grid') as $block) {
            $data = $block->data ?? [];
            $limit = max(3, min(12, (int) ($data['limit'] ?? 6)));
            $categoryId = ($data['category_id'] ?? null) ?: null;
            $destinationId = ($data['destination_id'] ?? null) ?: null;
            $configuration = implode(':', [$categoryId ?? '*', $destinationId ?? '*', $limit]);

            if (! array_key_exists($configuration, $resolvedByConfiguration)) {
                $resolvedByConfiguration[$configuration] = Product::publiclyVisible()
                    ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
                    ->when($destinationId, fn ($query) => $query->where('destination_id', $destinationId))
                    ->frontendListingReady()
                    ->latest()
                    ->limit($limit)
                    ->get();
            }

            $block->resolvedProducts = $resolvedByConfiguration[$configuration];
        }
    }

    /** @return Collection<int, PageBlock> */
    private function flattenBlocks(Collection $blocks): Collection
    {
        return $blocks->flatMap(function (PageBlock $block): array {
            $children = $block->relationLoaded('children') ? $block->children : collect();

            return [$block, ...$this->flattenBlocks($children)->all()];
        })->values();
    }
}
