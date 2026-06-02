<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $data;

    public $locale;

    /**
     * Create a new message instance.
     */
    public function __construct(array $data, string $locale)
    {
        $this->data = $data;
        $this->locale = $locale;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = __('passwords.subject', [], $this->locale);
        $view = 'emails.forgot-password';

        return $this->subject($subject)
            ->view($view)
            ->with([
                'otp' => $this->data['token'],
                'email' => $this->data['email'],
                'name' => $this->data['name'],
                'locale' => $this->locale,
            ]);
    }
}
