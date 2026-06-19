<?php

namespace App\Support;

use App\Models\Faq;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Product;

final class PageRenderData
{
    /**
     * Prepare relation-backed block data once before Blade rendering.
     *
     * @return array{faqItems: array<int, array<string, mixed>>}
     */
    public function prepare(Page $page): array
    {
        $faqItems = $this->prepareFaqBlocks($page);
        $this->prepareProductBlocks($page);

        return ['faqItems' => $faqItems];
    }

    /** @return array<int, array<string, mixed>> */
    private function prepareFaqBlocks(Page $page): array
    {
        $faqBlocks = $page->blocks->where('block_type', 'faq');
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

    private function prepareProductBlocks(Page $page): void
    {
        $resolvedByConfiguration = [];

        foreach ($page->blocks->where('block_type', 'products_grid') as $block) {
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
}
