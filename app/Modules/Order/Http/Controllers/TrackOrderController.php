<?php

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Http\Requests\TrackOrderRequest;
use App\Modules\Order\Services\CustomerOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Guest order tracking (F-024). The lookup is a POST so the phone never appears in a URL.
 */
final class TrackOrderController extends Controller
{
    public function __construct(private readonly CustomerOrderService $orderService) {}

    public function show(): View
    {
        return view('orders.track', ['order' => null, 'timeline' => []]);
    }

    public function lookup(TrackOrderRequest $request): View|RedirectResponse
    {
        $order = $this->orderService->track($request->orderNumber(), $request->phone());

        if ($order === null) {
            return back()->withInput()->withErrors(['order_number' => __("We couldn't find an order with that number and phone.")]);
        }

        return view('orders.track', ['order' => $order, 'timeline' => $this->orderService->timeline($order)]);
    }
}
