<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use App\Modules\Catalog\Data\CategoryData;
use App\Modules\Catalog\Models\Category;
use App\Support\NormalizesStringInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used for both create and update; on update the route's {category} is ignored for slug uniqueness.
 */
final class SaveCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:'.self::SLUG_PATTERN, Rule::unique('categories', 'slug')->ignore($this->category())],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->whereNull('parent_id')],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'remove_image' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => __('Use only lowercase letters, numbers and dashes.'),
            'parent_id.exists' => __('Choose a top-level category as the parent.'),
        ];
    }

    public function toData(): CategoryData
    {
        return new CategoryData(
            name: $this->string('name')->trim()->toString(),
            slug: $this->filled('slug') ? $this->string('slug')->toString() : null,
            parentId: $this->filled('parent_id') ? $this->integer('parent_id') : null,
            image: $this->file('image'),
            shouldRemoveImage: $this->boolean('remove_image'),
            sortOrder: $this->integer('sort_order'),
            isActive: $this->boolean('is_active'),
            metaTitle: $this->filled('meta_title') ? $this->string('meta_title')->trim()->toString() : null,
            metaDescription: $this->filled('meta_description') ? $this->string('meta_description')->trim()->toString() : null,
        );
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings(['slug' => fn (string $slug): string => mb_strtolower(trim($slug))]);
    }

    private function category(): ?Category
    {
        $category = $this->route('category');

        return $category instanceof Category ? $category : null;
    }
}
