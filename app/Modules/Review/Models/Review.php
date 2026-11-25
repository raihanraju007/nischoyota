<?php

namespace App\Modules\Review\Models;

use App\Modules\Account\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Review\Enums\ReviewStatus;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer's review of a product (F-027). Public only when approved.
 * product and user are read-only relations to show names; Catalog and Account own those rows.
 */
#[Fillable(['product_id', 'user_id', 'rating', 'comment', 'is_verified_purchase', 'status', 'moderated_by', 'moderated_at'])]
#[UseFactory(ReviewFactory::class)]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    public const MIN_RATING = 1;

    public const MAX_RATING = 5;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_verified_purchase' => 'boolean',
            'status' => ReviewStatus::class,
            'moderated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
