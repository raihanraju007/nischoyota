<?php

namespace App\Modules\Content\Http\Requests;

use App\Modules\Content\Data\ContactMessageData;
use App\Modules\Content\Enums\ContactSubject;
use App\Support\NormalizesStringInput;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Storefront contact form (F-020). "website" is a honeypot field people never see.
 */
final class SendContactMessageRequest extends FormRequest
{
    use NormalizesStringInput;

    public const HONEYPOT_FIELD = 'website';

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
            'phone' => ['required', 'string', 'regex:'.PhoneNumber::PATTERN],
            'email' => ['nullable', 'email', 'max:255'],
            'subject' => ['required', Rule::enum(ContactSubject::class)],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => __('Enter a valid Bangladeshi mobile number, e.g. 01712-345678.'),
        ];
    }

    /**
     * Only bots fill the hidden honeypot field.
     */
    public function isFromBot(): bool
    {
        return $this->filled(self::HONEYPOT_FIELD);
    }

    public function toData(): ContactMessageData
    {
        return new ContactMessageData(
            userId: $this->user()?->id,
            name: $this->string('name')->toString(),
            phone: $this->string('phone')->toString(),
            email: $this->filled('email') ? $this->string('email')->lower()->toString() : null,
            subject: $this->enum('subject', ContactSubject::class),
            message: $this->string('message')->toString(),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings(['phone' => fn (string $phone): string => PhoneNumber::normalize(trim($phone)) ?? trim($phone)]);
    }
}
