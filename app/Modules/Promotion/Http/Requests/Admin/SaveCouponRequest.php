<?php

namespace App\Modules\Promotion\Http\Requests\Admin;

use App\Modules\Promotion\Data\CouponData;
use App\Modules\Promotion\Enums\CouponType;
use App\Modules\Promotion\Models\Coupon;
use App\Support\Money;
use App\Support\NormalizesStringInput;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Money is typed in taka. Dates are Bangladesh days: start at 00:00, end at 23:59:59 (config shop.timezone), stored in UTC.
 */
final class SaveCouponRequest extends FormRequest
{
    use NormalizesStringInput;

    private const CODE_PATTERN = '/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/';

    public function authorize(): bool
    {
        return $this->user()->can('manage-store');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $coupon = $this->route('coupon');
        $isPercent = $this->input('type') === CouponType::Percent->value;

        return [
            'code' => ['required', 'string', 'min:3', 'max:30', 'regex:'.self::CODE_PATTERN, Rule::unique('coupons', 'code')->ignore($coupon instanceof Coupon ? $coupon : null)],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => $isPercent
                ? ['required', 'integer', 'min:1', 'max:100']
                : ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:100000'],
            'min_order_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:10000000'],
            'max_discount_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0.01', 'max:100000'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'starts_at' => ['nullable', 'date_format:Y-m-d'],
            'expires_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => __('Use capital letters, numbers and dashes, e.g. SAVE120.'),
            'expires_at.after_or_equal' => __('The end date must be on or after the start date.'),
        ];
    }

    public function toData(): CouponData
    {
        $type = $this->enum('type', CouponType::class);

        return new CouponData(
            code: $this->string('code')->toString(),
            type: $type,
            value: $type === CouponType::Fixed ? Money::fromTaka($this->input('value')) : $this->integer('value'),
            minOrderAmount: $this->takaAsPaisa('min_order_amount'),
            maxDiscountAmount: $this->takaAsPaisa('max_discount_amount'),
            usageLimit: $this->filled('usage_limit') ? $this->integer('usage_limit') : null,
            usageLimitPerUser: $this->filled('usage_limit_per_user') ? $this->integer('usage_limit_per_user') : null,
            startsAt: $this->storeDay('starts_at')?->startOfDay()->utc(),
            expiresAt: $this->storeDay('expires_at')?->endOfDay()->utc(),
            isActive: $this->boolean('is_active'),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings(['code' => fn (string $code): string => mb_strtoupper(trim($code))]);
    }

    private function takaAsPaisa(string $key): ?int
    {
        return $this->filled($key) ? Money::fromTaka($this->input($key)) : null;
    }

    private function storeDay(string $key): ?CarbonImmutable
    {
        return $this->filled($key)
            ? CarbonImmutable::createFromFormat('Y-m-d', $this->string($key)->toString(), config('shop.timezone'))
            : null;
    }
}
