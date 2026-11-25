<?php

namespace App\Modules\Account\Http\Requests;

use App\Modules\Account\Data\CustomerLoginData;
use App\Modules\Account\Http\Requests\Concerns\ThrottlesLogins;
use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    use ThrottlesLogins;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): CustomerLoginData
    {
        return new CustomerLoginData(
            login: $this->string('login')->trim()->toString(),
            password: $this->string('password')->toString(),
            shouldRemember: $this->boolean('remember'),
            ipAddress: $this->ip(),
        );
    }

    protected function throttlePrefix(): string
    {
        return 'customer-login';
    }
}
