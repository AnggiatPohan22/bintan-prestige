<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentEntry extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ARCHIVED  = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_SCHEDULED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'content_type_id',
        'title',
        'slug',
        'excerpt',
        'status',
        'published_at',
        'author_id',
        'template',
        'sort_order',
        'data',
        'seo',
    ];

    protected $casts = [
        'published_at'    => 'datetime',
        'data'            => 'array',
        'seo'             => 'array',
        'sort_order'      => 'integer',
        'content_type_id' => 'integer',
        'author_id'       => 'integer',
    ];

    /** @return BelongsTo<ContentType, $this> */
    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // ---------------------------------------------------------------- field value access

    /** Read a custom field value from the JSON data bag. */
    public function fieldValue(string $key): mixed
    {
        return ($this->data ?? [])[$key] ?? null;
    }

    /** Write a single custom field value (does not save — call save() separately). */
    public function setFieldValue(string $key, mixed $value): void
    {
        $data         = $this->data ?? [];
        $data[$key]   = $value;
        $this->data   = $data;
    }

    // ---------------------------------------------------------------- status helpers

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && ($this->published_at === null || $this->published_at->isPast());
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED
            && $this->published_at !== null
            && $this->published_at->isFuture();
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => 'admin-badge-success',
            self::STATUS_SCHEDULED => 'admin-badge-info',
            self::STATUS_ARCHIVED  => 'admin-badge-warning',
            default                => 'admin-badge-secondary',
        };
    }

    // ---------------------------------------------------------------- scopes

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->where(fn (Builder $q) => $q
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now()));
    }

    public function scopeForType(Builder $query, ContentType $type): Builder
    {
        return $query->where('content_type_id', $type->id);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('published_at');
    }
}
