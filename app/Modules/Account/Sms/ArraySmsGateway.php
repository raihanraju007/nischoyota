<?php

namespace App\Modules\Account\Sms;

/**
 * Test driver: keeps sent messages in memory.
 */
final class ArraySmsGateway implements SmsGateway
{
    /** @var list<array{phone: string, message: string}> */
    private array $sentMessages = [];

    public function send(string $phone, string $message): void
    {
        $this->sentMessages[] = ['phone' => $phone, 'message' => $message];
    }

    /**
     * @return list<array{phone: string, message: string}>
     */
    public function sentTo(string $phone): array
    {
        return array_values(array_filter($this->sentMessages, fn (array $sms): bool => $sms['phone'] === $phone));
    }

    /**
     * The 6-digit code in the last message to this phone.
     */
    public function lastCodeFor(string $phone): ?string
    {
        $messages = $this->sentTo($phone);

        if ($messages === [] || preg_match('/\b(\d{6})\b/', end($messages)['message'], $match) !== 1) {
            return null;
        }

        return $match[1];
    }
}
