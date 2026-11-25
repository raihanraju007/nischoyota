<?php

use App\Support\PhoneNumber;

it('normalizes Bangladeshi mobile numbers', function (string $input) {
    expect(PhoneNumber::normalize($input))->toBe('01712345678');
})->with([
    'local' => '01712345678',
    'with country code' => '+8801712345678',
    'country code without plus' => '8801712345678',
    'spaces and dashes' => '+880 1712-345678',
    'brackets' => '(+880) 17123 45678',
]);

it('rejects invalid mobile numbers', function (string $input) {
    expect(PhoneNumber::normalize($input))->toBeNull();
})->with([
    'too short' => '0171234567',
    'too long' => '017123456789',
    'invalid operator digit' => '01212345678',
    'landline' => '029876543',
    'empty' => '',
    'email' => 'admin@nischoyota.com',
]);
