<?php

namespace App\Modules\Review\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Review\Enums\ReviewStatus;
use App\Modules\Review\Http\Requests\Admin\ListReviewsRequest;
use App\Modules\Review\Models\Review;
use App\Modules\Review\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Review moderation for staff and admins (F-027).
 */
final class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService) {}

    public function index(ListReviewsRequest $request): View
    {
        return view('admin.reviews.index', [
            'reviews' => $this->reviewService->paginateForAdmin($request->status()),
            'statusCounts' => $this->reviewService->countPerStatus(),
            'activeStatus' => $request->status(),
            'statuses' => ReviewStatus::cases(),
        ]);
    }

    public function approve(Request $request, Review $review): RedirectResponse
    {
        $this->reviewService->approve($review, $request->user()->id);

        return back()->with('success', __('Review approved. It is now on the product page.'));
    }

    public function reject(Request $request, Review $review): RedirectResponse
    {
        $this->reviewService->reject($review, $request->user()->id);

        return back()->with('success', __('Review rejected.'));
    }
}
