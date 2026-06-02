<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $type,
        public int $expiryMinutes = 5
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            'register' => 'Mã xác thực đăng ký - ViecVat',
            'forgot_password' => 'Mã xác thực đặt lại mật khẩu - ViecVat',
            'verify_email' => 'Xác thực Email - ViecVat',
            default => 'Mã xác thực OTP - ViecVat',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.otp',
            with: [
                'code' => $this->code,
                'type' => $this->type,
                'expiryMinutes' => $this->expiryMinutes,
            ],
        );
    }
}
