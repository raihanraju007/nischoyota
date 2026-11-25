<?php

namespace App\Modules\Content\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Content\Http\Requests\Admin\UpdateStoreSettingsRequest;
use App\Modules\Content\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class SettingsController extends Controller
{
    public function __construct(private readonly SettingService $settingService) {}

    public function show(): View
    {
        return view('admin.settings', [
            'settings' => $this->settingService->current(),
        ]);
    }

    public function update(UpdateStoreSettingsRequest $request): RedirectResponse
    {
        $this->settingService->save($request->toSettings());

        return to_route('admin.settings')->with('success', __('Store settings saved.'));
    }
}
