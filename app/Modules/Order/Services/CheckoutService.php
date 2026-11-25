<?php

namespace App\Modules\Order\Services;

use App\Modules\Account\Enums\OtpChannel;
use App\Modules\Account\Enums\OtpPurpose;
use App\Modules\Account\Exceptions\OtpException;
use App\Modules\Account\Models\Address;
use App\Modules\Account\Models\User;
use App\Modules\Account\Services\AddressService;
use App\Modules\Account\Services\OtpService;
use App\Modules\Location\Services\DeliveryChargeService;
use App\Modules\Location\Services\LocationService;
use App\Modules\Order\Data\CheckoutTotals;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Promotion\Exceptions\CouponNotApplicableException;
use App\Modules\Promotion\Services\CouponService;
use App\Modules\Shopping\Data\CartContents;
use Illuminate\Database\Eloquent\Collection;

/**
 * Everything around placing an order (F-023): the amounts, the page's options, the guest phone code.
 */
final class CheckoutService
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly DeliveryChargeService $deliveryChargeService,
        private readonly AddressService $addressService,
        private readonly LocationService $locationService,
        private readonly OtpService $otpService,
        private readonly OrderRepository $orderRepository,
    ) {}

    /**
     * Subtotal of the cart, coupon (with this customer's earlier uses) and delivery to the upazila.
     * Free delivery is decided on the subtotal before the coupon (F-021 rule 9).
     */
    public function totalsFor(CartContents $contents, ?int $upazilaId, ?User $user, ?string $phone): CheckoutTotals
    {
        $subtotal = $contents->subtotal();
        $coupon = null;
        $couponError = null;

        if ($contents->couponCode !== null) {
            try {
                $coupon = $this->couponService->apply($contents->couponCode, $subtotal, $this->couponUsageCount($contents->couponCode, $user, $phone));
            } catch (CouponNotApplicableException $exception) {
                $couponError = $exception->getMessage();
            }
        }

        return new CheckoutTotals(
            subtotal: $subtotal,
            couponCode: $contents->couponCode,
            coupon: $coupon,
            couponError: $couponError,
            delivery: $upazilaId === null ? null : $this->deliveryChargeService->quoteFor($upazilaId, $subtotal),
        );
    }

    /**
     * @return Collection<int, Address>
     */
    public function savedAddressesFor(?User $user): Collection
    {
        return $user === null ? new Collection : $this->addressService->forUser($user);
    }

    /**
     * Division / district / upazila select options for the chosen ids.
     *
     * @return array{divisionOptions: array<int, string>, districtOptions: array<int, string>, upazilaOptions: array<int, string>}
     */
    public function locationOptions(?int $divisionId, ?int $districtId): array
    {
        return [
            'divisionOptions' => $this->locationService->divisionOptions(),
            'districtOptions' => $this->locationService->districtOptions($divisionId),
            'upazilaOptions' => $this->locationService->upazilaOptions($districtId),
        ];
    }

    /**
     * Upazila of one of the user's saved addresses (to price delivery); null for someone else's.
     */
    public function upazilaOfSavedAddress(User $user, int $addressId): ?int
    {
        return $this->addressService->findForUser($user, $addressId)?->upazila_id;
    }

    /**
     * @throws OtpException when sending too often
     */
    public function sendPhoneCode(string $phone, ?string $ipAddress): void
    {
        $this->otpService->send($phone, OtpChannel::Sms, OtpPurpose::CheckoutPhone, $ipAddress);
    }

    /**
     * @throws OtpException when the code is wrong, expired or used up
     */
    public function confirmPhone(string $phone, string $code): void
    {
        $this->otpService->verify($phone, OtpPurpose::CheckoutPhone, $code);
    }

    private function couponUsageCount(string $couponCode, ?User $user, ?string $phone): int
    {
        if ($user === null && $phone === null) {
            return 0;
        }

        return $this->orderRepository->couponUsageCount($couponCode, $user?->id, (string) $phone);
    }
}
