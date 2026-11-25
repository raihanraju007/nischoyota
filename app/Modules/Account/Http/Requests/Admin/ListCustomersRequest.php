<?php

namespace App\Modules\Account\Http\Requests\Admin;

use App\Modules\Account\Data\CustomerFilter;
use App\Modules\Account\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListCustomersRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(UserStatus::class)],
        ];
    }

    public function toFilter(): CustomerFilter
    {
        return new CustomerFilter(
            search: $this->filled('search') ? $this->string('search')->trim()->toString() : null,
            status: $this->enum('status', UserStatus::class),
        );
    }
}
