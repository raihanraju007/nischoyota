<?php

namespace App\Modules\Order\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Order\Http\Requests\Admin\PrintPackingSlipsRequest;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderPrintService;
use Illuminate\View\View;

/**
 * Print-friendly order pages (F-025b): packing slips for the parcel, order copy for inside the box.
 */
final class OrderPrintController extends Controller
{
    public function __construct(private readonly OrderPrintService $orderPrintService) {}

    public function invoice(Order $order): View
    {
        return view('admin.orders.print.invoice', [
            'order' => $this->orderPrintService->loadForPrint($order),
            'store' => $this->orderPrintService->store(),
        ]);
    }

    public function packingSlip(Order $order): View
    {
        return $this->packingSlips(collect([$this->orderPrintService->loadForPrint($order)]));
    }

    public function bulkPackingSlips(PrintPackingSlipsRequest $request): View
    {
        $orders = $this->orderPrintService->findForPrint($request->orderIds());

        if ($request->shouldMoveToProcessing()) {
            $this->orderPrintService->moveConfirmedToProcessing($orders, $request->user()->id);
            $orders = $this->orderPrintService->findForPrint($request->orderIds());
        }

        return $this->packingSlips($orders);
    }

    /**
     * @param  iterable<Order>  $orders
     */
    private function packingSlips(iterable $orders): View
    {
        return view('admin.orders.print.packing-slips', [
            'orders' => $orders,
            'weights' => $this->orderPrintService->parcelWeights($orders),
            'store' => $this->orderPrintService->store(),
        ]);
    }
}
