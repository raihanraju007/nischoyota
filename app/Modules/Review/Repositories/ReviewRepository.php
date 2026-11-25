<?php

namespace App\Modules\Review\Repositories;

use App\Modules\Catalog\Enums\ProductStatus;
use App\Modules\Review\Enums\ReviewStatus;
use App\Modules\Review\Models\Review;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ReviewRepository
{
    private const ADMIN_PER_PAGE = 20;

    public function findByCustomer(int $productId, int $userId): ?Review
    {
        return Review::query()->where('product_id', $productId)->where('user_id', $userId)->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function saveForCustomer(int $productId, int $userId, array $attributes): Review
    {
        return Review::query()->updateOrCreate(['product_id' => $productId, 'user_id' => $userId], $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Review $review, array $attributes): void
    {
        $review->update($attributes);
    }

    /**
     * @return LengthAwarePaginator<int, Review>
     */
    public function paginateForAdmin(ReviewStatus $status): LengthAwarePaginator
    {
        return Review::query()
            ->where('status', $status)
            ->with(['product.coverImage', 'user'])
            ->latest('id')
            ->paginate(self::ADMIN_PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return array<string, int> status value => count
     */
    public function countPerStatus(): array
    {
        return Review::query()
            ->toBase()
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    /**
     * @return array<int, int> stars => approved review count
     */
    public function approvedCountPerRating(int $productId): array
    {
        return Review::query()
            ->toBase()
            ->where('product_id', $productId)
            ->where('status', ReviewStatus::Approved)
            ->groupBy('rating')
            ->selectRaw('rating, COUNT(*) as aggregate')
            ->pluck('aggregate', 'rating')
            ->mapWithKeys(fn ($count, $rating): array => [(int) $rating => (int) $count])
            ->all();
    }

    /**
     * @return Collection<int, Review>
     */
    public function latestApprovedFor(int $productId, int $limit): Collection
    {
        return Review::query()
            ->where('product_id', $productId)
            ->where('status', ReviewStatus::Approved)
            ->with('user')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Newest approved reviews with at least $minimumRating stars whose product is active (home page).
     *
     * @return Collection<int, Review>
     */
    public function latestHighlyRated(int $minimumRating, int $limit): Collection
    {
        return Review::query()
            ->where('status', ReviewStatus::Approved)
            ->where('rating', '>=', $minimumRating)
            ->whereHas('product', fn (Builder $product) => $product->where('status', ProductStatus::Active))
            ->with(['user', 'product'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function countWithStatus(ReviewStatus $status): int
    {
        return Review::query()->where('status', $status)->count();
    }
}
