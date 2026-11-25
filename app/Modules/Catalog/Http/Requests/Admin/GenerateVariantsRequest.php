<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateVariantsRequest extends FormRequest
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
            'values' => ['nullable', 'array'],
            'values.*' => ['array'],
            'values.*.*' => ['integer'],
        ];
    }

    /**
     * @return array<int, list<int>> attribute id => value ids
     */
    public function selection(): array
    {
        $selection = [];

        foreach ($this->input('values', []) as $attributeId => $valueIds) {
            $selection[(int) $attributeId] = array_values(array_unique(array_map(intval(...), $valueIds)));
        }

        return $selection;
    }
}
