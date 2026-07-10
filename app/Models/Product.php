<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;


class Product extends Model
{
    use HasFactory;
    use Translatable;

    /**
     * Copy columns translated per locale via the translations sidecar (Phase 7 —
     * B6). The base columns keep the default-locale copy; prices, images, slugs,
     * relations (category/destination), and status stay shared across locales.
     *
     * @var list<string>
     */
    protected array $translatable = [
        'name',
        'short_description',
        'description',
        'meeting_point',
        'duration',
        'pickup_note',
        'cta_title',
        'cta_description',
        'cta_button_text',
        'meta_title',
        'meta_description',
    ];

    protected static function booted(): void
    {
        static::creating(function ($product) {

            if (!$product->slug) {

                $product->slug =
                    Str::slug($product->name)
                    . '-'
                    . uniqid();
            }
        });
    }

    protected $fillable = [
        'category_id',
        'destination_id',
        'name',
        'slug',
        'thumbnail',
        'short_description',
        'description',
        'meeting_point',
        'duration',
        'whatsapp_number',
        'is_featured',
        'status',

        'pickup_available',
        'pickup_type',
        'pickup_note',

        'cta_title',
        'cta_description',
        'cta_button_text',

        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'og_image',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)
            ->withTrashed();
    }

    /** @return BelongsTo<Destination, $this> */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class)
            ->withTrashed();
    }

    /** @return HasMany<ProductImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /** @return HasMany<ProductPrice, $this> */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /** @return HasMany<BookingItem, $this> */
    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->whereHas('category',
            fn ($q) => $q->where('name', $category)
        );
    }

    // Accessors
    public function getIdrPriceAttribute()
    {
        return $this->prices    
            ->where('currency', 'IDR')
            ->first()?->price;
    }

    public function getSgdPriceAttribute()
    {
        return $this->prices
            ->where('currency', 'SGD')
            ->first()?->price;
    }

    public function getThumbnailUrlAttribute()
    {
        if (! $this->thumbnail) {
            return null;
        }

        return asset('storage/' . $this->thumbnail);
    }

    public function getOgImageUrlAttribute()
    {
        if (! $this->og_image) {
            return $this->thumbnail_url;
        }

        return asset('storage/' . $this->og_image);
    }
    // end of accessors

    /** @return HasMany<ProductHighlight, $this> */
    public function highlights(): HasMany
    {
        return $this->hasMany(
            ProductHighlight::class
        )->orderBy('sort_order');
    }

    /** @return HasMany<ProductFeature, $this> */
    public function features(): HasMany
    {
        return $this->hasMany(
            ProductFeature::class
        )->orderBy('sort_order');
    }

    /** @return HasMany<ProductFeature, $this> */
    public function includedFeatures(): HasMany
    {
        return $this->hasMany(
            ProductFeature::class
        )
        ->where('label', 'included')
        ->orderBy('sort_order');
    }

    /** @return HasMany<ProductFeature, $this> */
    public function excludedFeatures(): HasMany
    {
        return $this->hasMany(
            ProductFeature::class
        )
        ->where('label', 'excluded')
        ->orderBy('sort_order');
    }

    /** @return HasMany<ProductItinerary, $this> */
    public function itineraries(): HasMany
    {
        return $this->hasMany(
            ProductItinerary::class
        )->orderBy('sort_order')
        ->orderBy('start_time');
    }

    /** @return HasMany<ProductFaq, $this> */
    public function faqs(): HasMany
    {
        return $this->hasMany(
            ProductFaq::class
        )->orderBy('sort_order');
    }

    /** @return HasMany<ProductNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(
            ProductNote::class
        )->orderBy('sort_order');
    }

    // Accessors for categorized features
    public function getIncludedFeaturesAttribute()
    {
        return $this->features
            ->where('label', 'included')
            ->values();
    }

    public function getExcludedFeaturesAttribute()
    {
        return $this->features
            ->where('label', 'excluded')
            ->values();
    }

    public function getOptionalFeaturesAttribute()
    {
        return $this->features
            ->where('label', 'optional')
            ->values();
    }

    public function getAddonFeaturesAttribute()
    {
        return $this->features
            ->where('label', 'addon')
            ->values();
    }

    public function getImportantFeaturesAttribute()
    {
        return $this->features
            ->where('label', 'important')
            ->values();
    }
    // End of categorized features accessors

    // Scopes Published products
    public function scopePublished($query)
    {
        return $query->where(
            'status',
            'published'
        );
    }
    // End of scopes published products

    // Scope public listing visibility without changing admin queries
    public function scopePubliclyVisible($query)
    {
        return $query
            ->published()
            ->whereHas('category', function ($categoryQuery) {
                $categoryQuery
                    ->whereNull('categories.deleted_at')
                    ->where('is_active', true);
            })
            ->whereHas('destination', function ($destinationQuery) {
                $destinationQuery
                    ->whereNull('destinations.deleted_at')
                    ->where('is_active', true);
            });
    }
    // End of scope public listing visibility

    // Scope for frontend listing cards with only relations used by the card
    public function scopeFrontendListingReady($query)
    {
        return $query->with([
            'category',
            'destination',
            'prices',
            'images',
        ]);
    }
    // End of scope for frontend listing card eager loading

    // Scope for frontend ready products with eager loading
    public function scopeFrontendReady($query)
    {
        return $query->with([
            'category',
            'destination',
            'prices',
            'images',
            'highlights',
            'features',
            'faqs',
            'itineraries',
            'notes',
        ]);
    }
    // End of scope for frontend ready products with eager loading

    // ------------------------------------------------------------- i18n accessors (Phase 7 — B6)
    // Reading $product->name (etc.) transparently returns the current locale's
    // value with fallback to the base column. Default locale short-circuits to
    // the base column (zero query overhead).
    public function getNameAttribute(): ?string             { return $this->translate('name'); }
    public function getShortDescriptionAttribute(): ?string { return $this->translate('short_description'); }
    public function getDescriptionAttribute(): ?string      { return $this->translate('description'); }
    public function getMeetingPointAttribute(): ?string     { return $this->translate('meeting_point'); }
    public function getPickupNoteAttribute(): ?string       { return $this->translate('pickup_note'); }
    public function getCtaTitleAttribute(): ?string         { return $this->translate('cta_title'); }
    public function getCtaDescriptionAttribute(): ?string   { return $this->translate('cta_description'); }
    public function getCtaButtonTextAttribute(): ?string    { return $this->translate('cta_button_text'); }
    public function getMetaTitleAttribute(): ?string        { return $this->translate('meta_title'); }
    public function getMetaDescriptionAttribute(): ?string  { return $this->translate('meta_description'); }
    public function getDurationAttribute(): ?string         { return $this->translate('duration'); }
}
