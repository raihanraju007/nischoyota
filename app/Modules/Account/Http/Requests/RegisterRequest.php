<?php

namespace App\Modules\Account\Http\Requests;

use App\Modules\Account\Data\RegistrationData;
use App\Support\NormalizesStringInput;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * A phone of an unverified account may register again; a verified phone may not.
 */
final class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:'.PhoneNumber::PATTERN, Rule::unique('users', 'phone')->whereNotNull('phone_verified_at')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->where(fn ($query) => $query->where('phone', '!=', $this->input('phone')))],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => __('Enter a valid Bangladeshi mobile number, e.g. 01712-345678.'),
            'phone.unique' => __('This phone number is already registered. Please log in.'),
            'email.unique' => __('This email is already used by another account.'),
            'terms.accepted' => __('Please accept the Terms & Conditions and Privacy Policy.'),
        ];
    }

    public function toData(): RegistrationData
    {
        return new RegistrationData(
            name: $this->string('name')->trim()->toString(),
            phone: $this->string('phone')->toString(),
            email: $this->filled('email') ? $this->string('email')->trim()->lower()->toString() : null,
            password: $this->string('password')->toString(),
            ipAddress: $this->ip(),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings(['phone' => fn (string $phone): string => PhoneNumber::normalize($phone) ?? $phone]);
    }
}
