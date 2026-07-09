<?php

namespace App\Models;

use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** @property 'draft'|'published'|'scheduled' $status */
class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'locale',
        'translation_group_id',
        'template_id',
        'status',
        'publish_at',
        'meta_title',
        'meta_description',
        'og_image',
        'seo_robots',
        'sort_order',
    ];

    protected static function booted(): void
    {
        // Every page belongs to a translation group and a locale (Phase 7 — B4).
        static::creating(function (Page $page): void {
            if (blank($page->locale)) {
                $page->locale = Locales::default();
            }

            if (blank($page->translation_group_id)) {
                $page->translation_group_id = (string) Str::ulid();
            }
        });
    }

    protected $casts = [
        'sort_order'  => 'integer',
        'template_id' => 'integer',
        'publish_at'  => 'datetime',
    ];

    /** @return HasMany<PageBlock, $this> */
    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('sort_order');
    }

    /** @return HasMany<PageBlock, $this> */
    public function rootBlocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->whereNull('parent_block_id')->orderBy('sort_order');
    }

    /** @return HasMany<PageRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class)->orderByDesc('revision_number');
    }

    /**
     * All pages in this page's translation group (including itself), one per locale.
     *
     * @return HasMany<Page, $this>
     */
    public function translationSiblings(): HasMany
    {
        return $this->hasMany(Page::class, 'translation_group_id', 'translation_group_id');
    }

    /** The sibling page for a given locale, or null if that locale is untranslated. */
    public function translationIn(string $locale): ?Page
    {
        if ($locale === $this->locale) {
            return $this;
        }

        return $this->translationSiblings()->where('locale', $locale)->first();
    }

    /** @return BelongsTo<PageTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(PageTemplate::class, 'template_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    /**
     * Public URL respecting this page's own locale (default locale = unprefixed,
     * others under /{locale}/pages/...).
     */
    public function publicUrl(): string
    {
        return $this->locale === Locales::default()
            ? route('pages.show', $this->slug)
            : route($this->locale.'.pages.show', $this->slug);
    }

    public function getUrlAttribute(): string
    {
        return $this->publicUrl();
    }

    /**
     * Resolve the PUBLIC {page:slug} binding within the current locale so
     * /pages/{slug} serves the default-locale page and /{locale}/pages/{slug}
     * serves that locale's page (404 when it has no translation). Admin id-based
     * bindings ({page}) resolve normally, untouched.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if (($field ?? $this->getRouteKeyName()) === 'slug') {
            // Use the URL-derived locale, not app()->getLocale(): binding is
            // substituted before the SetLocale middleware runs.
            return $this->where('slug', $value)
                ->where('locale', Locales::localeFromRequest())
                ->first();
        }

        return parent::resolveRouteBinding($value, $field);
    }
}
