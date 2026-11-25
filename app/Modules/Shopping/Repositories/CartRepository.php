<?php

namespace App\Modules\Shopping\Repositories;

use App\Modules\Shopping\Data\CartOwner;
use App\Modules\Shopping\Models\Cart;
use App\Modules\Shopping\Models\CartItem;
use Illuminate\Database\Eloquent\Builder;

final class CartRepository
{
    public function findForOwner(CartOwner $owner): ?Cart
    {
        return $this->ownedBy($owner)->first();
    }

    /**
     * Cart with every line's variant, product, category, photos and option values, oldest line first.
     */
    public function findForOwnerWithLines(CartOwner $owner): ?Cart
    {
        return $this->ownedBy($owner)
            ->with([
                'items' => fn ($query) => $query->orderBy('id'),
                'items.variant.product.category',
                'items.variant.product.coverImage',
                'items.variant.attributeValues.attribute',
                'items.variant.image',
            ])
            ->first();
    }

    public function firstOrCreateForOwner(CartOwner $owner): Cart
    {
        return $owner->user !== null
            ? Cart::query()->firstOrCreate(['user_id' => $owner->user->id])
            : Cart::query()->firstOrCreate(['guest_token' => $owner->guestToken]);
    }

    public function findGuestCartWithItems(string $guestToken): ?Cart
    {
        return Cart::query()->with('items')->whereNull('user_id')->where('guest_token', $guestToken)->first();
    }

    public function findItem(Cart $cart, int $itemId): ?CartItem
    {
        return $cart->items()->find($itemId);
    }

    public function findItemForVariant(Cart $cart, int $variantId): ?CartItem
    {
        return $cart->items()->where('product_variant_id', $variantId)->first();
    }

    public function createItem(Cart $cart, int $variantId, int $quantity, int $addedPrice): CartItem
    {
        return $cart->items()->create([
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
            'added_price' => $addedPrice,
        ]);
    }

    public function updateItemQuantity(CartItem $item, int $quantity): void
    {
        $item->update(['quantity' => $quantity]);
    }

    public function deleteItem(CartItem $item): void
    {
        $item->delete();
    }

    public function deleteItems(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->touch();
    }

    public function updateCouponCode(Cart $cart, ?string $couponCode): void
    {
        $cart->update(['coupon_code' => $couponCode]);
    }

    public function delete(Cart $cart): void
    {
        $cart->delete();
    }

    /**
     * @return Builder<Cart>
     */
    private function ownedBy(CartOwner $owner): Builder
    {
        return $owner->user !== null
            ? Cart::query()->where('user_id', $owner->user->id)
            : Cart::query()->whereNull('user_id')->where('guest_token', $owner->guestToken);
    }
}
