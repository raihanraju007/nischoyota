<?php

namespace App\Modules\Inventory\Http\Requests\Admin;

use App\Modules\Inventory\Data\StockFilter;
use App\Modules\Inventory\Enums\StockListTab;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListStockRequest extends FormRequest
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
            'tab' => ['nullable', Rule::enum(StockListTab::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function toFilter(): StockFilter
    {
        return new StockFilter(
            tab: $this->enum('tab', StockListTab::class) ?? StockListTab::All,
            search: $this->filled('search') ? $this->string('search')->trim()->toString() : null,
        );
    }
}
