<?php

namespace App\Modules\Account\Mail;

use App\Modules\Account\Enums\OtpPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class OtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly OtpPurpose $purpose,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Your Nischoyota code: :code', ['code' => $this->code]));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.otp-code',
            with: ['messageText' => str_replace(':code', $this->code, $this->purpose->message())],
        );
    }
}
