<?php

namespace App\Modules\Review\Services;

use App\Modules\Account\Models\User;
use App\Modules\Catalog\Services\ProductPageService;
use App\Modules\Catalog\Services\ProductService;
use App\Modules\Order\Services\CustomerOrderService;
use App\Modules\Review\Data\ReviewData;
use App\Modules\Review\Data\ReviewSummary;
use App\Modules\Review\Enums\ReviewStatus;
use App\Modules\Review\Models\Review;
use App\Modules\Review\Repositories\ReviewRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Product reviews (F-027): one per customer per product, moderated, with the product's rating cache
 * recalculated from approved reviews after every change.
 */
final class ReviewService
{
    private const REVIEWS_ON_PRODUCT_PAGE = 10;

    private const HOME_REVIEW_COUNT = 3;

    private const HOME_MINIMUM_RATING = 4;

    public function __construct(
        private readonly ReviewRepository $reviewRepository,
        private readonly ProductService $productService,
        private readonly ProductPageService $productPageService,
        private readonly CustomerOrderService $customerOrderService,
    ) {}

    /**
     * Reviews can only be written for products the storefront shows.
     */
    public function listedProductId(string $slug): ?int
    {
        return $this->productPageService->findListedProduct($slug)?->id;
    }

    /**
     * Writes or replaces the customer's review; it waits for approval again.
     */
    public function saveForCustomer(int $productId, User $customer, ReviewData $data): Review
    {
        return DB::transaction(function () use ($productId, $customer, $data): Review {
            $review = $this->reviewRepository->saveForCustomer($productId, $customer->id, [
                'rating' => $data->rating,
                'comment' => $data->comment,
                'is_verified_purchase' => $this->customerOrderService->hasDeliveredProduct($customer->id, $productId),
                'status' => ReviewStatus::Pending,
                'moderated_by' => null,
                'moderated_at' => null,
            ]);
            $this->refreshRatingCache($productId);

            return $review;
        });
    }

    public function approve(Review $review, int $staffUserId): void
    {
        $this->moderate($review, ReviewStatus::Approved, $staffUserId);
    }

    public function reject(Review $review, int $staffUserId): void
    {
        $this->moderate($review, ReviewStatus::Rejected, $staffUserId);
    }

    public function findByCustomer(int $productId, ?User $customer): ?Review
    {
        return $customer === null ? null : $this->reviewRepository->findByCustomer($productId, $customer->id);
    }

    /**
     * @return LengthAwarePaginator<int, Review>
     */
    public function paginateForAdmin(ReviewStatus $status): LengthAwarePaginator
    {
        return $this->reviewRepository->paginateForAdmin($status);
    }

    /**
     * @return array<string, int> status value => count
     */
    public function countPerStatus(): array
    {
        $perStatus = $this->reviewRepository->countPerStatus();

        return collect(ReviewStatus::cases())
            ->mapWithKeys(fn (ReviewStatus $status): array => [$status->value => $perStatus[$status->value] ?? 0])
            ->all();
    }

    public function pendingCount(): int
    {
        return $this->reviewRepository->countWithStatus(ReviewStatus::Pending);
    }

    public function summaryFor(int $productId): ReviewSummary
    {
        $perRating = $this->reviewRepository->approvedCountPerRating($productId);
        $countPerRating = [];

        foreach (range(Review::MAX_RATING, Review::MIN_RATING) as $stars) {
            $countPerRating[$stars] = $perRating[$stars] ?? 0;
        }

        $count = array_sum($countPerRating);
        $total = array_sum(array_map(fn (int $stars, int $reviews): int => $stars * $reviews, array_keys($countPerRating), $countPerRating));

        return new ReviewSummary($count === 0 ? 0.0 : $total / $count, $count, $countPerRating);
    }

    /**
     * @return Collection<int, Review>
     */
    public function latestApprovedFor(int $productId): Collection
    {
        return $this->reviewRepository->latestApprovedFor($productId, self::REVIEWS_ON_PRODUCT_PAGE);
    }

    /**
     * @return Collection<int, Review>
     */
    public function highlightsForHome(): Collection
    {
        return $this->reviewRepository->latestHighlyRated(self::HOME_MINIMUM_RATING, self::HOME_REVIEW_COUNT);
    }

    private function moderate(Review $review, ReviewStatus $status, int $staffUserId): void
    {
        DB::transaction(function () use ($review, $status, $staffUserId): void {
            $this->reviewRepository->update($review, ['status' => $status, 'moderated_by' => $staffUserId, 'moderated_at' => now()]);
            $this->refreshRatingCache($review->product_id);
        });
    }

    private function refreshRatingCache(int $productId): void
    {
        $summary = $this->summaryFor($productId);

        $this->productService->updateRatingCache($productId, $summary->average, $summary->count);
    }
}
