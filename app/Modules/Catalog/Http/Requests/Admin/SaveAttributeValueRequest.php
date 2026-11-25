<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use App\Modules\Catalog\Data\AttributeValueData;
use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Support\NormalizesStringInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A value must be unique within its attribute ("White" may exist in Color and in Frame Color).
 */
final class SaveAttributeValueRequest extends FormRequest
{
    use NormalizesStringInput;

    private const COLOR_PATTERN = '/^#[0-9A-F]{6}$/';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var ProductAttribute $attribute */
        $attribute = $this->route('attribute');
        $value = $this->route('value');

        return [
            'value' => [
                'required', 'string', 'max:50',
                Rule::unique('attribute_values', 'value')
                    ->where('attribute_id', $attribute->id)
                    ->ignore($value instanceof AttributeValue ? $value : null),
            ],
            'color_hex' => ['nullable', 'regex:'.self::COLOR_PATTERN],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'value.unique' => __('This attribute already has that value.'),
            'color_hex.regex' => __('Use a hex colour like #1160A8.'),
        ];
    }

    public function toData(): AttributeValueData
    {
        return new AttributeValueData(
            value: $this->string('value')->toString(),
            colorHex: $this->filled('color_hex') ? $this->string('color_hex')->toString() : null,
            sortOrder: $this->integer('sort_order'),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings([
            'value' => fn (string $value): string => trim($value),
            'color_hex' => fn (string $colorHex): string => mb_strtoupper(trim($colorHex)),
        ]);
    }
}
