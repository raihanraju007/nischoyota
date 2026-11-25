<?php

namespace App\Modules\Review\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Review\Http\Requests\SaveReviewRequest;
use App\Modules\Review\Services\ReviewService;
use Illuminate\Http\RedirectResponse;

/**
 * Customers write or update their review of a product (F-027).
 */
final class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService) {}

    public function store(SaveReviewRequest $request, string $slug): RedirectResponse
    {
        $productId = $this->reviewService->listedProductId($slug) ?? abort(404);

        $this->reviewService->saveForCustomer($productId, $request->user(), $request->toData());

        return redirect(route('product', $slug).'#tab-reviews')
            ->with('success', __('Thank you! Your review will appear after we check it.'));
    }
}
