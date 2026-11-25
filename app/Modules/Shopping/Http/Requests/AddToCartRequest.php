<?php

namespace App\Modules\Shopping\Http\Requests;

use App\Modules\Shopping\Services\CartService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Add to Cart / Buy Now from the product page or a product card (F-021). Quantity defaults to 1.
 */
final class AddToCartRequest extends FormRequest
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
            'variant_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:'.CartService::MAX_LINE_QUANTITY],
        ];
    }

    public function variantId(): int
    {
        return $this->integer('variant_id');
    }

    public function quantity(): int
    {
        return $this->integer('quantity', 1);
    }

    public function isBuyNow(): bool
    {
        return $this->boolean('buy_now');
    }
}
