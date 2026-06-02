<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetSuccessMail extends Mailable
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
        $subject = __('passwords.subject_success', [], $this->locale);
        $view = 'emails.password-reset-success';

        return $this->subject($subject)
            ->view($view)
            ->with([
                'name' => $this->data['name'],
                'email' => $this->data['email'],
                'locale' => $this->locale,
            ]);
    }
}
