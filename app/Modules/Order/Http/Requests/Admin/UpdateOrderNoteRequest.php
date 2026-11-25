<?php

namespace App\Modules\Order\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateOrderNoteRequest extends FormRequest
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
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function note(): ?string
    {
        return $this->filled('admin_note') ? $this->string('admin_note')->trim()->toString() : null;
    }
}
