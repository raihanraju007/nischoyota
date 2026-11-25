<?php

namespace App\Modules\Order\Services;

use App\Modules\Account\Data\AddressData;
use App\Modules\Account\Enums\AddressLabel;
use App\Modules\Account\Exceptions\AddressLimitReachedException;
use App\Modules\Account\Models\User;
use App\Modules\Account\Services\AddressService;
use App\Modules\Account\Services\UserService;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Location\Data\DeliveryQuote;
use App\Modules\Location\Services\LocationService;
use App\Modules\Order\Data\CheckoutData;
use App\Modules\Order\Data\CheckoutTotals;
use App\Modules\Order\Data\ShippingAddress;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Events\OrderPlaced;
use App\Modules\Order\Exceptions\CheckoutException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Promotion\Exceptions\CouponNotApplicableException;
use App\Modules\Promotion\Services\CouponService;
use App\Modules\Shopping\Data\CartContents;
use App\Modules\Shopping\Data\CartLine;
use App\Modules\Shopping\Data\CartOwner;
use App\Modules\Shopping\Services\CartService;
use Illuminate\Support\Facades\DB;

/**
 * Places Cash on Delivery orders (F-023). Every amount is recalculated from the cart; the order,
 * its items, history, payment, stock, coupon use and the cleared cart are one transaction.
 */
