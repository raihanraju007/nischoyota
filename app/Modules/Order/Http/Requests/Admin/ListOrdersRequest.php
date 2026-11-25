<?php

namespace App\Modules\Order\Http\Requests\Admin;

use App\Modules\Order\Data\AdminOrderFilter;
use App\Modules\Order\Enums\OrderDateRange;
use App\Modules\Order\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListOrdersRequest extends FormRequest
{
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
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', Rule::enum(OrderDateRange::class)],
        ];
    }

    public function toFilter(): AdminOrderFilter
    {
        return new AdminOrderFilter(
            status: $this->enum('status', OrderStatus::class),
            search: $this->filled('search') ? $this->string('search')->trim()->toString() : null,
            dateRange: $this->enum('date', OrderDateRange::class) ?? OrderDateRange::AllTime,
        );
    }
}
