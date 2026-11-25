<?php

namespace App\Modules\Account\Exceptions;

use RuntimeException;

/**
 * A code could not be sent or checked; the message is shown next to the code field.
 */
final class OtpException extends RuntimeException
{
    public static function sendingTooOften(int $seconds): self
    {
        return new self(__('Please wait :seconds seconds before asking for another code.', ['seconds' => $seconds]));
    }

    public static function invalidOrExpired(): self
    {
        return new self(__('This code is wrong or has expired. Ask for a new code.'));
    }

    public static function wrongCode(int $triesLeft): self
    {
        return new self(trans_choice('Wrong code. :count try left.|Wrong code. :count tries left.', $triesLeft));
    }

    public static function tooManyAttempts(): self
    {
        return new self(__('Too many wrong codes. Ask for a new code.'));
    }
}
