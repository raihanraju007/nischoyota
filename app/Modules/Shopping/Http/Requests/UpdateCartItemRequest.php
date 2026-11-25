<?php

namespace App\Modules\Shopping\Http\Requests;

use App\Modules\Shopping\Services\CartService;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateCartItemRequest extends FormRequest
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
            'quantity' => ['required', 'integer', 'min:1', 'max:'.CartService::MAX_LINE_QUANTITY],
        ];
    }

    public function quantity(): int
    {
        return $this->integer('quantity');
    }
}
