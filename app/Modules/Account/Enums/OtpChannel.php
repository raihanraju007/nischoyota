<?php

namespace App\Modules\Account\Enums;

enum OtpChannel: string
{
    case Sms = 'sms';
    case Email = 'email';
}
