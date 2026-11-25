<?php

namespace App\Modules\Account\Services;

use App\Modules\Account\Data\CustomerLoginData;
use App\Modules\Account\Data\RegistrationData;
use App\Modules\Account\Data\RegistrationResult;
use App\Modules\Account\Enums\OtpChannel;
use App\Modules\Account\Enums\OtpPurpose;
use App\Modules\Account\Exceptions\CustomerLoginFailedException;
use App\Modules\Account\Exceptions\OtpException;
use App\Modules\Account\Exceptions\PhoneNotVerifiedException;
use App\Modules\Account\Models\User;
use App\Modules\Account\Repositories\UserRepository;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Storefront register / login / phone verification / password reset (F-014).
 */
final class CustomerAuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly OtpService $otpService,
        private readonly StatefulGuard $guard,
    ) {}

    /**
     * Saves the unverified customer and sends the verification code. If a code was sent too recently,
     * the account is still saved and the result carries the "please wait" notice.
     */
    public function register(RegistrationData $data): RegistrationResult
    {
        $user = DB::transaction(fn (): User => $this->userRepository->saveUnverifiedCustomer(
            $this->userRepository->findByPhone($data->phone),
            $data,
        ));

        try {
            $this->sendPhoneVerificationCode($user, $data->ipAddress);
        } catch (OtpException $exception) {
            return new RegistrationResult($user, $exception->getMessage());
        }

        return new RegistrationResult($user, codeNotice: null);
    }

    /**
     * The customer waiting for phone verification (id kept in the session); null when verified or gone.
     */
    public function findPendingCustomer(int $userId): ?User
    {
        $user = $this->userRepository->findById($userId);

        return $user?->phone_verified_at === null ? $user : null;
    }

    /**
     * @throws OtpException
     */
    public function sendPhoneVerificationCode(User $user, ?string $ipAddress): void
    {
        $this->otpService->send($user->phone, OtpChannel::Sms, OtpPurpose::PhoneVerification, $ipAddress);
    }

    /**
     * @throws OtpException
     */
    public function verifyPhoneAndLogin(User $user, string $code): void
    {
        $this->otpService->verify($user->phone, OtpPurpose::PhoneVerification, $code);
        $this->userRepository->markPhoneVerified($user);
        $this->completeLogin($user, shouldRemember: false);
    }

    /**
     * @throws CustomerLoginFailedException
     * @throws PhoneNotVerifiedException
     */
    public function login(CustomerLoginData $data): User
    {
        $user = $this->userRepository->findByLogin($data->login);

        if ($user === null || ! Hash::check($data->password, $user->password)) {
            throw CustomerLoginFailedException::invalidCredentials();
        }

        if (! $user->status->canSignIn()) {
            throw CustomerLoginFailedException::accountBlocked();
        }

        if ($user->phone_verified_at === null) {
            throw new PhoneNotVerifiedException($user);
        }

        $this->completeLogin($user, $data->shouldRemember);

        return $user;
    }

    public function logout(): void
    {
        $this->guard->logout();
    }

    /**
     * Sends a reset code to the phone or email of an existing account. Returns the recipient to verify later.
     * Unknown accounts get no code but the same answer, so nobody can find out who has an account.
     *
     * @throws OtpException
     */
    public function sendPasswordResetCode(string $login, ?string $ipAddress): string
    {
        $user = $this->userRepository->findByLogin($login);
        $channel = str_contains($login, '@') ? OtpChannel::Email : OtpChannel::Sms;
        $recipient = $channel === OtpChannel::Email
            ? ($user->email ?? mb_strtolower(trim($login)))
            : ($user->phone ?? PhoneNumber::normalize($login) ?? trim($login));

        if ($user !== null) {
            $this->otpService->send($recipient, $channel, OtpPurpose::PasswordReset, $ipAddress);
        }

        return $recipient;
    }

    /**
     * @throws OtpException
     */
    public function resetPasswordAndLogin(string $recipient, string $code, string $newPassword): void
    {
        $this->otpService->verify($recipient, OtpPurpose::PasswordReset, $code);

        $isPhone = ! str_contains($recipient, '@');
        $user = $isPhone ? $this->userRepository->findByPhone($recipient) : $this->userRepository->findByEmail($recipient);

        if ($user === null) {
            throw OtpException::invalidOrExpired();
        }

        DB::transaction(function () use ($user, $newPassword, $isPhone): void {
            $this->userRepository->updatePassword($user, $newPassword);

            if ($isPhone) {
                $this->userRepository->markPhoneVerified($user);
            }
        });

        if ($user->status->canSignIn()) {
            $this->completeLogin($user, shouldRemember: false);
        }
    }

    private function completeLogin(User $user, bool $shouldRemember): void
    {
        $this->guard->login($user, $shouldRemember);
        $this->userRepository->updateLastLogin($user);
    }
}
