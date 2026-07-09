<?php

namespace App\Services;

use App\Models\Page;
use App\Models\PageRevision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PageService
{
    public function store(Request $request): Page
    {
        $publishAt = $request->filled('publish_at') ? $request->date('publish_at') : null;

        $data = [
            'title'            => $request->title,
            'slug'             => $request->slug ?: Str::slug($request->title),
            'status'           => $this->resolveStatus($request->status, $publishAt),
            'publish_at'       => $this->resolvePublishAt($publishAt),
            'template_id'      => $request->input('template_id') ?: null,
            'meta_title'       => $request->meta_title,
            'meta_description' => $request->meta_description,
            'sort_order'       => $request->input('sort_order', 0),
        ];

        $ogImage = trim((string) $request->input('og_image', ''));
        if ($ogImage !== '') {
            $data['og_image'] = $ogImage;
        }

        return Page::create($data);
    }

    public function update(Request $request, Page $page): Page
    {
        $this->saveRevision($page);

        $publishAt = $request->filled('publish_at') ? $request->date('publish_at') : null;

        $data = [
            'title'            => $request->title,
            'slug'             => $request->slug ?: Str::slug($request->title),
            'status'           => $this->resolveStatus($request->status, $publishAt),
            'publish_at'       => $this->resolvePublishAt($publishAt),
            'template_id'      => $request->input('template_id') ?: null,
            'meta_title'       => $request->meta_title,
            'meta_description' => $request->meta_description,
            'sort_order'       => $request->input('sort_order', 0),
        ];

        $ogImage = trim((string) $request->input('og_image', ''));
        if ($ogImage !== $page->og_image) {
            $this->deleteOgImage($page);
            $data['og_image'] = $ogImage !== '' ? $ogImage : null;
        }

        $page->update($data);

        return $page->refresh();
    }

    private function resolveStatus(string $submittedStatus, ?\Carbon\Carbon $publishAt): string
    {
        if ($publishAt === null) {
            return $submittedStatus;
        }

        return $publishAt->isFuture() ? 'scheduled' : 'published';
    }

    private function resolvePublishAt(?\Carbon\Carbon $publishAt): ?\Carbon\Carbon
    {
        if ($publishAt === null) {
            return null;
        }

        // Past or present publish_at means we publish immediately — clear the field.
        return $publishAt->isFuture() ? $publishAt : null;
    }

    public function saveRevision(Page $page, ?int $authorId = null): void
    {
        if (! Auth::check() && $authorId === null) {
            return;
        }

        $lastNumber = $page->revisions()->max('revision_number') ?? 0;

        PageRevision::create([
            'page_id'          => $page->id,
            'revision_number'  => $lastNumber + 1,
            'content_snapshot' => $page->blocks()->get(['id', 'parent_block_id', 'block_type', 'label', 'data', 'sort_order', 'is_visible'])->toArray(),
            'meta_snapshot'    => $page->only(['title', 'slug', 'status', 'publish_at', 'template_id', 'meta_title', 'meta_description']),
            'created_by'       => $authorId ?? Auth::id(),
            'created_at'       => now(),
        ]);

        // Prune oldest beyond 20 revisions.
        $keepIds = $page->revisions()->orderByDesc('revision_number')->limit(20)->pluck('id');
        $page->revisions()->whereNotIn('id', $keepIds)->delete();
    }

    public function deleteOgImage(Page $page): void
    {
        // Only remove a legacy module upload (pages/…). Media Library files
        // (media/…) belong to the library and are usage-tracked + delete-guarded
        // there, so they must never be deleted from here.
        if ($page->og_image
            && str_starts_with($page->og_image, 'pages/')
            && Storage::disk('public')->exists($page->og_image)) {
            Storage::disk('public')->delete($page->og_image);
        }
    }

    public function duplicate(Page $page): Page
    {
        $copy = $page->replicate();
        $copy->title     = $page->title . ' (Copy)';
        $copy->slug      = $page->slug . '-copy-' . time();
        $copy->status     = 'draft';
        $copy->publish_at = null;
        $copy->og_image   = null;
        $copy->save();

        $blockMap = [];
        $blocks = $page->blocks()->get();

        foreach ($blocks as $block) {
            $duplicate = $block->replicate(['parent_block_id']);
            $duplicate->fill(['page_id' => $copy->id, 'parent_block_id' => null])->save();
            $blockMap[$block->id] = $duplicate;
        }

        foreach ($blocks as $block) {
            if ($block->parent_block_id !== null && isset($blockMap[$block->parent_block_id])) {
                $blockMap[$block->id]->update(['parent_block_id' => $blockMap[$block->parent_block_id]->id]);
            }
        }

        return $copy;
    }

    public function reorder(array $ids): void
    {
        foreach ($ids as $sortOrder => $id) {
            Page::where('id', $id)->update(['sort_order' => $sortOrder]);
        }
    }
}
