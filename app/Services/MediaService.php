<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /** Mime types the ImageOptimizationService can convert to webp. */
    private const OPTIMIZABLE = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

    public function __construct(
        protected ImageOptimizationService $imageService,
    ) {}

    /**
     * Optimize/store an upload and register a Media record.
     * jpeg/png/webp are routed through ImageOptimizationService (→ webp);
     * gif is stored as-is (GD pipeline can't process it).
     */
    public function store(UploadedFile $file, ?User $uploader = null): Media
    {
        $folder       = 'media/' . now()->format('Y/m');
        $originalName = $file->getClientOriginalName();
        $mime         = (string) $file->getMimeType();

        if (in_array($mime, self::OPTIMIZABLE, true)) {
            $path      = $this->imageService->upload($file, $folder); // folder/uuid.webp
            $extension = 'webp';
            $mimeType  = 'image/webp';
        } else {
            // gif (validated upstream) — keep original, hashed filename.
            $filename  = Str::uuid() . '.gif';
            $path      = $file->storeAs($folder, $filename, 'public');
            $extension = 'gif';
            $mimeType  = 'image/gif';
        }

        [$width, $height] = $this->dimensions($path);

        return Media::create([
            'filename'      => basename($path),
            'original_name' => $originalName,
            'mime_type'     => $mimeType,
            'extension'     => $extension,
            'size'          => $this->fileSize($path),
            'width'         => $width,
            'height'        => $height,
            'path'          => $path,
            'disk'          => 'public',
            'uploaded_by'   => $uploader?->id,
        ]);
    }

    public function delete(Media $media): void
    {
        Storage::disk($media->disk ?: 'public')->delete($media->path);

        $media->delete();
    }

    public function updateMeta(Media $media, array $data): Media
    {
        $media->update([
            'alt'     => $data['alt'] ?? null,
            'caption' => $data['caption'] ?? null,
        ]);

        return $media;
    }

    public function list(array $filters = [], int $perPage = 24): LengthAwarePaginator
    {
        return Media::query()
            ->with('uploader:id,name')
            ->search($filters['search'] ?? null)
            ->extension($filters['extension'] ?? null)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Distinct extensions present, for the type filter chips. */
    public function availableExtensions(): array
    {
        return Media::query()
            ->select('extension')
            ->distinct()
            ->orderBy('extension')
            ->pluck('extension')
            ->all();
    }

    private function dimensions(string $path): array
    {
        $full = storage_path('app/public/' . $path);

        if (! is_file($full)) {
            return [null, null];
        }

        $info = @getimagesize($full);

        return $info ? [$info[0], $info[1]] : [null, null];
    }

    private function fileSize(string $path): int
    {
        $full = storage_path('app/public/' . $path);

        return is_file($full) ? (int) filesize($full) : 0;
    }
}
