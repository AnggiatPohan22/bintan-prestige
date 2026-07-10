<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFaq extends Model
{
    use Translatable;

    /** @var list<string> Phase 7 (B6.1) — sidecar-translated Q&A. */
    protected array $translatable = ['question', 'answer'];

    protected $fillable = [
        'product_id',
        'question',
        'answer',
        'sort_order',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class
        );
    }

    public function getQuestionAttribute(): ?string { return $this->translate('question'); }
    public function getAnswerAttribute(): ?string   { return $this->translate('answer'); }
}
