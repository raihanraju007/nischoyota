<?php

namespace App\Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Enums\ContactSubject;
use App\Modules\Content\Http\Requests\SendContactMessageRequest;
use App\Modules\Content\Services\ContactMessageService;
use App\Modules\Content\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Storefront contact page and form (F-020).
 */
final class ContactController extends Controller
{
    public function __construct(
        private readonly ContactMessageService $contactMessageService,
        private readonly SettingService $settingService,
    ) {}

    public function show(Request $request): View
    {
        return view('pages.contact', [
            'settings' => $this->settingService->current(),
            'subjects' => ContactSubject::options(),
            'customer' => $request->user(),
        ]);
    }

    /**
     * A bot that filled the honeypot gets the same answer, but nothing is saved.
     */
    public function store(SendContactMessageRequest $request): RedirectResponse
    {
        if (! $request->isFromBot()) {
            $this->contactMessageService->submit($request->toData());
        }

        return to_route('contact')->with('success', __("Message sent! We'll get back to you within 24 hours."));
    }
}
