<?php

namespace App\Services;

use App\Models\PageSection;
use App\Models\PageSectionMedia;
use App\Models\SiteAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
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
            'role' => 'gallery',
            'slot_key' => 'gallery',
            'label' => 'Gallery image',
            'path' => $this->storeFile($file, $this->folderFor($section)),
            'alt' => $section->title,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    public function storeSlotUpload(
        UploadedFile $file,
        PageSection $section,
        string $role,
        string $slotKey,
        string $label,
        ?string $objectFit = null,
        ?string $objectPosition = null
    ): PageSectionMedia
    {
        $media = $section->media()
            ->where('role', $role)
            ->where('slot_key', $slotKey)
            ->first();

        if ($media) {
            $this->deleteIfLocalPageSectionImage($media->path);
            $data = [
                'label' => $label,
                'path' => $this->storeFile($file, $this->folderFor($section) . '/' . Str::slug($role)),
                'alt' => $label,
                'is_active' => true,
            ];

            if ($this->supportsMediaDisplayOptions()) {
                $data['object_fit'] = $objectFit;
                $data['object_position'] = $objectPosition;
            }

            $media->update($data);

            return $media;
        }

        $data = [
            'role' => $role,
            'slot_key' => $slotKey,
            'label' => $label,
            'path' => $this->storeFile($file, $this->folderFor($section) . '/' . Str::slug($role)),
            'alt' => $label,
            'sort_order' => 0,
            'is_active' => true,
        ];

        if ($this->supportsMediaDisplayOptions()) {
            $data['object_fit'] = $objectFit;
            $data['object_position'] = $objectPosition;
        }

        return $section->media()->create($data);
    }

    public function storeSiteAssetUpload(UploadedFile $file, string $key, string $label, ?string $alt = null): SiteAsset
    {
        $asset = SiteAsset::firstOrNew(['key' => $key]);

        $this->deleteIfLocalSiteAssetImage($asset->path);

        $asset->fill([
            'label' => $label,
            'path' => $this->storeFile($file, 'site-assets/' . Str::slug($key)),
            'alt' => $alt ?: $label,
            'is_active' => true,
        ])->save();

        return $asset;
    }

    public function clearSiteAsset(SiteAsset $asset): void
    {
        $this->deleteIfLocalSiteAssetImage($asset->path);

        $asset->update([
            'path' => null,
            'is_active' => false,
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

    public function deleteIfLocalSiteAssetImage(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'site-assets/')) {
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

    private function supportsMediaDisplayOptions(): bool
    {
        return Schema::hasColumn('page_section_media', 'object_fit')
            && Schema::hasColumn('page_section_media', 'object_position');
    }
}
