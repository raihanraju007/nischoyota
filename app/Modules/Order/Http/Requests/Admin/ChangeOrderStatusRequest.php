<?php

namespace App\Modules\Order\Http\Requests\Admin;

use App\Modules\Order\Data\OrderStatusChangeData;
use App\Modules\Order\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Whether the move is allowed from the current status is checked by OrderStatusService under a lock.
 */
final class ChangeOrderStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:255'],
            'courier_name' => ['nullable', 'string', 'max:50'],
            'tracking_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function toData(): OrderStatusChangeData
    {
        return new OrderStatusChangeData(
            toStatus: $this->enum('status', OrderStatus::class),
            note: $this->filledText('note'),
            courierName: $this->filledText('courier_name'),
            trackingCode: $this->filledText('tracking_code'),
            userId: $this->user()->id,
        );
    }

    private function filledText(string $key): ?string
    {
        return $this->filled($key) ? $this->string($key)->trim()->toString() : null;
    }
}
