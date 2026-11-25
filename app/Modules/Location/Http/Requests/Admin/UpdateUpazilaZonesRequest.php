<?php

namespace App\Modules\Location\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * upazilas[{upazila id}] = zone id, or empty for "same as district".
 */
final class UpdateUpazilaZonesRequest extends FormRequest
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
            'upazilas' => ['required', 'array'],
            'upazilas.*' => ['nullable', 'integer', Rule::exists('delivery_zones', 'id')],
        ];
    }

    /**
     * @return array<int, int|null>
     */
    public function zoneIdByUpazilaId(): array
    {
        return array_map(fn (mixed $zoneId): ?int => filled($zoneId) ? (int) $zoneId : null, $this->input('upazilas'));
    }
}
