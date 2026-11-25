<?php

namespace App\Modules\Location\Http\Requests\Admin;

use App\Modules\Location\Data\DeliveryZoneData;
use App\Modules\Location\Models\DeliveryZone;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Charge is typed in taka and stored as paisa.
 */
final class SaveDeliveryZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-store');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $zone = $this->route('delivery_zone');

        return [
            'name' => ['required', 'string', 'max:60', Rule::unique('delivery_zones', 'name')->ignore($zone instanceof DeliveryZone ? $zone : null)],
            'charge' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:10000'],
            'min_days' => ['required', 'integer', 'min:1', 'max:30'],
            'max_days' => ['required', 'integer', 'min:1', 'max:30', 'gte:min_days'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'max_days.gte' => __('The latest day must be the same as or after the earliest day.'),
        ];
    }

    public function toData(): DeliveryZoneData
    {
        return new DeliveryZoneData(
            name: $this->string('name')->trim()->toString(),
            charge: Money::fromTaka($this->input('charge')),
            minDays: $this->integer('min_days'),
            maxDays: $this->integer('max_days'),
            isActive: $this->boolean('is_active'),
            sortOrder: $this->integer('sort_order'),
        );
    }
}
