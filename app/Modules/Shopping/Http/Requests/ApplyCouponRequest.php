<?php

namespace App\Modules\Shopping\Http\Requests;

use App\Support\NormalizesStringInput;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Coupon preview on the cart page (F-021). Codes are stored upper-case (F-011).
 */
final class ApplyCouponRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['code' => __('coupon code')];
    }

    public function code(): string
    {
        return $this->string('code')->toString();
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings(['code' => fn (string $code): string => mb_strtoupper(trim($code))]);
    }
}
