<?php

namespace App\Modules\Content\Http\Requests\Admin;

use App\Modules\Content\Data\PageData;
use App\Modules\Content\Models\Page;
use App\Support\NormalizesStringInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SavePageRequest extends FormRequest
{
    use NormalizesStringInput;

    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function authorize(): bool
    {
        return $this->user()->can('manage-store');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $page = $this->route('page');

        return [
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:'.self::SLUG_PATTERN, Rule::unique('pages', 'slug')->ignore($page instanceof Page ? $page : null)],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:50000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_published' => ['nullable', 'boolean'],
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

    public function toData(): PageData
    {
        return new PageData(
            title: $this->string('title')->trim()->toString(),
            slug: $this->filled('slug') ? mb_strtolower($this->string('slug')->trim()->toString()) : null,
            subtitle: $this->filled('subtitle') ? $this->string('subtitle')->trim()->toString() : null,
            body: str_replace("\r\n", "\n", $this->string('body')->toString()),
            metaTitle: $this->filled('meta_title') ? $this->string('meta_title')->trim()->toString() : null,
            metaDescription: $this->filled('meta_description') ? $this->string('meta_description')->trim()->toString() : null,
            isPublished: $this->boolean('is_published'),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings(['slug' => fn (string $slug): string => mb_strtolower(trim($slug))]);
    }
}
