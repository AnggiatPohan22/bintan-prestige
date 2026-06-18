<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PageService
{
    public function __construct(
        protected ImageOptimizationService $imageService,
    ) {}

    public function store(Request $request): Page
    {
        $data = [
            'title'            => $request->title,
            'slug'             => $request->slug ?: Str::slug($request->title),
            'status'           => $request->status,
            'template_id'      => $request->input('template_id') ?: null,
            'meta_title'       => $request->meta_title,
            'meta_description' => $request->meta_description,
            'sort_order'       => $request->input('sort_order', 0),
        ];

        if ($request->hasFile('og_image')) {
            $data['og_image'] = $this->imageService->upload($request->file('og_image'), 'pages');
        }

        return Page::create($data);
    }

    public function update(Request $request, Page $page): Page
    {
        $data = [
            'title'            => $request->title,
            'slug'             => $request->slug ?: Str::slug($request->title),
            'status'           => $request->status,
            'template_id'      => $request->input('template_id') ?: null,
            'meta_title'       => $request->meta_title,
            'meta_description' => $request->meta_description,
            'sort_order'       => $request->input('sort_order', 0),
        ];

        if ($request->hasFile('og_image')) {
            $this->deleteOgImage($page);
            $data['og_image'] = $this->imageService->upload($request->file('og_image'), 'pages');
        }

        $page->update($data);

        return $page->refresh();
    }

    public function deleteOgImage(Page $page): void
    {
        if ($page->og_image && Storage::disk('public')->exists($page->og_image)) {
            Storage::disk('public')->delete($page->og_image);
        }
    }

    public function reorder(array $ids): void
    {
        foreach ($ids as $sortOrder => $id) {
            Page::where('id', $id)->update(['sort_order' => $sortOrder]);
        }
    }
}
