<?php

namespace App\Modules\Content\Services;

use App\Modules\Content\Data\StoreSettings;
use App\Modules\Content\Enums\StoreSetting;
use App\Modules\Content\Repositories\SettingRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The one place that reads and writes store settings (ADR-011). Values are cached until the next save.
 */
final class SettingService
{
    private const CACHE_KEY = 'store_settings';

    public function __construct(private readonly SettingRepository $settingRepository) {}

    public function current(): StoreSettings
    {
        $saved = Cache::rememberForever(self::CACHE_KEY, $this->settingRepository->allValues(...));
        $value = fn (StoreSetting $setting): mixed => array_key_exists($setting->value, $saved) ? $saved[$setting->value] : $setting->defaultValue();

        return new StoreSettings(
            storeName: (string) $value(StoreSetting::StoreName),
            supportPhone: (string) $value(StoreSetting::SupportPhone),
            supportEmail: (string) $value(StoreSetting::SupportEmail),
            storeAddress: $value(StoreSetting::StoreAddress),
            supportHours: $value(StoreSetting::SupportHours),
            freeDeliveryThreshold: $value(StoreSetting::FreeDeliveryThreshold) === null ? null : (int) $value(StoreSetting::FreeDeliveryThreshold),
            lowStockThreshold: (int) $value(StoreSetting::LowStockThreshold),
        );
    }

    public function save(StoreSettings $settings): void
    {
        DB::transaction(fn () => $this->settingRepository->saveMany([
            StoreSetting::StoreName->value => $settings->storeName,
            StoreSetting::SupportPhone->value => $settings->supportPhone,
            StoreSetting::SupportEmail->value => $settings->supportEmail,
            StoreSetting::StoreAddress->value => $settings->storeAddress,
            StoreSetting::SupportHours->value => $settings->supportHours,
            StoreSetting::FreeDeliveryThreshold->value => $settings->freeDeliveryThreshold,
            StoreSetting::LowStockThreshold->value => $settings->lowStockThreshold,
        ]));

        Cache::forget(self::CACHE_KEY);
    }
}
