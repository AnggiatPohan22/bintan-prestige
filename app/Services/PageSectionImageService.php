<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PageSectionImageService
{
    public function storeUploadedImage(UploadedFile $file, ?string $oldPath = null): string
    {
        $this->deleteIfLocalPageSectionImage($oldPath);

        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . strtolower($extension);

        return $file->storeAs('page-sections', $filename, 'public');
    }

    public function deleteIfLocalPageSectionImage(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'page-sections/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
