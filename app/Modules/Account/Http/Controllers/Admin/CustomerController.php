<?php

namespace App\Modules\Account\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Account\Enums\UserStatus;
use App\Modules\Account\Http\Requests\Admin\ListCustomersRequest;
use App\Modules\Account\Models\User;
use App\Modules\Account\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Customer accounts for staff and admins (F-026). Staff and admin accounts are not reachable here.
 */
final class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customerService) {}

    public function index(ListCustomersRequest $request): View
    {
        $filter = $request->toFilter();
        $customers = $this->customerService->paginate($filter);

        return view('admin.customers.index', [
            'customers' => $customers,
            'orderStats' => $this->customerService->orderStatsFor($customers->items()),
            'statusCounts' => $this->customerService->countPerStatus($filter),
            'filter' => $filter,
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function show(int $customerId): View
    {
        $customer = $this->customer($customerId);

        return view('admin.customers.show', [
            'customer' => $customer,
            'stats' => $this->customerService->orderStatsFor([$customer])[$customer->id],
            'orders' => $this->customerService->ordersOf($customer),
            'addresses' => $this->customerService->addressesOf($customer),
        ]);
    }

    public function block(int $customerId): RedirectResponse
    {
        return $this->changeStatus($customerId, UserStatus::Blocked, __(':name is blocked and can no longer sign in or order.'));
    }

    public function unblock(int $customerId): RedirectResponse
    {
        return $this->changeStatus($customerId, UserStatus::Active, __(':name is active again.'));
    }

    private function changeStatus(int $customerId, UserStatus $status, string $successMessage): RedirectResponse
    {
        $customer = $this->customer($customerId);

        if (! $this->customerService->changeStatus($customer, $status)) {
            return back()->with('info', __('No change: :name is already :status.', ['name' => $customer->name, 'status' => mb_strtolower($status->label())]));
        }

        return back()->with('success', str_replace(':name', $customer->name, $successMessage));
    }

    private function customer(int $customerId): User
    {
        return $this->customerService->findCustomer($customerId) ?? throw new NotFoundHttpException;
    }
}
