<?php

namespace App\Modules\Shopping\Models;

use App\Modules\Catalog\Models\ProductVariant;
use Database\Factories\CartItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One variant in a cart. The price is always read from the variant; added_price (paisa) is kept
 * only to tell the customer that the price changed.
 */
#[Fillable(['cart_id', 'product_variant_id', 'quantity', 'added_price'])]
#[UseFactory(CartItemFactory::class)]
class CartItem extends Model
{
    /** @use HasFactory<CartItemFactory> */
    use HasFactory;

    /**
     * Changing a line counts as cart activity (guest cart pruning looks at carts.updated_at).
     *
     * @var list<string>
     */
    protected $touches = ['cart'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'added_price' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Read-only link to Catalog: the cart shows the variant's live name, price and stock.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
