<?php

namespace App\Modules\Account\Services;

use App\Modules\Account\Enums\OtpChannel;
use App\Modules\Account\Enums\OtpPurpose;
use App\Modules\Account\Exceptions\OtpException;
use App\Modules\Account\Models\User;
use App\Modules\Account\Repositories\UserRepository;

/**
 * The signed-in customer's own details (F-015).
 */
final class ProfileService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly OtpService $otpService,
    ) {}

    public function updateProfile(User $user, string $name, ?string $email): void
    {
        $this->userRepository->updateProfile($user, $name, $email);
    }

    /**
     * @throws OtpException
     */
    public function sendPhoneChangeCode(string $newPhone, ?string $ipAddress): void
    {
        $this->otpService->send($newPhone, OtpChannel::Sms, OtpPurpose::PhoneChange, $ipAddress);
    }

    /**
     * @throws OtpException
     */
    public function confirmPhoneChange(User $user, string $newPhone, string $code): void
    {
        $this->otpService->verify($newPhone, OtpPurpose::PhoneChange, $code);
        $this->userRepository->changePhone($user, $newPhone);
    }
}
