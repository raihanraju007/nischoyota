<?php

namespace App\Modules\Account\Repositories;

use App\Modules\Account\Data\AddressData;
use App\Modules\Account\Models\Address;
use App\Modules\Account\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class AddressRepository
{
    /**
     * Default first, then oldest first; with place names for display.
     *
     * @return Collection<int, Address>
     */
    public function forUser(User $user): Collection
    {
        return $user->addresses()
            ->with(['upazila', 'district'])
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();
    }

    public function findForUser(User $user, int $addressId): ?Address
    {
        return $user->addresses()->find($addressId);
    }

    public function defaultFor(User $user): ?Address
    {
        return $user->addresses()->with(['upazila', 'district'])->where('is_default', true)->first();
    }

    public function countFor(User $user): int
    {
        return $user->addresses()->count();
    }

    public function create(User $user, AddressData $data, bool $isDefault): Address
    {
        $address = $user->addresses()->make($this->attributesFrom($data));
        $address->forceFill(['is_default' => $isDefault])->save();

        return $address;
    }

    public function update(Address $address, AddressData $data): void
    {
        $address->update($this->attributesFrom($data));
    }

    public function delete(Address $address): void
    {
        $address->delete();
    }

    public function makeDefault(User $user, Address $address): void
    {
        $user->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
        $address->forceFill(['is_default' => true])->save();
    }

    public function oldestFor(User $user): ?Address
    {
        return $user->addresses()->orderBy('id')->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesFrom(AddressData $data): array
    {
        return [
            'label' => $data->label,
            'recipient_name' => $data->recipientName,
            'phone' => $data->phone,
            'division_id' => $data->divisionId,
            'district_id' => $data->districtId,
            'upazila_id' => $data->upazilaId,
            'area' => $data->area,
            'address_line' => $data->addressLine,
            'postal_code' => $data->postalCode,
        ];
    }
}
