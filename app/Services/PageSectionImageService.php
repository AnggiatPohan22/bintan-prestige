<?php

namespace App\Services;

use App\Models\PageSection;
use App\Models\PageSectionMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PageSectionImageService
{
    public function storeUploadedImage(
        UploadedFile $file,
        ?string $oldPath = null,
        ?PageSection $pageSection = null
    ): string
    {
        $this->deleteIfLocalPageSectionImage($oldPath);

        $filename = Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());

        return $file->storeAs($this->folderFor($pageSection), $filename, 'public');
    }

    public function storeMediaUpload(
        PageSection $pageSection,
        UploadedFile $file,
        int $sortOrder = 0
    ): PageSectionMedia {
        $filename = Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs($this->folderFor($pageSection), $filename, 'public');

        return $pageSection->media()->create([
            'path' => $path,
            'alt' => $pageSection->title,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    public function deleteIfLocalPageSectionImage(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'page-sections/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    public function deleteMedia(PageSectionMedia $media): void
    {
        $this->deleteIfLocalPageSectionImage($media->path);
        $media->delete();
    }

    public function folderFor(?PageSection $pageSection): string
    {
        if (! $pageSection) {
            return 'page-sections/general';
        }

        $pageKey = Str::slug($pageSection->page_key);
        $sectionKey = $pageSection->section_key;

        if (str_starts_with($sectionKey, $pageSection->page_key . '.')) {
            $sectionKey = Str::after($sectionKey, $pageSection->page_key . '.');
        }

        $sectionPath = collect(explode('.', $sectionKey))
            ->map(fn ($part) => Str::slug($part))
            ->filter()
            ->implode('/');

        return trim('page-sections/' . $pageKey . '/' . $sectionPath, '/');
    }
}
