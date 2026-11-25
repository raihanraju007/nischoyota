<?php

namespace App\Modules\Account\Services;

use App\Modules\Account\Data\AddressData;
use App\Modules\Account\Exceptions\AddressLimitReachedException;
use App\Modules\Account\Models\Address;
use App\Modules\Account\Models\User;
use App\Modules\Account\Repositories\AddressRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Address book rules (F-015): max 10, exactly one default while any exist.
 */
final class AddressService
{
    public const MAX_ADDRESSES = 10;

    public function __construct(private readonly AddressRepository $addressRepository) {}

    /**
     * @return Collection<int, Address>
     */
    public function forUser(User $user): Collection
    {
        return $this->addressRepository->forUser($user);
    }

    /**
     * One of the user's own addresses, with its upazila (F-023 checkout); null for someone else's.
     */
    public function findForUser(User $user, int $addressId): ?Address
    {
        return $this->addressRepository->findForUser($user, $addressId);
    }

    public function defaultFor(User $user): ?Address
    {
        return $this->addressRepository->defaultFor($user);
    }

    /**
     * @throws AddressLimitReachedException
     */
    public function create(User $user, AddressData $data): Address
    {
        $count = $this->addressRepository->countFor($user);

        if ($count >= self::MAX_ADDRESSES) {
            throw new AddressLimitReachedException(self::MAX_ADDRESSES);
        }

        return $this->addressRepository->create($user, $data, isDefault: $count === 0);
    }

    public function update(Address $address, AddressData $data): void
    {
        $this->addressRepository->update($address, $data);
    }

    public function makeDefault(User $user, Address $address): void
    {
        DB::transaction(fn () => $this->addressRepository->makeDefault($user, $address));
    }

    /**
     * Deleting the default makes the oldest remaining address the default.
     */
    public function delete(User $user, Address $address): void
    {
        DB::transaction(function () use ($user, $address): void {
            $this->addressRepository->delete($address);

            $nextDefault = $address->is_default ? $this->addressRepository->oldestFor($user) : null;

            if ($nextDefault !== null) {
                $this->addressRepository->makeDefault($user, $nextDefault);
            }
        });
    }
}
