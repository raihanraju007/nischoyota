<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use App\Modules\Catalog\Enums\BulkProductAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BulkProductActionRequest extends FormRequest
{
    private const MAX_PRODUCTS = 100;

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
            'action' => ['required', Rule::enum(BulkProductAction::class)],
            'ids' => ['required', 'array', 'max:'.self::MAX_PRODUCTS],
            'ids.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => __('Select at least one product.'),
            'action.required' => __('Choose an action.'),
        ];
    }

    public function action(): BulkProductAction
    {
        return $this->enum('action', BulkProductAction::class);
    }

    /**
     * @return list<int>
     */
    public function productIds(): array
    {
        return array_map(intval(...), $this->input('ids'));
    }
}
