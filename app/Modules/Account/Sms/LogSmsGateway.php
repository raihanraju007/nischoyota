<?php

namespace App\Modules\Account\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Development driver: writes the SMS to the log (storage/logs/laravel.log) instead of sending it.
 */
final class LogSmsGateway implements SmsGateway
{
    public function send(string $phone, string $message): void
    {
        Log::info("SMS to {$phone}: {$message}");
    }
}
