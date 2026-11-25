<?php

namespace App\Modules\Account\Services;

use App\Modules\Account\Data\CustomerFilter;
use App\Modules\Account\Enums\UserStatus;
use App\Modules\Account\Models\Address;
use App\Modules\Account\Models\User;
use App\Modules\Account\Repositories\UserRepository;
use App\Modules\Order\Data\CustomerOrderStats;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\CustomerOrderService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Customer accounts in the admin panel (F-026): list, detail, block / unblock.
 */
final class CustomerService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AddressService $addressService,
        private readonly CustomerOrderService $customerOrderService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(CustomerFilter $filter): LengthAwarePaginator
    {
        return $this->userRepository->paginateCustomers($filter);
    }

    /**
     * @return array<string, int> 'all' and each status value => count
     */
    public function countPerStatus(CustomerFilter $filter): array
    {
        $perStatus = $this->userRepository->countCustomersPerStatus($filter->search);
        $counts = ['all' => array_sum($perStatus)];

        foreach (UserStatus::cases() as $status) {
            $counts[$status->value] = $perStatus[$status->value] ?? 0;
        }

        return $counts;
    }

    /**
     * @param  iterable<User>  $customers
     * @return array<int, CustomerOrderStats> user id => stats
     */
    public function orderStatsFor(iterable $customers): array
    {
        return $this->customerOrderService->statsFor(collect($customers)->pluck('id')->all());
    }

    /**
     * New customer accounts since a moment (dashboard, F-028).
     */
    public function countRegisteredSince(CarbonInterface $since): int
    {
        return $this->userRepository->countCustomersRegisteredSince($since);
    }

    public function findCustomer(int $userId): ?User
    {
        return $this->userRepository->findCustomer($userId);
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function ordersOf(User $customer): LengthAwarePaginator
    {
        return $this->customerOrderService->paginateFor($customer, status: null);
    }

    /**
     * @return Collection<int, Address>
     */
    public function addressesOf(User $customer): Collection
    {
        return $this->addressService->forUser($customer);
    }

    /**
     * Returns false when the customer already had this status.
     */
    public function changeStatus(User $customer, UserStatus $status): bool
    {
        if ($customer->status === $status) {
            return false;
        }

        $this->userRepository->setStatus($customer, $status);

        return true;
    }
}
