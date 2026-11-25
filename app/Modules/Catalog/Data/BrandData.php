<?php

namespace App\Modules\Catalog\Data;

use Illuminate\Http\UploadedFile;

final readonly class BrandData
{
    public function __construct(
        public string $name,
        public ?string $slug,
        public ?UploadedFile $logo,
        public bool $shouldRemoveLogo,
        public bool $isActive,
        public ?string $nameBn = null,
    ) {}
}
