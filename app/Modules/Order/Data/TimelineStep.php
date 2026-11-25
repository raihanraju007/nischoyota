<?php

namespace App\Modules\Order\Data;

/**
 * One step of the order timeline. $state: done | current | upcoming (the x-order.timeline component).
 */
final readonly class TimelineStep
{
    public function __construct(
        public string $title,
        public string $note,
        public string $state,
    ) {}

    /**
     * @return array{title: string, note: string, state: string}
     */
    public function toArray(): array
    {
        return ['title' => $this->title, 'note' => $this->note, 'state' => $this->state];
    }
}
