<?php

namespace App\Modules\Order\Data;

use App\Modules\Order\Enums\PaymentMethod;

/**
 * Validated checkout form. Exactly one of $savedAddressId / $newAddress is set.
 * $expectedTotal is the total (paisa) the customer saw: only compared, never charged.
 * toArray()/fromArray() keep a guest's checkout in the (JSON) session while the phone code is checked.
 */
final readonly class CheckoutData
{
    public function __construct(
        public string $customerName,
        public string $customerPhone,
        public ?string $customerEmail,
        public ?int $savedAddressId,
        public ?NewAddressData $newAddress,
        public bool $shouldSaveAddress,
        public PaymentMethod $paymentMethod,
        public ?int $expectedTotal,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ...get_object_vars($this),
            'newAddress' => $this->newAddress === null ? null : get_object_vars($this->newAddress),
            'paymentMethod' => $this->paymentMethod->value,
        ];
    }

    /**
     * @param  array<string, mixed>  $data  From toArray()
     */
    public static function fromArray(array $data): self
    {
        return new self(...[
            ...$data,
            'newAddress' => $data['newAddress'] === null ? null : new NewAddressData(...$data['newAddress']),
            'paymentMethod' => PaymentMethod::from($data['paymentMethod']),
        ]);
    }
}
