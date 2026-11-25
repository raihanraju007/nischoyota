<?php

namespace App\Http\View\Composers;

use App\Modules\Content\Services\ContactMessageService;
use App\Modules\Order\Services\AdminOrderService;
use App\Modules\Review\Services\ReviewService;
use Illuminate\View\View;

/**
 * Counts for the admin sidebar badges and the top-bar bell (F-025, F-026, F-027).
 */
final class AdminShellComposer
{
    public function __construct(
        private readonly AdminOrderService $adminOrderService,
        private readonly ContactMessageService $contactMessageService,
        private readonly ReviewService $reviewService,
    ) {}

    public function compose(View $view): void
    {
        $view->with([
            'pendingOrderCount' => $this->adminOrderService->pendingCount(),
            'newMessageCount' => $this->contactMessageService->newCount(),
            'pendingReviewCount' => $this->reviewService->pendingCount(),
        ]);
    }
}
