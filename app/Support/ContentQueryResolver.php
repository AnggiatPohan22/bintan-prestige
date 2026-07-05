<?php

namespace App\Support;

use App\Models\ContentEntry;
use App\Models\ContentType;
use Illuminate\Database\Eloquent\Collection;

/**
 * Resolves a `content_query` builder block's data into a list of published
 * content entries. Shared by PageRenderData (pages) and the entry frontend
 * controller (entries) so the block renders identically wherever it is placed.
 *
 * All queries live here — never in Blade.
 */
final class ContentQueryResolver
{
    /**
     * @param  array<string, mixed>  $data  the block's data bag
     * @return Collection<int, ContentEntry>
     */
    public function resolve(array $data): Collection
    {
        $typeId = ($data['content_type'] ?? null) ?: null;

        if ($typeId === null || ! is_numeric($typeId)) {
            return ContentEntry::query()->whereRaw('1 = 0')->get();
        }

        $type = ContentType::query()->public()->find((int) $typeId);

        if ($type === null) {
            return ContentEntry::query()->whereRaw('1 = 0')->get();
        }

        $limit   = max(1, min(24, (int) ($data['limit'] ?? 6)));
        $orderby = is_string($data['orderby'] ?? null) ? $data['orderby'] : 'newest';

        return $type->entries()
            ->published()
            ->with('contentType')
            ->when($orderby === 'newest', fn ($q) => $q->orderByDesc('published_at')->orderByDesc('id'))
            ->when($orderby === 'oldest', fn ($q) => $q->orderBy('published_at')->orderBy('id'))
            ->when($orderby === 'title', fn ($q) => $q->orderBy('title'))
            ->when($orderby === 'sort_order', fn ($q) => $q->ordered())
            ->limit($limit)
            ->get();
    }
}
