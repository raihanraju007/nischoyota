<?php

namespace App\Modules\Catalog\Data;

final readonly class AttributeValueData
{
    public function __construct(
        public string $value,
        public ?string $colorHex,
        public int $sortOrder,
    ) {}
}
