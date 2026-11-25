<?php

namespace App\Http\View\Composers;

use App\Modules\Review\Services\ReviewService;
use Illuminate\View\View;

/**
 * "What Our Customers Say" on the home page (F-027): the newest well-rated approved reviews.
 */
final class HomeReviewsComposer
{
    public function __construct(private readonly ReviewService $reviewService) {}

    public function compose(View $view): void
    {
        $view->with('customerReviews', $this->reviewService->highlightsForHome());
    }
}
