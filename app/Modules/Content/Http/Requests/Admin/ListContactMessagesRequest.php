<?php

namespace App\Modules\Content\Http\Requests\Admin;

use App\Modules\Content\Data\ContactMessageFilter;
use App\Modules\Content\Enums\ContactMessageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The inbox opens on "New"; status=all shows every message.
 */
final class ListContactMessagesRequest extends FormRequest
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
            'status' => ['nullable', Rule::in([ContactMessageFilter::ALL, ...array_column(ContactMessageStatus::cases(), 'value')])],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function toFilter(): ContactMessageFilter
    {
        $status = $this->string('status')->toString();

        return new ContactMessageFilter(
            status: $status === ContactMessageFilter::ALL ? null : (ContactMessageStatus::tryFrom($status) ?? ContactMessageStatus::New),
            search: $this->filled('search') ? $this->string('search')->trim()->toString() : null,
        );
    }
}
