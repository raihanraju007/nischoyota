<?php

namespace App\Modules\Order\Http\Requests;

use App\Modules\Order\Data\CheckoutData;
use App\Modules\Order\Data\NewAddressData;
use App\Modules\Order\Enums\PaymentMethod;
use App\Support\NormalizesStringInput;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Checkout form (F-023). A signed-in customer sends address_id (a saved address) or leaves it empty and
 * types a new address; a guest always types one. expected_total is the total shown (paisa), only compared.
 */
final class PlaceOrderRequest extends FormRequest
{
    use NormalizesStringInput;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $isNewAddress = ! $this->filled('address_id');

        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:'.PhoneNumber::PATTERN],
            'email' => ['nullable', 'email', 'max:255'],
            'address_id' => [Rule::prohibitedIf($this->user() === null), 'nullable', 'integer'],
            'division_id' => [Rule::requiredIf($isNewAddress), 'nullable', 'integer', Rule::exists('divisions', 'id')],
            'district_id' => [Rule::requiredIf($isNewAddress), 'nullable', 'integer', Rule::exists('districts', 'id')->where('division_id', $this->integer('division_id'))],
            'upazila_id' => [Rule::requiredIf($isNewAddress), 'nullable', 'integer', Rule::exists('upazilas', 'id')->where('district_id', $this->integer('district_id'))],
            'area' => ['nullable', 'string', 'max:100'],
            'address_line' => [Rule::requiredIf($isNewAddress), 'nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'digits:4'],
            'save_address' => ['nullable', 'boolean'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'terms' => ['accepted'],
            'expected_total' => ['nullable', 'integer', 'min:0'],
            'checkout_token' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => __('Enter a valid Bangladeshi mobile number, e.g. 01712-345678.'),
            'district_id.exists' => __('Choose a district in the selected division.'),
            'upazila_id.exists' => __('Choose an upazila / thana in the selected district.'),
            'terms.accepted' => __('Please accept the Terms & Conditions.'),
        ];
    }

    public function checkoutToken(): string
    {
        return $this->string('checkout_token')->toString();
    }

    public function toData(): CheckoutData
    {
        $isNewAddress = ! $this->filled('address_id');

        return new CheckoutData(
            customerName: $this->string('name')->trim()->toString(),
            customerPhone: $this->string('phone')->toString(),
            customerEmail: $this->filled('email') ? $this->string('email')->trim()->toString() : null,
            savedAddressId: $isNewAddress ? null : $this->integer('address_id'),
            newAddress: $isNewAddress ? new NewAddressData(
                divisionId: $this->integer('division_id'),
                districtId: $this->integer('district_id'),
                upazilaId: $this->integer('upazila_id'),
                area: $this->filled('area') ? $this->string('area')->trim()->toString() : null,
                addressLine: $this->string('address_line')->trim()->toString(),
                postalCode: $this->filled('postal_code') ? $this->string('postal_code')->trim()->toString() : null,
            ) : null,
            shouldSaveAddress: $this->boolean('save_address'),
            paymentMethod: $this->enum('payment_method', PaymentMethod::class),
            expectedTotal: $this->filled('expected_total') ? $this->integer('expected_total') : null,
        );
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings(['phone' => fn (string $phone): string => PhoneNumber::normalize($phone) ?? $phone]);
    }
}
