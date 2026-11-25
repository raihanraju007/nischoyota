<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use App\Modules\Catalog\Data\AttributeData;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Support\NormalizesStringInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveAttributeRequest extends FormRequest
{
    use NormalizesStringInput;

    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

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
            'name' => ['required', 'string', 'max:50', Rule::unique('attributes', 'name')->ignore($this->productAttribute())],
            'slug' => ['nullable', 'string', 'max:60', 'regex:'.self::SLUG_PATTERN, Rule::unique('attributes', 'slug')->ignore($this->productAttribute())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => __('Use only lowercase letters, numbers and dashes.'),
        ];
    }

    public function toData(): AttributeData
    {
        return new AttributeData(
            name: $this->string('name')->toString(),
            slug: $this->filled('slug') ? $this->string('slug')->toString() : null,
        );
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings([
            'name' => fn (string $name): string => trim($name),
            'slug' => fn (string $slug): string => mb_strtolower(trim($slug)),
        ]);
    }

    private function productAttribute(): ?ProductAttribute
    {
        $attribute = $this->route('attribute');

        return $attribute instanceof ProductAttribute ? $attribute : null;
    }
}
