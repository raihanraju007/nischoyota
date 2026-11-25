<?php

namespace App\Modules\Shopping\Repositories;

use App\Modules\Account\Models\User;
use App\Modules\Shopping\Models\WishlistItem;

final class WishlistRepository
{
    /**
     * Saved product ids, newest saved first.
     *
     * @return list<int>
     */
    public function productIdsFor(User $user): array
    {
        return WishlistItem::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->pluck('product_id')
            ->all();
    }

    public function find(User $user, int $productId): ?WishlistItem
    {
        return WishlistItem::query()->where('user_id', $user->id)->where('product_id', $productId)->first();
    }

    /**
     * A double tap can send two saves at once: the unique index decides and both get the same row.
     */
    public function create(User $user, int $productId): WishlistItem
    {
        return WishlistItem::query()->createOrFirst(['user_id' => $user->id, 'product_id' => $productId]);
    }

    public function delete(WishlistItem $item): void
    {
        $item->delete();
    }
}
