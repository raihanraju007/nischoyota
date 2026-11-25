<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use App\Modules\Catalog\Data\ProductFilter;
use App\Modules\Catalog\Enums\ProductListTab;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListProductsRequest extends FormRequest
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
            'tab' => ['nullable', Rule::enum(ProductListTab::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer'],
        ];
    }

    public function toFilter(): ProductFilter
    {
        return new ProductFilter(
            tab: $this->enum('tab', ProductListTab::class) ?? ProductListTab::All,
            search: $this->filled('search') ? $this->string('search')->trim()->toString() : null,
            categoryId: $this->filled('category') ? $this->integer('category') : null,
        );
    }
}
