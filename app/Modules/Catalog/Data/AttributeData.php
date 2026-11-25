<?php

namespace App\Modules\Catalog\Data;

final readonly class AttributeData
{
    public function __construct(
        public string $name,
        public ?string $slug,
    ) {}
}
