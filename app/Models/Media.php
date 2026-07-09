<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'filename',
        'original_name',
        'mime_type',
        'extension',
        'size',
        'width',
        'height',
        'path',
        'disk',
        'collection',
        'alt',
        'caption',
        'uploaded_by',
    ];

    protected $casts = [
        'size'   => 'integer',
        'width'  => 'integer',
        'height' => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        // Use asset() (request-host aware) to match the rest of the app and avoid
        // the APP_URL-locked Storage::url(), which breaks previews on other hosts.
        return asset('storage/' . ltrim($this->path, '/'));
    }

    public function getSizeForHumansAttribute(): string
    {
        $bytes = (int) $this->size;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024) . ' KB';
        }

        return $bytes . ' B';
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function scopeImages(Builder $query): Builder
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where('original_name', 'like', '%' . $term . '%');
    }

    public function scopeExtension(Builder $query, ?string $extension): Builder
    {
        $extension = trim((string) $extension);

        if ($extension === '') {
            return $query;
        }

        return $query->where('extension', $extension);
    }

    public function scopeCollection(Builder $query, ?string $collection): Builder
    {
        $collection = trim((string) $collection);

        if ($collection === '') {
            return $query;
        }

        // Legacy uploads predate collections and carry NULL.
        if ($collection === 'uncategorized') {
            return $query->whereNull('collection');
        }

        return $query->where('collection', $collection);
    }

    public function collectionLabel(): string
    {
        if ($this->collection === null) {
            return 'Uncategorized';
        }

        return config('media.collections')[$this->collection] ?? ucfirst($this->collection);
    }
}
