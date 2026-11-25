<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Account\Models\User;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Enums\StockMovementReason;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One stock change. Append-only: rows are never updated (no updated_at).
 */
#[Fillable(['product_variant_id', 'quantity_change', 'stock_after', 'reason', 'order_id', 'user_id', 'note', 'created_at'])]
#[UseFactory(StockMovementFactory::class)]
#[WithoutTimestamps]
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_change' => 'integer',
            'stock_after' => 'integer',
            'reason' => StockMovementReason::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
