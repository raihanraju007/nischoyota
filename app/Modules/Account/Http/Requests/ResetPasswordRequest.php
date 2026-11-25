<?php

namespace App\Modules\Account\Http\Requests;

use App\Support\NormalizesStringInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends FormRequest
{
    use NormalizesStringInput;

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
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ];
    }

    public function code(): string
    {
        return $this->string('code')->toString();
    }

    public function newPassword(): string
    {
        return $this->string('password')->toString();
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings(['code' => fn (string $code): ?string => preg_replace('/\s+/', '', $code)]);
    }
}
