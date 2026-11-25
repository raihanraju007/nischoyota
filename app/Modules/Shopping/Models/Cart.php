<?php

namespace App\Modules\Shopping\Models;

use App\Modules\Account\Models\User;
use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A signed-in user's cart (user_id) or a guest's cart (guest_token from the session) (F-021).
 */
#[Fillable(['user_id', 'guest_token', 'coupon_code'])]
#[UseFactory(CartFactory::class)]
class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory, MassPrunable;

    public const GUEST_CART_LIFETIME_DAYS = 30;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Guest carts untouched for GUEST_CART_LIFETIME_DAYS; their session has long expired.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->whereNull('user_id')
            ->where('updated_at', '<', now()->subDays(self::GUEST_CART_LIFETIME_DAYS));
    }
}
