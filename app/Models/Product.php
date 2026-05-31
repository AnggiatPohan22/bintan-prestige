<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;


class Product extends Model
{
    use HasFactory;

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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)
            ->withTrashed();
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class)
            ->withTrashed();
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

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

    public function highlights(): HasMany
    {
        return $this->hasMany(
            ProductHighlight::class
        )->orderBy('sort_order');
    }

    public function features(): HasMany
    {
        return $this->hasMany(
            ProductFeature::class
        )->orderBy('sort_order');
    }

    public function includedFeatures(): HasMany
    {
        return $this->hasMany(
            ProductFeature::class
        )
        ->where('label', 'included')
        ->orderBy('sort_order');
    }

    public function excludedFeatures(): HasMany
    {
        return $this->hasMany(
            ProductFeature::class
        )
        ->where('label', 'excluded')
        ->orderBy('sort_order');
    }

    public function itineraries(): HasMany
    {
        return $this->hasMany(
            ProductItinerary::class
        )->orderBy('sort_order')
        ->orderBy('start_time');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(
            ProductFaq::class
        )->orderBy('sort_order');
    }

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
}
