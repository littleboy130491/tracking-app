<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $otp,
        public readonly int $expiresMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode verifikasi BL Tracking Anda',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.customer.otp',
            with: [
                'otp' => $this->otp,
                'expiresMinutes' => $this->expiresMinutes,
                'appName' => config('app.name', 'BL Tracking'),
            ],
        );
    }
}
