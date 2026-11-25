<?php

namespace App\Modules\Content\Repositories;

use App\Modules\Content\Models\Setting;

final class SettingRepository
{
    /**
     * @return array<string, mixed> key => value
     */
    public function allValues(): array
    {
        return Setting::query()->pluck('value', 'key')->all();
    }

    /**
     * @param  array<string, mixed>  $valuesByKey
     */
    public function saveMany(array $valuesByKey): void
    {
        foreach ($valuesByKey as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
