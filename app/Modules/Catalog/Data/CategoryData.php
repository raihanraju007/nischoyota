<?php

namespace App\Modules\Catalog\Data;

use Illuminate\Http\UploadedFile;

final readonly class CategoryData
{
    public function __construct(
        public string $name,
        public ?string $slug,
        public ?int $parentId,
        public ?UploadedFile $image,
        public bool $shouldRemoveImage,
        public int $sortOrder,
        public bool $isActive,
        public ?string $metaTitle,
        public ?string $metaDescription,
    ) {}
}
