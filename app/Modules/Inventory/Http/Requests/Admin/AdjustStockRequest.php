<?php

namespace App\Modules\Inventory\Http\Requests\Admin;

use App\Modules\Inventory\Data\StockAdjustmentData;
use App\Modules\Inventory\Enums\StockMovementReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * quantity: amount to add/remove (1–100,000), or the counted total for a stock count (0–1,000,000).
 */
final class AdjustStockRequest extends FormRequest
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
        $isStockCount = $this->input('reason') === StockMovementReason::StockCount->value;

        return [
            'reason' => ['required', Rule::enum(StockMovementReason::class)->only(StockMovementReason::manualReasons())],
            'quantity' => ['required', 'integer', 'min:'.($isStockCount ? 0 : 1), 'max:'.($isStockCount ? 1_000_000 : 100_000)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toData(): StockAdjustmentData
    {
        return new StockAdjustmentData(
            reason: $this->enum('reason', StockMovementReason::class),
            quantity: $this->integer('quantity'),
            note: $this->filled('note') ? $this->string('note')->trim()->toString() : null,
            userId: $this->user()->id,
        );
    }
}
