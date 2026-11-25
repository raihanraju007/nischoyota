<?php

namespace App\Modules\Account\Http\Requests;

use App\Support\NormalizesStringInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProfileRequest extends FormRequest
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
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user())],
        ];
    }

    public function name(): string
    {
        return $this->string('name')->trim()->toString();
    }

    public function email(): ?string
    {
        return $this->filled('email') ? $this->string('email')->trim()->lower()->toString() : null;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings(['email' => fn (string $email): string => mb_strtolower(trim($email))]);
    }
}
