<?php

namespace App\Modules\Content\Data;

final readonly class PageData
{
    public function __construct(
        public string $title,
        public ?string $slug,
        public ?string $subtitle,
        public string $body,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public bool $isPublished,
    ) {}
}
