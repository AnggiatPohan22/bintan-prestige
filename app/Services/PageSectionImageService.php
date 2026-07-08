<?php

namespace App\Services;

use App\Models\PageSection;
use App\Models\PageSectionMedia;
use App\Models\SiteAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PageSectionImageService
{
    private const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'ico',
        'svg',
    ];

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

    /**
     * Point a site asset at an existing Media Library path (selected via the
     * picker) instead of uploading a new file. Mirrors storeSiteAssetUpload but
     * stores the given path as-is. A previous *local* upload (site-assets/…) is
     * cleaned up; Media Library paths (media/…) are left intact — they belong to
     * the library and are usage-tracked there.
     */
    public function setSiteAssetPath(string $path, string $key, string $label, ?string $alt = null): SiteAsset
    {
        $asset = SiteAsset::firstOrNew(['key' => $key]);

        if ($asset->path !== $path) {
            $this->deleteIfLocalSiteAssetImage($asset->path);
        }

        $asset->fill([
            'label' => $label,
            'path' => $path,
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
        $extension = $this->safeExtension($file);

        return $file->storeAs($folder, Str::uuid() . '.' . $extension, 'public');
    }

    protected function safeExtension(UploadedFile $file): string
    {
        $clientExtension = strtolower($file->getClientOriginalExtension() ?: '');
        $extension = strtolower($file->extension() ?: $file->guessExtension() ?: $clientExtension);

        if (
            ($clientExtension !== '' && ! in_array($clientExtension, self::ALLOWED_EXTENSIONS, true))
            || ! in_array($extension, self::ALLOWED_EXTENSIONS, true)
        ) {
            throw ValidationException::withMessages([
                'file' => 'Unsupported upload extension.',
            ]);
        }

        return $extension;
    }

    private function supportsMediaDisplayOptions(): bool
    {
        return Schema::hasColumn('page_section_media', 'object_fit')
            && Schema::hasColumn('page_section_media', 'object_position');
    }
}
