<?php

namespace App\Modules\Account\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ForgotPasswordRequest extends FormRequest
{
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
        ];
    }

    public function login(): string
    {
        return $this->string('login')->trim()->toString();
    }
}
