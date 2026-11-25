<?php

namespace App\Modules\Account\Sms;

/**
 * Sends one SMS. The real provider (SSL Wireless, Alpha SMS…) is chosen in G-6; until then the log driver is used.
 */
interface SmsGateway
{
    /**
     * @param  string  $phone  Normalised 01XXXXXXXXX
     */
    public function send(string $phone, string $message): void;
}
