<?php

namespace App\Modules\Order\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class PrintPackingSlipsRequest extends FormRequest
{
    public const MAX_ORDERS = 100;

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
            'ids' => ['required', 'array', 'max:'.self::MAX_ORDERS],
            'ids.*' => ['integer', 'exists:orders,id'],
            'move_to_processing' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => __('Select the orders to print.'),
            'ids.max' => __('Print at most :max orders at a time.', ['max' => self::MAX_ORDERS]),
        ];
    }

    /**
     * @return list<int>
     */
    public function orderIds(): array
    {
        return array_values(array_unique(array_map(intval(...), $this->input('ids', []))));
    }

    public function shouldMoveToProcessing(): bool
    {
        return $this->boolean('move_to_processing');
    }
}
