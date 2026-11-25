<?php

namespace App\Modules\Account\Http\Requests\Concerns;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * 5 failed logins per login ID + IP → locked for 60 seconds. Used by the admin and customer login requests.
 * Errors are reported on the "login" field.
 */
trait ThrottlesLogins
{
    private int $maxLoginAttempts = 5;

    private int $loginLockoutSeconds = 60;

    abstract protected function throttlePrefix(): string;

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $this->maxLoginAttempts)) {
            return;
        }

        throw ValidationException::withMessages([
            'login' => __('Too many attempts. Try again in :seconds seconds.', [
                'seconds' => RateLimiter::availableIn($this->throttleKey()),
            ]),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function failLogin(string $message): never
    {
        RateLimiter::hit($this->throttleKey(), $this->loginLockoutSeconds);

        throw ValidationException::withMessages(['login' => $message]);
    }

    public function clearRateLimit(): void
    {
        RateLimiter::clear($this->throttleKey());
    }

    private function throttleKey(): string
    {
        return $this->throttlePrefix().':'.Str::transliterate(Str::lower($this->string('login')->trim()->toString())).'|'.$this->ip();
    }
}
