<?php

namespace App\Modules\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Services\AddressService;
use App\Modules\Order\Services\CustomerOrderService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AccountDashboardController extends Controller
{
    private const RECENT_ORDERS = 3;

    public function __construct(
        private readonly AddressService $addressService,
        private readonly CustomerOrderService $orderService,
    ) {}

    public function __invoke(Request $request): View
    {
        return view('account.dashboard', [
            'defaultAddress' => $this->addressService->defaultFor($request->user()),
            'recentOrders' => $this->orderService->recentFor($request->user(), self::RECENT_ORDERS),
        ]);
    }
}
