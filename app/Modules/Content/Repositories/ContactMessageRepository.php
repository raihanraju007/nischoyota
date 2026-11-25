<?php

namespace App\Modules\Content\Repositories;

use App\Modules\Content\Data\ContactMessageFilter;
use App\Modules\Content\Enums\ContactMessageStatus;
use App\Modules\Content\Models\ContactMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ContactMessageRepository
{
    private const PER_PAGE = 20;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ContactMessage
    {
        return ContactMessage::query()->create($attributes);
    }

    /**
     * @return LengthAwarePaginator<int, ContactMessage>
     */
    public function paginateForAdmin(ContactMessageFilter $filter): LengthAwarePaginator
    {
        return $this->searchQuery($filter->search)
            ->when($filter->status !== null, fn (Builder $query) => $query->where('status', $filter->status))
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return array<string, int> status value => count, with the search applied
     */
    public function countPerStatus(?string $search): array
    {
        return $this->searchQuery($search)
            ->toBase()
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    public function countWithStatus(ContactMessageStatus $status): int
    {
        return ContactMessage::query()->where('status', $status)->count();
    }

    public function loadSender(ContactMessage $message): ContactMessage
    {
        return $message->load('user');
    }

    public function updateStatus(ContactMessage $message, ContactMessageStatus $status): void
    {
        $message->update(['status' => $status]);
    }

    /**
     * @return Builder<ContactMessage>
     */
    private function searchQuery(?string $search): Builder
    {
        return ContactMessage::query()
            ->when($search !== null, function (Builder $query) use ($search): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
                $digits = preg_replace('/\D/', '', $search) ?? '';

                $query->where(fn (Builder $query) => $query
                    ->whereLike('name', $term)
                    ->orWhereLike('email', $term)
                    ->when(strlen($digits) >= 4, fn (Builder $query) => $query->orWhereLike('phone', '%'.$digits.'%')));
            });
    }
}
