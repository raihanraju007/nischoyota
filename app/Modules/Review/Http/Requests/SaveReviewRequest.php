<?php

namespace App\Modules\Review\Http\Requests;

use App\Modules\Account\Enums\UserRole;
use App\Modules\Review\Data\ReviewData;
use App\Modules\Review\Models\Review;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Only customers review products; staff and admin accounts get 403.
 */
final class SaveReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === UserRole::Customer;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:'.Review::MIN_RATING.','.Review::MAX_RATING],
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * Validation errors go back to the open Reviews tab, where the form is.
     */
    protected function getRedirectUrl(): string
    {
        return route('product', $this->route('slug')).'#tab-reviews';
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => __('Choose a star rating.'),
        ];
    }

    public function toData(): ReviewData
    {
        return new ReviewData(
            rating: $this->integer('rating'),
            comment: $this->string('comment')->trim()->toString(),
        );
    }
}
