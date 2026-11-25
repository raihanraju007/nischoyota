<?php

namespace App\Modules\Account\Http\Requests;

use App\Modules\Account\Data\AddressData;
use App\Modules\Account\Enums\AddressLabel;
use App\Support\NormalizesStringInput;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The district must belong to the division and the upazila to the district.
 */
final class SaveAddressRequest extends FormRequest
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
        return [
            'label' => ['required', Rule::enum(AddressLabel::class)],
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:'.PhoneNumber::PATTERN],
            'division_id' => ['required', 'integer', Rule::exists('divisions', 'id')],
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')->where('division_id', $this->integer('division_id'))],
            'upazila_id' => ['required', 'integer', Rule::exists('upazilas', 'id')->where('district_id', $this->integer('district_id'))],
            'area' => ['nullable', 'string', 'max:100'],
            'address_line' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'digits:4'],
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
        ];
    }

    public function toData(): AddressData
    {
        return new AddressData(
            label: $this->enum('label', AddressLabel::class),
            recipientName: $this->string('recipient_name')->trim()->toString(),
            phone: $this->string('phone')->toString(),
            divisionId: $this->integer('division_id'),
            districtId: $this->integer('district_id'),
            upazilaId: $this->integer('upazila_id'),
            area: $this->filled('area') ? $this->string('area')->trim()->toString() : null,
            addressLine: $this->string('address_line')->trim()->toString(),
            postalCode: $this->filled('postal_code') ? $this->string('postal_code')->trim()->toString() : null,
        );
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings(['phone' => fn (string $phone): string => PhoneNumber::normalize($phone) ?? $phone]);
    }
}
