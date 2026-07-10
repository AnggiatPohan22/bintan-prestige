<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductItinerary extends Model
{
    use Translatable;

    /**
     * @var list<string> Phase 7 (B6.1). `time` is a free-form label
     * ("Morning" / "Pagi"). `start_time` stays shared — it is a real clock
     * value.
     */
    protected array $translatable = ['time', 'title', 'description'];

    protected $fillable = [
        'product_id',
        'time',
        'title',
        'description',
        'start_time',
        'sort_order',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class
        );
    }

    public function getTimeAttribute(): ?string        { return $this->translate('time'); }
    public function getTitleAttribute(): ?string       { return $this->translate('title'); }
    public function getDescriptionAttribute(): ?string { return $this->translate('description'); }
}
