<?php

namespace App\Modules\Content\Services;

use App\Modules\Content\Data\ContactMessageData;
use App\Modules\Content\Data\ContactMessageFilter;
use App\Modules\Content\Enums\ContactMessageStatus;
use App\Modules\Content\Models\ContactMessage;
use App\Modules\Content\Repositories\ContactMessageRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Storefront contact messages (F-020) and the admin inbox (F-026).
 */
final class ContactMessageService
{
    public function __construct(private readonly ContactMessageRepository $contactMessageRepository) {}

    public function submit(ContactMessageData $data): ContactMessage
    {
        return $this->contactMessageRepository->create([
            'user_id' => $data->userId,
            'name' => $data->name,
            'phone' => $data->phone,
            'email' => $data->email,
            'subject' => $data->subject,
            'message' => $data->message,
            'status' => ContactMessageStatus::New,
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, ContactMessage>
     */
    public function paginateForAdmin(ContactMessageFilter $filter): LengthAwarePaginator
    {
        return $this->contactMessageRepository->paginateForAdmin($filter);
    }

    /**
     * @return array<string, int> each status value and 'all' => count
     */
    public function countPerTab(ContactMessageFilter $filter): array
    {
        $perStatus = $this->contactMessageRepository->countPerStatus($filter->search);
        $counts = [];

        foreach (ContactMessageStatus::cases() as $status) {
            $counts[$status->value] = $perStatus[$status->value] ?? 0;
        }

        return [...$counts, ContactMessageFilter::ALL => array_sum($perStatus)];
    }

    /**
     * Messages nobody has answered yet (sidebar badge).
     */
    public function newCount(): int
    {
        return $this->contactMessageRepository->countWithStatus(ContactMessageStatus::New);
    }

    public function loadForAdmin(ContactMessage $message): ContactMessage
    {
        return $this->contactMessageRepository->loadSender($message);
    }

    public function changeStatus(ContactMessage $message, ContactMessageStatus $status): void
    {
        $this->contactMessageRepository->updateStatus($message, $status);
    }
}
