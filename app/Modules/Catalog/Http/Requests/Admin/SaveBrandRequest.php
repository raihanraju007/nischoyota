<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use App\Modules\Catalog\Data\BrandData;
use App\Modules\Catalog\Models\Brand;
use App\Support\NormalizesStringInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used for both create and update; on update the route's {brand} is ignored for uniqueness.
 */
final class SaveBrandRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100', Rule::unique('brands', 'name')->ignore($this->brand())],
            'slug' => ['nullable', 'string', 'max:120', 'regex:'.self::SLUG_PATTERN, Rule::unique('brands', 'slug')->ignore($this->brand())],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'remove_logo' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
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

    public function toData(): BrandData
    {
        return new BrandData(
            name: $this->string('name')->trim()->toString(),
            slug: $this->filled('slug') ? $this->string('slug')->toString() : null,
            logo: $this->file('logo'),
            shouldRemoveLogo: $this->boolean('remove_logo'),
            isActive: $this->boolean('is_active'),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings([
            'name' => fn (string $name): string => trim($name),
            'slug' => fn (string $slug): string => mb_strtolower(trim($slug)),
        ]);
    }

    private function brand(): ?Brand
    {
        $brand = $this->route('brand');

        return $brand instanceof Brand ? $brand : null;
    }
}
