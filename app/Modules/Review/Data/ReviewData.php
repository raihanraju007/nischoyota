<?php

namespace App\Modules\Review\Data;

final readonly class ReviewData
{
    public function __construct(
        public int $rating,
        public string $comment,
    ) {}
}
