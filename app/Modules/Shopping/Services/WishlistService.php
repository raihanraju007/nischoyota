<?php

namespace App\Modules\Shopping\Services;

use App\Modules\Account\Models\User;
use App\Modules\Catalog\Data\ProductCardData;
use App\Modules\Catalog\Services\ProductListingService;
use App\Modules\Catalog\Services\ProductVariantService;
use App\Modules\Shopping\Data\CartOwner;
use App\Modules\Shopping\Exceptions\CartException;
use App\Modules\Shopping\Exceptions\WishlistException;
use App\Modules\Shopping\Models\WishlistItem;
use App\Modules\Shopping\Repositories\WishlistRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Products a signed-in customer saved (F-022). Only listed products are shown and counted.
 */
final class WishlistService
{
    private const MOVE_QUANTITY = 1;

    public function __construct(
        private readonly WishlistRepository $wishlistRepository,
        private readonly ProductListingService $listingService,
        private readonly ProductVariantService $variantService,
        private readonly CartService $cartService,
    ) {}

    /**
     * Every saved product id, newest first (also unlisted ones: their hearts stay red if they come back).
     *
     * @return list<int>
     */
    public function productIdsFor(User $user): array
    {
        return $this->wishlistRepository->productIdsFor($user);
    }

    /**
     * Cards of the saved products that are still listed, newest saved first.
     *
     * @param  list<int>  $savedProductIds  From productIdsFor()
     * @return list<ProductCardData>
     */
    public function cardsFor(array $savedProductIds): array
    {
        return $this->listingService->cardsFor($savedProductIds);
    }

    /**
     * @param  list<int>  $savedProductIds  From productIdsFor()
     */
    public function listedCount(array $savedProductIds): int
    {
        return count($this->listingService->listedProductIds($savedProductIds));
    }

    /**
     * Saves the product, or removes it when already saved. Returns whether it is saved now.
     *
     * @throws WishlistException when saving a product that is not listed
     */
    public function toggle(User $user, int $productId): bool
    {
        $item = $this->wishlistRepository->find($user, $productId);

        if ($item !== null) {
            $this->wishlistRepository->delete($item);

            return false;
        }

        if ($this->listingService->listedProductIds([$productId]) === []) {
            throw WishlistException::unavailable();
        }

        $this->wishlistRepository->create($user, $productId);

        return true;
    }

    /**
     * Adds one of the product's only variant to the cart and removes it from the wishlist.
     *
     * @throws WishlistException when the product has options
     * @throws CartException when the cart refuses it (stock, availability); the product stays saved
     * @throws ModelNotFoundException when the product is not in the wishlist
     */
    public function moveToCart(User $user, int $productId): void
    {
        $item = $this->wishlistRepository->find($user, $productId)
            ?? throw (new ModelNotFoundException)->setModel(WishlistItem::class, [$productId]);
        $variantId = $this->variantService->singleActiveVariantId($productId) ?? throw WishlistException::chooseOptions();

        DB::transaction(function () use ($user, $item, $variantId): void {
            $this->cartService->add(CartOwner::user($user), $variantId, self::MOVE_QUANTITY);
            $this->wishlistRepository->delete($item);
        });
    }
}
