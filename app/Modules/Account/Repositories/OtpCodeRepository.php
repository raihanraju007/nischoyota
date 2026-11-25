<?php

namespace App\Modules\Account\Repositories;

use App\Modules\Account\Enums\OtpPurpose;
use App\Modules\Account\Models\OtpCode;

final class OtpCodeRepository
{
    public function latestUnverified(string $recipient, OtpPurpose $purpose): ?OtpCode
    {
        return OtpCode::query()
            ->where('recipient', $recipient)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();
    }

    /**
     * Older codes stop working when a new one is sent.
     */
    public function expireUnverified(string $recipient, OtpPurpose $purpose): void
    {
        OtpCode::query()
            ->where('recipient', $recipient)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): OtpCode
    {
        return OtpCode::query()->create($attributes);
    }

    public function recordFailedAttempt(OtpCode $otp): void
    {
        $otp->increment('attempts');
    }

    public function markVerified(OtpCode $otp): void
    {
        $otp->forceFill(['verified_at' => now()])->save();
    }
}
