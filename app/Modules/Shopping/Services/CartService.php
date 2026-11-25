<?php

namespace App\Modules\Shopping\Services;

use App\Modules\Account\Models\User;
use App\Modules\Account\Services\AddressService;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Services\ProductVariantService;
use App\Modules\Location\Data\DeliveryQuote;
use App\Modules\Location\Services\DeliveryChargeService;
use App\Modules\Promotion\Data\CouponDiscount;
use App\Modules\Promotion\Exceptions\CouponNotApplicableException;
use App\Modules\Promotion\Services\CouponService;
use App\Modules\Shopping\Data\CartContents;
use App\Modules\Shopping\Data\CartLine;
use App\Modules\Shopping\Data\CartOwner;
use App\Modules\Shopping\Data\CartTotals;
use App\Modules\Shopping\Enums\CartLineState;
use App\Modules\Shopping\Exceptions\CartException;
use App\Modules\Shopping\Models\Cart;
use App\Modules\Shopping\Models\CartItem;
use App\Modules\Shopping\Repositories\CartRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Cart rules (F-021): one line per variant, live prices, stock checked on every change.
 */
final class CartService
{
    public const MAX_LINE_QUANTITY = 99;

    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly ProductVariantService $variantService,
        private readonly CouponService $couponService,
        private readonly DeliveryChargeService $deliveryChargeService,
        private readonly AddressService $addressService,
    ) {}

    /**
     * A guest without a cart token has nothing in the cart; no row is created for them.
     */
    public function contentsFor(?CartOwner $owner): CartContents
    {
        $cart = $owner === null ? null : $this->cartRepository->findForOwnerWithLines($owner);

        if ($cart === null) {
            return CartContents::empty();
        }

        return new CartContents(
            lines: $cart->items->map($this->lineFor(...))->values()->all(),
            couponCode: $cart->coupon_code,
        );
    }

    /**
     * Adds to the line of the same variant when there is one.
     *
     * @throws CartException
     */
    public function add(CartOwner $owner, int $variantId, int $quantity): void
    {
        $variant = $this->sellableVariant($variantId);

        DB::transaction(function () use ($owner, $variant, $quantity): void {
            $cart = $this->cartRepository->firstOrCreateForOwner($owner);
            $item = $this->cartRepository->findItemForVariant($cart, $variant->id);
            $newQuantity = ($item?->quantity ?? 0) + $quantity;

            $this->ensureCanHold($variant, $newQuantity);

            $item === null
                ? $this->cartRepository->createItem($cart, $variant->id, $newQuantity, $variant->price)
                : $this->cartRepository->updateItemQuantity($item, $newQuantity);
        });
    }

    /**
     * @throws CartException
     * @throws ModelNotFoundException when the line is not in the owner's cart
     */
    public function updateQuantity(CartOwner $owner, int $itemId, int $quantity): void
    {
        $item = $this->ownedItem($owner, $itemId);
        $variant = $this->sellableVariant($item->product_variant_id);

        $this->ensureCanHold($variant, $quantity);

        $this->cartRepository->updateItemQuantity($item, $quantity);
    }

    /**
     * @throws ModelNotFoundException when the line is not in the owner's cart
     */
    public function remove(CartOwner $owner, int $itemId): void
    {
        $this->cartRepository->deleteItem($this->ownedItem($owner, $itemId));
    }

    /**
     * Removes every line and the coupon.
     */
    public function clear(?CartOwner $owner): void
    {
        $cart = $owner === null ? null : $this->cartRepository->findForOwner($owner);

        if ($cart === null) {
            return;
        }

        DB::transaction(function () use ($cart): void {
            $this->cartRepository->deleteItems($cart);
            $this->cartRepository->updateCouponCode($cart, null);
        });
    }

    /**
     * Saves the code on the cart when it applies to the current subtotal. The per-customer
     * usage limit is checked at checkout (F-023).
     *
     * @throws CouponNotApplicableException
     */
    public function applyCoupon(CartOwner $owner, string $code): CouponDiscount
    {
        $cart = $this->cartRepository->findForOwner($owner) ?? throw CouponNotApplicableException::invalidCode();
        $discount = $this->couponService->apply($code, $this->contentsFor($owner)->subtotal());

        $this->cartRepository->updateCouponCode($cart, $discount->code);

        return $discount;
    }

    public function removeCoupon(CartOwner $owner): void
    {
        $cart = $this->cartRepository->findForOwner($owner);

        if ($cart !== null) {
            $this->cartRepository->updateCouponCode($cart, null);
        }
    }

    /**
     * Moves a guest's lines into the user's cart on sign-in. Same variant: quantities summed, capped
     * at MAX_LINE_QUANTITY (stock is checked again when the cart is read). The guest's coupon is kept
     * when the user's cart has none.
     */
    public function mergeGuestCart(string $guestToken, User $user): void
    {
        $guestCart = $this->cartRepository->findGuestCartWithItems($guestToken);

        if ($guestCart === null) {
            return;
        }

        DB::transaction(function () use ($guestCart, $user): void {
            $userCart = $this->cartRepository->firstOrCreateForOwner(CartOwner::user($user));

            foreach ($guestCart->items as $guestItem) {
                $this->mergeItem($userCart, $guestItem);
            }

            if ($userCart->coupon_code === null && $guestCart->coupon_code !== null) {
                $this->cartRepository->updateCouponCode($userCart, $guestCart->coupon_code);
            }

            $this->cartRepository->delete($guestCart);
        });
    }

    /**
     * Order summary for the cart page: coupon re-checked, delivery from the default address.
     */
    public function totalsFor(CartContents $contents, ?User $user): CartTotals
    {
        $subtotal = $contents->subtotal();
        [$discount, $couponError] = $this->couponPreview($contents->couponCode, $subtotal);

        return new CartTotals(
            subtotal: $subtotal,
            discount: $discount,
            couponCode: $contents->couponCode,
            couponError: $couponError,
            delivery: $user === null ? null : $this->deliveryQuoteFor($user, $subtotal),
            amountToFreeDelivery: $this->amountToFreeDelivery($subtotal),
        );
    }

    /**
     * Unavailable first, then enough stock, then pre-order, then sold out or too few left.
     */
    public function lineStateFor(ProductVariant $variant, int $quantity): CartLineState
    {
        return match (true) {
            ! $variant->isSellable() => CartLineState::Unavailable,
            $variant->stock_quantity >= $quantity => CartLineState::Available,
            $variant->product->allow_preorder => CartLineState::Preorder,
            $variant->stock_quantity <= 0 => CartLineState::OutOfStock,
            default => CartLineState::NotEnoughStock,
        };
    }

    private function lineFor(CartItem $item): CartLine
    {
        $variant = $item->variant;
        $product = $variant->product;

        return new CartLine(
            itemId: $item->id,
            variantId: $variant->id,
            productName: $product->name,
            productUrl: route('product', $product->slug),
            variantLabel: $variant->attributeValues->isEmpty() ? null : $variant->label(),
            imageUrl: $variant->image?->url() ?? $product->coverImage?->url(),
            unitPrice: $variant->price,
            quantity: $item->quantity,
            state: $this->lineStateFor($variant, $item->quantity),
            stockQuantity: $variant->stock_quantity,
            previousPrice: $item->added_price !== $variant->price ? $item->added_price : null,
            productId: $product->id,
            sku: $variant->sku,
            allowsPreorder: $product->allow_preorder,
        );
    }

    /**
     * @throws CartException
     */
    private function sellableVariant(int $variantId): ProductVariant
    {
        $variant = $this->variantService->findForCart($variantId);

        if ($variant === null || ! $variant->isSellable()) {
            throw CartException::unavailable();
        }

        return $variant;
    }

    /**
     * @throws CartException
     */
    private function ensureCanHold(ProductVariant $variant, int $quantity): void
    {
        match (true) {
            $quantity > self::MAX_LINE_QUANTITY => throw CartException::quantityLimit(self::MAX_LINE_QUANTITY),
            $variant->product->allow_preorder || $quantity <= $variant->stock_quantity => null,
            $variant->stock_quantity <= 0 => throw CartException::outOfStock(),
            default => throw CartException::notEnoughStock($variant->stock_quantity),
        };
    }

    /**
     * @throws ModelNotFoundException
     */
    private function ownedItem(CartOwner $owner, int $itemId): CartItem
    {
        $cart = $this->cartRepository->findForOwner($owner);
        $item = $cart === null ? null : $this->cartRepository->findItem($cart, $itemId);

        return $item ?? throw (new ModelNotFoundException)->setModel(CartItem::class, [$itemId]);
    }

    private function mergeItem(Cart $userCart, CartItem $guestItem): void
    {
        $existing = $this->cartRepository->findItemForVariant($userCart, $guestItem->product_variant_id);

        if ($existing === null) {
            $this->cartRepository->createItem($userCart, $guestItem->product_variant_id, $guestItem->quantity, $guestItem->added_price);

            return;
        }

        $this->cartRepository->updateItemQuantity($existing, min($existing->quantity + $guestItem->quantity, self::MAX_LINE_QUANTITY));
    }

    /**
     * @return array{0: int, 1: ?string} discount (paisa) and the reason when the saved code no longer applies
     */
    private function couponPreview(?string $couponCode, int $subtotal): array
    {
        if ($couponCode === null) {
            return [0, null];
        }

        try {
            return [$this->couponService->apply($couponCode, $subtotal)->discountAmount, null];
        } catch (CouponNotApplicableException $exception) {
            return [0, $exception->getMessage()];
        }
    }

    /**
     * Free delivery is decided on the subtotal before the coupon (F-021, rule 9).
     */
    private function deliveryQuoteFor(User $user, int $subtotal): ?DeliveryQuote
    {
        $address = $this->addressService->defaultFor($user);

        return $address === null ? null : $this->deliveryChargeService->quoteFor($address->upazila_id, $subtotal);
    }

    private function amountToFreeDelivery(int $subtotal): ?int
    {
        $threshold = $this->deliveryChargeService->freeDeliveryThreshold();

        return $threshold === null || $subtotal >= $threshold ? null : $threshold - $subtotal;
    }
}
