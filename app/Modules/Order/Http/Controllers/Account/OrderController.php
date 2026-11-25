<?php

namespace App\Modules\Order\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Exceptions\OrderException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\CustomerOrderService;
use App\Modules\Shopping\Http\CurrentCart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * "My Orders" in the customer account (F-024). Orders are always looked up within the signed-in customer's own.
 */
final class OrderController extends Controller
{
    public function __construct(private readonly CustomerOrderService $orderService) {}

    public function index(Request $request): View
    {
        $requestedStatus = $request->query('status');
        $status = is_string($requestedStatus) ? OrderStatus::tryFrom($requestedStatus) : null;

        return view('account.orders.index', [
            'orders' => $this->orderService->paginateFor($request->user(), $status),
            'activeStatus' => $status,
            'statusFilters' => OrderStatus::customerFilters(),
        ]);
    }

    public function show(Request $request, string $orderNumber): View
    {
        $order = $this->ownOrder($request, $orderNumber);

        return view('account.orders.show', [
            'order' => $order,
            'timeline' => $this->orderService->timeline($order),
        ]);
    }

    public function cancel(Request $request, string $orderNumber): RedirectResponse
    {
        $order = $this->ownOrder($request, $orderNumber);

        try {
            $this->orderService->cancel($request->user(), $order);
        } catch (OrderException $exception) {
            return to_route('account.orders.show', $orderNumber)->with('error', $exception->getMessage());
        }

        return to_route('account.orders.show', $orderNumber)->with('success', __('Your order has been cancelled.'));
    }

    public function buyAgain(Request $request, CurrentCart $currentCart, string $orderNumber): RedirectResponse
    {
        $result = $this->orderService->buyAgain($this->ownOrder($request, $orderNumber), $currentCart->ownerForChange());

        return match (true) {
            $result->skippedProductNames === [] => to_route('cart')->with('success', __('The items are in your cart.')),
            $result->addedCount === 0 => back()->with('error', __('These items can no longer be bought: :names.', ['names' => implode(', ', $result->skippedProductNames)])),
            default => to_route('cart')->with('info', __('Added to your cart. Not available any more: :names.', ['names' => implode(', ', $result->skippedProductNames)])),
        };
    }

    private function ownOrder(Request $request, string $orderNumber): Order
    {
        return $this->orderService->findFor($request->user(), $orderNumber) ?? throw new NotFoundHttpException;
    }
}