final class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly CheckoutService $checkoutService,
        private readonly CartService $cartService,
        private readonly InventoryService $inventoryService,
        private readonly CouponService $couponService,
        private readonly AddressService $addressService,
        private readonly LocationService $locationService,
        private readonly UserService $userService,
    ) {}

    /**
     * @throws CheckoutException
     */
    public function place(CheckoutData $data, CartOwner $cartOwner, ?User $user): Order
    {
        $this->ensureCanOrder($user, $data->customerPhone);

        $address = $this->shippingAddressFor($data, $user);

        $order = DB::transaction(function () use ($data, $cartOwner, $user, $address): Order {
            $contents = $this->cartService->contentsFor($cartOwner);

            if (! $contents->canCheckout()) {
                throw CheckoutException::cartNotReady();
            }

            $totals = $this->checkoutService->totalsFor($contents, $address->upazilaId, $user, $data->customerPhone);
            $delivery = $this->ensurePayable($totals, $data->expectedTotal);

            $order = $this->orderRepository->create($this->orderAttributes($data, $user, $address, $totals, $delivery));
            $this->orderRepository->createItems($order, $this->takeStockFor($contents, $order));
            $this->orderRepository->recordStatus($order, fromStatus: null, toStatus: OrderStatus::Pending, userId: $user?->id);
            $this->orderRepository->createPayment($order, [
                'method' => $data->paymentMethod,
                'status' => PaymentStatus::Pending,
                'amount' => $totals->total(),
            ]);
            $this->recordCouponUse($totals);
            $this->cartService->clear($cartOwner);

            OrderPlaced::dispatch($order->id);

            return $order;
        });

        $this->saveNewAddress($data, $user);

        return $order;
    }

    /**
     * Checked again before a guest is sent a phone code, so a blocked phone costs no SMS (F-026).
     *
     * @throws CheckoutException
     */
    public function ensureCanOrder(?User $user, string $phone): void
    {
        if ($this->userService->isBlockedFromOrdering($user, $phone)) {
            throw CheckoutException::accountBlocked();
        }
    }

    public function findByNumber(string $orderNumber): ?Order
    {
        return $this->orderRepository->findByNumber($orderNumber);
    }

    /**
     * @throws CheckoutException
     */
    private function shippingAddressFor(CheckoutData $data, ?User $user): ShippingAddress
    {
        if ($data->savedAddressId !== null) {
            $saved = $user === null ? null : $this->addressService->findForUser($user, $data->savedAddressId);

            return $saved === null
                ? throw CheckoutException::addressNotFound()
                : $this->snapshot($saved->upazila_id, $saved->area, $saved->address_line, $saved->postal_code);
        }

        $new = $data->newAddress ?? throw CheckoutException::addressNotFound();

        return $this->snapshot($new->upazilaId, $new->area, $new->addressLine, $new->postalCode);
    }

    /**
     * @throws CheckoutException
     */
    private function snapshot(int $upazilaId, ?string $area, string $addressLine, ?string $postalCode): ShippingAddress
    {
        $upazila = $this->locationService->findUpazilaWithParents($upazilaId) ?? throw CheckoutException::addressNotFound();

        return new ShippingAddress(
            divisionId: $upazila->district->division_id,
            divisionName: $upazila->district->division->name,
            districtId: $upazila->district_id,
            districtName: $upazila->district->name,
            upazilaId: $upazila->id,
            upazilaName: $upazila->name,
            area: $area,
            addressLine: $addressLine,
            postalCode: $postalCode,
        );
    }

    /**
     * @throws CheckoutException
     */
    private function ensurePayable(CheckoutTotals $totals, ?int $expectedTotal): DeliveryQuote
    {
        if ($totals->couponError !== null) {
            throw CheckoutException::coupon($totals->couponError);
        }

        $delivery = $totals->delivery ?? throw CheckoutException::noDelivery();

        if ($expectedTotal !== $totals->total()) {
            throw CheckoutException::pricesChanged();
        }

        return $delivery;
    }

    /**
     * @return array<string, mixed>
     */
    private function orderAttributes(CheckoutData $data, ?User $user, ShippingAddress $address, CheckoutTotals $totals, DeliveryQuote $delivery): array
    {
        return [
            'user_id' => $user?->id,
            'status' => OrderStatus::Pending,
            'customer_name' => $data->customerName,
            'customer_phone' => $data->customerPhone,
            'customer_email' => $data->customerEmail,
            'division_id' => $address->divisionId,
            'division_name' => $address->divisionName,
            'district_id' => $address->districtId,
            'district_name' => $address->districtName,
            'upazila_id' => $address->upazilaId,
            'upazila_name' => $address->upazilaName,
            'area' => $address->area,
            'address_line' => $address->addressLine,
            'postal_code' => $address->postalCode,
            'delivery_zone_id' => $delivery->deliveryZoneId,
            'delivery_zone_name' => $delivery->zoneName,
            'delivery_min_days' => $delivery->minDays,
            'delivery_max_days' => $delivery->maxDays,
            'subtotal' => $totals->subtotal,
            'discount_total' => $totals->discount(),
            'delivery_charge' => $totals->deliveryCharge(),
            'total' => $totals->total(),
            'coupon_id' => $totals->coupon?->couponId,
            'coupon_code' => $totals->coupon?->code,
            'payment_method' => $data->paymentMethod,
            'payment_status' => PaymentStatus::Pending,
            'placed_at' => now(),
        ];
    }

    /**
     * Takes each line from stock and returns the item snapshots. Rows are locked in variant id order,
     * so two orders with the same items cannot deadlock.
     *
     * @return list<array<string, mixed>>
     *
     * @throws CheckoutException when a variant sold out meanwhile
     */
    private function takeStockFor(CartContents $contents, Order $order): array
    {
        $lines = $contents->lines;
        usort($lines, fn (CartLine $first, CartLine $second): int => $first->variantId <=> $second->variantId);

        return array_map(function (CartLine $line) use ($order): array {
            try {
                $takenFromStock = $this->inventoryService->deductForOrder($line->variantId, $line->quantity, $order->id, $line->allowsPreorder);
            } catch (InsufficientStockException $exception) {
                throw CheckoutException::soldOut($line->productName, $exception->availableStock);
            }

            return [
                'product_id' => $line->productId,
                'product_variant_id' => $line->variantId,
                'product_name' => $line->productName,
                'variant_label' => $line->variantLabel,
                'sku' => $line->sku,
                'image_url' => $line->imageUrl,
                'unit_price' => $line->unitPrice,
                'quantity' => $line->quantity,
                'line_total' => $line->lineTotal(),
                'is_preorder' => $takenFromStock < $line->quantity,
            ];
        }, $lines);
    }

    /**
     * @throws CheckoutException when the coupon's last use was taken meanwhile
     */
    private function recordCouponUse(CheckoutTotals $totals): void
    {
        if ($totals->coupon === null) {
            return;
        }

        try {
            $this->couponService->recordUse($totals->coupon->couponId);
        } catch (CouponNotApplicableException $exception) {
            throw CheckoutException::coupon($exception->getMessage());
        }
    }

    /**
     * "Save this address" adds a typed address to the book; a full book does not stop the order.
     */
    private function saveNewAddress(CheckoutData $data, ?User $user): void
    {
        if ($user === null || ! $data->shouldSaveAddress || $data->newAddress === null) {
            return;
        }

        try {
            $this->addressService->create($user, new AddressData(
                label: AddressLabel::Home,
                recipientName: $data->customerName,
                phone: $data->customerPhone,
                divisionId: $data->newAddress->divisionId,
                districtId: $data->newAddress->districtId,
                upazilaId: $data->newAddress->upazilaId,
                area: $data->newAddress->area,
                addressLine: $data->newAddress->addressLine,
                postalCode: $data->newAddress->postalCode,
            ));
        } catch (AddressLimitReachedException) {
            return;
        }
    }
}
