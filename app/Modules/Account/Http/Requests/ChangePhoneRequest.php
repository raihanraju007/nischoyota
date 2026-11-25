<?php

namespace App\Modules\Account\Http\Requests;

use App\Support\NormalizesStringInput;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChangePhoneRequest extends FormRequest
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
            'phone' => [
                'required', 'string', 'regex:'.PhoneNumber::PATTERN,
                Rule::notIn([$this->user()->phone]),
                Rule::unique('users', 'phone')->ignore($this->user()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => __('Enter a valid Bangladeshi mobile number, e.g. 01712-345678.'),
            'phone.not_in' => __('This is already your phone number.'),
            'phone.unique' => __('This phone number belongs to another account.'),
        ];
    }

    public function newPhone(): string
    {
        return $this->string('phone')->toString();
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings(['phone' => fn (string $phone): string => PhoneNumber::normalize($phone) ?? $phone]);
    }
}
