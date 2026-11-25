<?php

namespace App\Modules\Shopping\Http;

use App\Modules\Account\Models\User;
use App\Modules\Shopping\Services\WishlistService;
use Illuminate\Http\Request;

/**
 * The signed-in customer's saved product ids, loaded once per request (kept on the request) for
 * every heart and the header count. Empty for guests. Inject it per action, not in a controller constructor.
 */
final class CurrentWishlist
{
    private const PRODUCT_IDS_ATTRIBUTE = 'shopping.wishlist_product_ids';

    private const COUNT_ATTRIBUTE = 'shopping.wishlist_count';

    public function __construct(
        private readonly Request $request,
        private readonly WishlistService $wishlistService,
    ) {}

    /**
     * @return list<int>
     */
    public function productIds(): array
    {
        $user = $this->request->user();

        if (! $user instanceof User) {
            return [];
        }

        if (! $this->request->attributes->has(self::PRODUCT_IDS_ATTRIBUTE)) {
            $this->request->attributes->set(self::PRODUCT_IDS_ATTRIBUTE, $this->wishlistService->productIdsFor($user));
        }

        return $this->request->attributes->get(self::PRODUCT_IDS_ATTRIBUTE);
    }

    /**
     * Saved products that are still listed (the header badge).
     */
    public function count(): int
    {
        if (! $this->request->attributes->has(self::COUNT_ATTRIBUTE)) {
            $this->request->attributes->set(self::COUNT_ATTRIBUTE, $this->wishlistService->listedCount($this->productIds()));
        }

        return $this->request->attributes->get(self::COUNT_ATTRIBUTE);
    }

    /**
     * After a change in this request.
     */
    public function forget(): void
    {
        $this->request->attributes->remove(self::PRODUCT_IDS_ATTRIBUTE);
        $this->request->attributes->remove(self::COUNT_ATTRIBUTE);
    }
}
