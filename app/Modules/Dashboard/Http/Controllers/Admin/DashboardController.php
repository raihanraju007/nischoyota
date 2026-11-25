<?php

namespace App\Modules\Dashboard\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Enums\ChartPeriod;
use App\Modules\Dashboard\Http\Requests\Admin\ShowDashboardRequest;
use App\Modules\Dashboard\Services\DashboardService;
use Illuminate\View\View;

/**
 * The admin home page (F-028), for staff and admins.
 */
final class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function show(ShowDashboardRequest $request): View
    {
        return view('admin.dashboard', [
            'stats' => $this->dashboardService->stats($request->period()),
            'period' => $request->period(),
            'periods' => ChartPeriod::cases(),
            'recentOrders' => $this->dashboardService->recentOrders(),
            'lowStockVariants' => $this->dashboardService->lowStockVariants(),
            'lowStockCount' => $this->dashboardService->lowStockCount(),
        ]);
    }
}
