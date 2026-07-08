<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class MediaService
{
    private const OPTIMIZABLE = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

    private const DIRECT_REFERENCES = [
        'pages' => ['og_image'],
        'page_templates' => ['preview_image'],
        'products' => ['thumbnail', 'og_image'],
        'product_images' => ['image'],
        'destinations' => ['image'],
        'page_sections' => ['image', 'mobile_image'],
        'page_section_media' => ['path'],
        'site_assets' => ['path'],
        'site_settings' => ['value'],
    ];

    public function __construct(
        protected ImageOptimizationService $imageService,
    ) {}

    public function store(UploadedFile $file, ?User $uploader = null, ?string $collection = null): Media
    {
        $this->validateUpload($file);

        $collection = $this->resolveCollection($collection);
        $folder = 'media/'.$collection.'/'.now()->format('Y/m');
        $originalName = $file->getClientOriginalName();
        $mime = (string) $file->getMimeType();
        $path = null;

        try {
            if (in_array($mime, self::OPTIMIZABLE, true)) {
                $path = $this->imageService->upload($file, $folder);
                $extension = 'webp';
                $mimeType = 'image/webp';
            } else {
                // Preserve animated GIF frames instead of passing them through GD.
                $filename = Str::uuid().'.gif';
                $path = $file->storeAs($folder, $filename, 'public');
                $extension = 'gif';
                $mimeType = 'image/gif';
            }

            if (! is_string($path) || $path === '') {
                throw ValidationException::withMessages([
                    'file' => 'The image could not be stored.',
                ]);
            }

            [$width, $height] = $this->dimensions($path);

            return Media::create([
                'filename' => basename($path),
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'extension' => $extension,
                'size' => $this->fileSize($path),
                'width' => $width,
                'height' => $height,
                'path' => $path,
                'disk' => 'public',
                'collection' => $collection,
                'uploaded_by' => $uploader?->id,
            ]);
        } catch (Throwable $exception) {
            if (is_string($path) && $path !== '') {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }
    }

    public function delete(Media $media): void
    {
        $references = $this->references($media);

        if ($references !== []) {
            throw ValidationException::withMessages([
                'media' => 'This media is still used by '.count($references).' content location(s). Remove those references before deleting it.',
            ]);
        }

        $media->delete();
        Storage::disk($media->disk ?: 'public')->delete($media->path);
    }

    public function updateMeta(Media $media, array $data): Media
    {
        $media->update([
            'alt' => $data['alt'] ?? null,
            'caption' => $data['caption'] ?? null,
        ]);

        return $media;
    }

    public function list(array $filters = [], int $perPage = 24): LengthAwarePaginator
    {
        $paginator = Media::query()
            ->with('uploader:id,name')
            ->search($filters['search'] ?? null)
            ->extension($filters['extension'] ?? null)
            ->collection($filters['collection'] ?? null)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $usage = $this->usageMap($paginator->items());

        foreach ($paginator->items() as $media) {
            $references = $usage[$this->normalizePath($media->path)] ?? [];
            $media->setAttribute('usage_count', count($references));
            $media->setAttribute('usage_references', $references);
            $media->setAttribute('missing_file', ! Storage::disk($media->disk ?: 'public')->exists($media->path));
        }

        return $paginator;
    }

    public function references(Media $media): array
    {
        $path = $this->normalizePath($media->path);

        return $this->usageMap([$media])[$path] ?? [];
    }

    public function orphanedFiles(): array
    {
        $disk = Storage::disk('public');
        $files = array_values(array_filter(
            $disk->allFiles('media'),
            fn (string $path): bool => $this->normalizePath($path) !== ''
        ));
        $registered = Media::query()->pluck('path')
            ->map(fn (string $path): string => $this->normalizePath($path))
            ->flip();
        $usage = $this->usageMapForPaths($files);

        return array_values(array_filter(
            $files,
            fn (string $path): bool => ! $registered->has($this->normalizePath($path))
                && empty($usage[$this->normalizePath($path)])
        ));
    }

    public function purgeOrphanedFiles(): int
    {
        $orphans = $this->orphanedFiles();

        if ($orphans === []) {
            return 0;
        }

        Storage::disk('public')->delete($orphans);

        return count($orphans);
    }

    public function availableExtensions(): array
    {
        return Media::query()
            ->select('extension')
            ->distinct()
            ->orderBy('extension')
            ->pluck('extension')
            ->all();
    }

    /**
     * Collections drive the storage folder (media/{collection}/YYYY/MM).
     * Unknown values fall back to the configured default so a stale client
     * can never write outside the media tree.
     */
    private function resolveCollection(?string $collection): string
    {
        $collection = trim((string) $collection);
        $known = array_keys((array) config('media.collections', []));

        if ($collection !== '' && in_array($collection, $known, true)) {
            return $collection;
        }

        return (string) config('media.default_collection', 'general');
    }

    private function validateUpload(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)
            || ! in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
            throw ValidationException::withMessages([
                'file' => 'Only valid JPG, JPEG, PNG, GIF, and WebP images are allowed.',
            ]);
        }
    }

    private function usageMap(iterable $media): array
    {
        $paths = collect($media)
            ->pluck('path')
            ->filter(fn (mixed $path): bool => is_string($path) && $path !== '')
            ->all();

        return $this->usageMapForPaths($paths);
    }

    private function usageMapForPaths(array $paths): array
    {
        $normalizedPaths = collect($paths)
            ->map(fn (string $path): string => $this->normalizePath($path))
            ->filter()
            ->unique()
            ->values();
        $usage = $normalizedPaths->mapWithKeys(fn (string $path): array => [$path => []])->all();

        if ($normalizedPaths->isEmpty()) {
            return $usage;
        }

        foreach (self::DIRECT_REFERENCES as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $pathCandidates = $normalizedPaths->flatMap(fn (string $path): array => [
                    $path,
                    'storage/'.$path,
                    '/storage/'.$path,
                    asset('storage/'.$path),
                ])->unique()->values();

                DB::table($table)
                    ->select(['id', $column])
                    ->whereIn($column, $pathCandidates)
                    ->get()
                    ->each(function (object $record) use (&$usage, $table, $column): void {
                        $path = $this->normalizePath((string) $record->{$column});
                        $usage[$path][] = [
                            'source' => $table,
                            'field' => $column,
                            'record_id' => (int) $record->id,
                            'label' => Str::headline(Str::singular($table)).' #'.$record->id,
                        ];
                    });
            }
        }

        if (Schema::hasTable('page_blocks')) {
            DB::table('page_blocks')
                ->select(['id', 'page_id', 'label', 'data'])
                ->get()
                ->each(function (object $block) use (&$usage): void {
                    $data = is_string($block->data) ? json_decode($block->data, true) : $block->data;

                    $this->findBlockReferences(
                        is_array($data) ? $data : [],
                        array_keys($usage),
                        function (string $path, string $field) use (&$usage, $block): void {
                            $usage[$path][] = [
                                'source' => 'page_blocks',
                                'field' => $field,
                                'record_id' => (int) $block->id,
                                'label' => 'Page block '.($block->label ?: '#'.$block->id).' on page #'.$block->page_id,
                            ];
                        }
                    );
                });
        }

        return $usage;
    }

    private function findBlockReferences(array $data, array $paths, callable $found, string $prefix = 'data'): void
    {
        foreach ($data as $key => $value) {
            $field = $prefix.'.'.$key;

            if (is_array($value)) {
                $this->findBlockReferences($value, $paths, $found, $field);

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            $path = $this->normalizePath($value);
            if (in_array($path, $paths, true)) {
                $found($path, $field);
            }
        }
    }

    private function normalizePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        $storageUrl = asset('storage/');

        if (str_starts_with($path, $storageUrl)) {
            $path = substr($path, strlen($storageUrl));
        } elseif (filter_var($path, FILTER_VALIDATE_URL)) {
            $urlPath = (string) parse_url($path, PHP_URL_PATH);
            if (str_starts_with($urlPath, '/storage/')) {
                $path = $urlPath;
            }
        }

        return ltrim(preg_replace('#^/?storage/#', '', $path) ?? $path, '/');
    }

    private function dimensions(string $path): array
    {
        $full = storage_path('app/public/'.$path);

        if (! is_file($full)) {
            return [null, null];
        }

        $info = @getimagesize($full);

        return $info ? [$info[0], $info[1]] : [null, null];
    }

    private function fileSize(string $path): int
    {
        $full = storage_path('app/public/'.$path);

        return is_file($full) ? (int) filesize($full) : 0;
    }
}
