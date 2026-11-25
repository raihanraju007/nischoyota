<?php

namespace App\Modules\Account\Models;

use App\Modules\Account\Enums\OtpChannel;
use App\Modules\Account\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

/**
 * A one-time code. Only the hash is stored; see OtpService.
 */
#[Fillable(['recipient', 'channel', 'purpose', 'code_hash', 'attempts', 'expires_at', 'verified_at', 'ip_address'])]
#[Hidden(['code_hash'])]
class OtpCode extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => OtpChannel::class,
            'purpose' => OtpPurpose::class,
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}
