<?php

namespace App\Modules\Content\Http\Requests\Admin;

use App\Modules\Content\Enums\ContactMessageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateContactMessageStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(ContactMessageStatus::class)],
        ];
    }

    public function status(): ContactMessageStatus
    {
        return $this->enum('status', ContactMessageStatus::class);
    }
}
