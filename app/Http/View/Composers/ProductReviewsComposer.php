<?php

namespace App\Http\View\Composers;

use App\Modules\Account\Enums\UserRole;
use App\Modules\Catalog\Models\Product;
use App\Modules\Review\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Reviews tab on the product page (F-027): summary, approved reviews and the visitor's own review.
 */
final class ProductReviewsComposer
{
    public function __construct(
        private readonly ReviewService $reviewService,
        private readonly Request $request,
    ) {}

    public function compose(View $view): void
    {
        /** @var Product $product */
        $product = $view->getData()['product'];
        $user = $this->request->user();

        $view->with([
            'reviewSummary' => $this->reviewService->summaryFor($product->id),
            'approvedReviews' => $this->reviewService->latestApprovedFor($product->id),
            'ownReview' => $this->reviewService->findByCustomer($product->id, $user),
            'canWriteReview' => $user?->role === UserRole::Customer,
        ]);
    }
}
