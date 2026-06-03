<?php

namespace App\Services;

use App\Models\PageSection;
use App\Models\PageSectionMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PageSectionImageService
{
    public function storeUploadedImage(UploadedFile $file, PageSection $section, ?string $oldPath = null): string
    {
        $this->deleteIfLocalPageSectionImage($oldPath);

        return $this->storeFile($file, $this->folderFor($section));
    }

    public function storeMediaUpload(UploadedFile $file, PageSection $section, int $sortOrder = 0): PageSectionMedia
    {
        return $section->media()->create([
            'path' => $this->storeFile($file, $this->folderFor($section)),
            'alt' => $section->title,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    public function deleteMedia(PageSectionMedia $media): void
    {
        $this->deleteIfLocalPageSectionImage($media->path);
        $media->delete();
    }

    public function deleteIfLocalPageSectionImage(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'page-sections/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    public function folderFor(PageSection $section): string
    {
        return 'page-sections/'
            . Str::slug($section->page_key)
            . '/'
            . Str::slug(str_replace($section->page_key . '.', '', $section->section_key));
    }

    protected function storeFile(UploadedFile $file, string $folder): string
    {
        $extension = strtolower($file->extension() ?: $file->guessExtension() ?: 'jpg');

        return $file->storeAs($folder, Str::uuid() . '.' . $extension, 'public');
    }
}
