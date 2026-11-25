<?php

namespace App\Modules\Account\Repositories;

use App\Modules\Account\Data\CustomerFilter;
use App\Modules\Account\Data\NewAdminData;
use App\Modules\Account\Data\RegistrationData;
use App\Modules\Account\Enums\UserRole;
use App\Modules\Account\Enums\UserStatus;
use App\Modules\Account\Models\User;
use App\Support\PhoneNumber;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class UserRepository
{
    private const CUSTOMERS_PER_PAGE = 20;

    /**
     * Find a user by email (contains "@", case-insensitive) or by phone (any common BD format).
     */
    public function findByLogin(string $login): ?User
    {
        if (str_contains($login, '@')) {
            return User::query()->where('email', Str::lower(trim($login)))->first();
        }

        $phone = PhoneNumber::normalize($login);

        if ($phone === null) {
            return null;
        }

        return User::query()->where('phone', $phone)->first();
    }

    public function findById(int $userId): ?User
    {
        return User::query()->find($userId);
    }

    public function findByPhone(string $phone): ?User
    {
        return User::query()->where('phone', $phone)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', Str::lower($email))->first();
    }

    /**
     * New, unverified customer; or an unverified account with this phone is overwritten (rule F-014 §3.2).
     */
    public function saveUnverifiedCustomer(?User $existing, RegistrationData $data): User
    {
        $user = $existing ?? new User;

        $user->fill([
            'name' => $data->name,
            'phone' => $data->phone,
            'email' => $data->email,
            'password' => $data->password,
        ])->forceFill([
            'role' => UserRole::Customer,
            'status' => UserStatus::Active,
            'phone_verified_at' => null,
        ])->save();

        return $user;
    }

    public function updateProfile(User $user, string $name, ?string $email): void
    {
        $user->update(['name' => $name, 'email' => $email]);
    }

    /**
     * The new phone was proven with a code, so it counts as verified.
     */
    public function changePhone(User $user, string $phone): void
    {
        $user->forceFill(['phone' => $phone, 'phone_verified_at' => now()])->save();
    }

    public function markPhoneVerified(User $user): void
    {
        $user->forceFill(['phone_verified_at' => now()])->save();
    }

    public function createAdmin(NewAdminData $data): User
    {
        $user = new User([
            'name' => $data->name,
            'phone' => $data->phone,
            'email' => $data->email,
            'password' => $data->password,
        ]);

        $user->forceFill([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
            'phone_verified_at' => now(),
        ])->save();

        return $user;
    }

    public function updateLastLogin(User $user): void
    {
        $user->forceFill(['last_login_at' => now()])->save();
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->forceFill(['password' => $password])->save();
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateCustomers(CustomerFilter $filter): LengthAwarePaginator
    {
        return $this->customerQuery($filter->search)
            ->when($filter->status !== null, fn (Builder $query) => $query->where('status', $filter->status))
            ->latest('id')
            ->paginate(self::CUSTOMERS_PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return array<string, int> status value => count, with the search applied
     */
    public function countCustomersPerStatus(?string $search): array
    {
        return $this->customerQuery($search)
            ->toBase()
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    public function countCustomersRegisteredSince(CarbonInterface $since): int
    {
        return User::query()->where('role', UserRole::Customer)->where('created_at', '>=', $since)->count();
    }

    public function findCustomer(int $userId): ?User
    {
        return User::query()->where('role', UserRole::Customer)->find($userId);
    }

    public function setStatus(User $user, UserStatus $status): void
    {
        $user->forceFill(['status' => $status])->save();
    }

    public function isPhoneBlocked(string $phone): bool
    {
        return User::query()->where('phone', $phone)->where('status', UserStatus::Blocked)->exists();
    }

    /**
     * @return Builder<User>
     */
    private function customerQuery(?string $search): Builder
    {
        return User::query()
            ->where('role', UserRole::Customer)
            ->when($search !== null, function (Builder $query) use ($search): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
                $digits = preg_replace('/\D/', '', $search) ?? '';

                $query->where(fn (Builder $query) => $query
                    ->whereLike('name', $term)
                    ->orWhereLike('email', $term)
                    ->when(strlen($digits) >= 4, fn (Builder $query) => $query->orWhereLike('phone', '%'.$digits.'%')));
            });
    }
}
