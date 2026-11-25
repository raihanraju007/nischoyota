<?php

namespace App\Modules\Order\Http\Requests;

use App\Support\NormalizesStringInput;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Guest order tracking (F-024): order number + the phone used on the order.
 */
final class TrackOrderRequest extends FormRequest
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
            'order_number' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'regex:'.PhoneNumber::PATTERN],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => __('Enter a valid Bangladeshi mobile number, e.g. 01712-345678.'),
        ];
    }

    public function orderNumber(): string
    {
        return $this->string('order_number')->toString();
    }

    public function phone(): string
    {
        return $this->string('phone')->toString();
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings(['phone' => fn (string $phone): string => PhoneNumber::normalize($phone) ?? $phone]);
    }
}
