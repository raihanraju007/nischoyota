<?php

namespace App\Modules\Content\Http\Requests\Admin;

use App\Modules\Content\Data\StoreSettings;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Free-delivery threshold is typed in taka (empty = never free) and stored as paisa.
 */
final class UpdateStoreSettingsRequest extends FormRequest
{
    private const PHONE_PATTERN = '/^\+?[0-9][0-9 \-]{5,18}$/';

    public function authorize(): bool
    {
        return $this->user()->can('manage-store');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:100'],
            'support_phone' => ['required', 'string', 'max:20', 'regex:'.self::PHONE_PATTERN],
            'support_email' => ['required', 'email', 'max:255'],
            'store_address' => ['nullable', 'string', 'max:255'],
            'support_hours' => ['nullable', 'string', 'max:100'],
            'free_delivery_threshold' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'support_phone.regex' => __('Use digits, spaces, dashes and an optional leading +, e.g. +880 9612-345678.'),
        ];
    }

    public function toSettings(): StoreSettings
    {
        return new StoreSettings(
            storeName: $this->string('store_name')->trim()->toString(),
            supportPhone: $this->string('support_phone')->trim()->toString(),
            supportEmail: $this->string('support_email')->trim()->lower()->toString(),
            storeAddress: $this->filled('store_address') ? $this->string('store_address')->trim()->toString() : null,
            supportHours: $this->filled('support_hours') ? $this->string('support_hours')->trim()->toString() : null,
            freeDeliveryThreshold: $this->filled('free_delivery_threshold') ? Money::fromTaka($this->input('free_delivery_threshold')) : null,
            lowStockThreshold: $this->integer('low_stock_threshold'),
        );
    }
}
