<?php

namespace App\Modules\Inventory\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Enums\StockListTab;
use App\Modules\Inventory\Enums\StockMovementReason;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Http\Requests\Admin\AdjustStockRequest;
use App\Modules\Inventory\Http\Requests\Admin\ListStockRequest;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function index(ListStockRequest $request): View
    {
        $filter = $request->toFilter();

        return view('admin.inventory.index', [
            'variants' => $this->inventoryService->paginateVariants($filter),
            'tabCounts' => $this->inventoryService->countPerTab($filter),
            'filter' => $filter,
            'tabs' => StockListTab::cases(),
            'lowStockThreshold' => $this->inventoryService->lowStockThreshold(),
        ]);
    }

    public function show(ProductVariant $variant): View
    {
        return view('admin.inventory.show', [
            'variant' => $this->inventoryService->loadVariantDetails($variant),
            'movements' => $this->inventoryService->paginateMovements($variant),
            'reasons' => StockMovementReason::manualReasons(),
            'lowStockThreshold' => $this->inventoryService->lowStockThreshold(),
        ]);
    }

    public function adjust(AdjustStockRequest $request, ProductVariant $variant): RedirectResponse
    {
        try {
            $movement = $this->inventoryService->adjust($variant->id, $request->toData());
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->withErrors(['quantity' => $exception->getMessage()]);
        }

        if ($movement === null) {
            return back()->with('info', __('No change: the stock is already :count.', ['count' => $variant->stock_quantity]));
        }

        return to_route('admin.inventory.show', $variant)
            ->with('success', __('Stock updated to :count.', ['count' => $movement->stock_after]));
    }
}
