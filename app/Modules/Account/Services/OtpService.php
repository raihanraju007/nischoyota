<?php

namespace App\Modules\Account\Services;

use App\Modules\Account\Enums\OtpChannel;
use App\Modules\Account\Enums\OtpPurpose;
use App\Modules\Account\Exceptions\OtpException;
use App\Modules\Account\Mail\OtpCodeMail;
use App\Modules\Account\Repositories\OtpCodeRepository;
use App\Modules\Account\Sms\SmsGateway;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * One-time codes (ADR-007, D2): 6 digits, hashed, 5 minutes, 5 tries; sending limited to 1/minute and 5/hour per recipient.
 */
final class OtpService
{
    public const EXPIRY_MINUTES = 5;

    public const MAX_ATTEMPTS = 5;

    private const SENDS_PER_MINUTE = 1;

    private const SENDS_PER_HOUR = 5;

    public function __construct(
        private readonly OtpCodeRepository $otpRepository,
        private readonly SmsGateway $smsGateway,
    ) {}

    /**
     * @throws OtpException when sending too often
     */
    public function send(string $recipient, OtpChannel $channel, OtpPurpose $purpose, ?string $ipAddress): void
    {
        $this->ensureNotSendingTooOften($recipient, $purpose);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->otpRepository->expireUnverified($recipient, $purpose);
        $this->otpRepository->create([
            'recipient' => $recipient,
            'channel' => $channel,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            'ip_address' => $ipAddress,
        ]);

        match ($channel) {
            OtpChannel::Sms => $this->smsGateway->send($recipient, str_replace(':code', $code, $purpose->message())),
            OtpChannel::Email => Mail::to($recipient)->send(new OtpCodeMail($code, $purpose)),
        };
    }

    /**
     * @throws OtpException when the code is wrong, expired or used up
     */
    public function verify(string $recipient, OtpPurpose $purpose, string $code): void
    {
        $otp = $this->otpRepository->latestUnverified($recipient, $purpose);

        if ($otp === null || $otp->expires_at->isPast()) {
            throw OtpException::invalidOrExpired();
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            throw OtpException::tooManyAttempts();
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $this->otpRepository->recordFailedAttempt($otp);
            $triesLeft = self::MAX_ATTEMPTS - $otp->attempts;

            throw $triesLeft > 0 ? OtpException::wrongCode($triesLeft) : OtpException::tooManyAttempts();
        }

        $this->otpRepository->markVerified($otp);
    }

    private function ensureNotSendingTooOften(string $recipient, OtpPurpose $purpose): void
    {
        $minuteKey = "otp-minute:{$purpose->value}:{$recipient}";
        $hourKey = "otp-hour:{$purpose->value}:{$recipient}";

        foreach ([$minuteKey => self::SENDS_PER_MINUTE, $hourKey => self::SENDS_PER_HOUR] as $key => $maxSends) {
            if (RateLimiter::tooManyAttempts($key, $maxSends)) {
                throw OtpException::sendingTooOften(RateLimiter::availableIn($key));
            }
        }

        RateLimiter::hit($minuteKey, 60);
        RateLimiter::hit($hourKey, 3600);
    }
}
