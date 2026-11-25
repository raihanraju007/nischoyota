<?php

namespace App\Modules\Order\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Order\Enums\OrderDateRange;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Exceptions\OrderException;
use App\Modules\Order\Http\Requests\Admin\ChangeOrderStatusRequest;
use App\Modules\Order\Http\Requests\Admin\ListOrdersRequest;
use App\Modules\Order\Http\Requests\Admin\UpdateOrderNoteRequest;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\AdminOrderService;
use App\Modules\Order\Services\OrderStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin order management (F-025), for staff and admins (ADR-012).
 */
final class OrderController extends Controller
{
    public function __construct(
        private readonly AdminOrderService $adminOrderService,
        private readonly OrderStatusService $orderStatusService,
    ) {}

    public function index(ListOrdersRequest $request): View
    {
        $filter = $request->toFilter();

        return view('admin.orders.index', [
            'orders' => $this->adminOrderService->paginate($filter),
            'tabCounts' => $this->adminOrderService->countPerTab($filter),
            'filter' => $filter,
            'statuses' => OrderStatus::adminFilters(),
            'dateRanges' => OrderDateRange::cases(),
        ]);
    }

    public function show(Order $order): View
    {
        $order = $this->adminOrderService->loadDetails($order);

        return view('admin.orders.show', [
            'order' => $order,
            'history' => $this->adminOrderService->history($order),
            'customerOrderCount' => $this->adminOrderService->customerOrderCount($order),
        ]);
    }

    public function updateStatus(ChangeOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $data = $request->toData();

        try {
            $this->orderStatusService->change($order, $data);
        } catch (OrderException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return to_route('admin.orders.show', $order)->with('success', __('Order marked as :status.', ['status' => $data->toStatus->label()]));
    }

    public function markPaid(Order $order): RedirectResponse
    {
        try {
            $this->orderStatusService->markPaid($order);
        } catch (OrderException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('admin.orders.show', $order)->with('success', __('Payment marked as received.'));
    }

    public function updateNote(UpdateOrderNoteRequest $request, Order $order): RedirectResponse
    {
        $this->adminOrderService->updateNote($order, $request->note());

        return to_route('admin.orders.show', $order)->with('success', __('Note saved.'));
    }
}
