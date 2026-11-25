<?php

namespace App\Modules\Content\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Content\Enums\ContactMessageStatus;
use App\Modules\Content\Http\Requests\Admin\ListContactMessagesRequest;
use App\Modules\Content\Http\Requests\Admin\UpdateContactMessageStatusRequest;
use App\Modules\Content\Models\ContactMessage;
use App\Modules\Content\Services\ContactMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Contact form inbox for staff and admins (F-026).
 */
final class ContactMessageController extends Controller
{
    public function __construct(private readonly ContactMessageService $contactMessageService) {}

    public function index(ListContactMessagesRequest $request): View
    {
        $filter = $request->toFilter();

        return view('admin.messages.index', [
            'messages' => $this->contactMessageService->paginateForAdmin($filter),
            'tabCounts' => $this->contactMessageService->countPerTab($filter),
            'filter' => $filter,
            'statuses' => ContactMessageStatus::cases(),
        ]);
    }

    public function show(ContactMessage $message): View
    {
        return view('admin.messages.show', [
            'message' => $this->contactMessageService->loadForAdmin($message),
            'statuses' => ContactMessageStatus::cases(),
        ]);
    }

    public function updateStatus(UpdateContactMessageStatusRequest $request, ContactMessage $message): RedirectResponse
    {
        $this->contactMessageService->changeStatus($message, $request->status());

        return to_route('admin.messages.show', $message)
            ->with('success', __('Message marked as :status.', ['status' => mb_strtolower($request->status()->label())]));
    }
}
