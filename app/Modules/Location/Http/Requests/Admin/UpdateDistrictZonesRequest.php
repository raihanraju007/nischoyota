<?php

namespace App\Modules\Location\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * districts[{district id}] = zone id.
 */
final class UpdateDistrictZonesRequest extends FormRequest
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
        return [
            'districts' => ['required', 'array'],
            'districts.*' => ['required', 'integer', Rule::exists('delivery_zones', 'id')],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function zoneIdByDistrictId(): array
    {
        return array_map(intval(...), $this->input('districts'));
    }
}
