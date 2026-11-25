<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class EditAttributeRequest extends FormRequest
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
            'value' => ['nullable', 'integer'],
        ];
    }

    public function valueId(): ?int
    {
        return $this->filled('value') ? $this->integer('value') : null;
    }
}
