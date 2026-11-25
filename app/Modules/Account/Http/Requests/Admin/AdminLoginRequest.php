<?php

namespace App\Modules\Account\Http\Requests\Admin;

use App\Modules\Account\Data\AdminLoginData;
use App\Modules\Account\Http\Requests\Concerns\ThrottlesLogins;
use Illuminate\Foundation\Http\FormRequest;

final class AdminLoginRequest extends FormRequest
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

    public function toData(): AdminLoginData
    {
        return new AdminLoginData(
            login: $this->string('login')->trim()->toString(),
            password: $this->string('password')->toString(),
            shouldRemember: $this->boolean('remember'),
        );
    }

    protected function throttlePrefix(): string
    {
        return 'admin-login';
    }
}
