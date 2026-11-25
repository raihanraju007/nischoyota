<?php

namespace App\Modules\Account\Enums;

enum OtpPurpose: string
{
    case PhoneVerification = 'phone_verification';
    case PasswordReset = 'password_reset';
    case PhoneChange = 'phone_change';
    case CheckoutPhone = 'checkout_phone';

    /**
     * Text of the SMS / email, with :code replaced.
     */
    public function message(): string
    {
        return match ($this) {
            self::PhoneVerification => __('Your Nischoyota verification code is :code. It expires in 5 minutes.'),
            self::PasswordReset => __('Your Nischoyota password reset code is :code. It expires in 5 minutes. If you did not ask for it, ignore this message.'),
            self::PhoneChange => __('Your Nischoyota code to confirm this new phone number is :code. It expires in 5 minutes.'),
            self::CheckoutPhone => __('Your Nischoyota code to confirm your order is :code. It expires in 5 minutes.'),
        };
    }
}
