<?php

namespace App\Modules\Review\Http\Requests\Admin;

use App\Modules\Review\Enums\ReviewStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListReviewsRequest extends FormRequest
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
            'status' => ['nullable', Rule::enum(ReviewStatus::class)],
        ];
    }

    /**
     * The moderation queue opens on pending reviews.
     */
    public function status(): ReviewStatus
    {
        return $this->enum('status', ReviewStatus::class) ?? ReviewStatus::Pending;
    }
}
