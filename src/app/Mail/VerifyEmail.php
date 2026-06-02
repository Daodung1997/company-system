<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $otp;

    public $name;

    /**
     * Create a new message instance.
     */
    public function __construct(string $otp, string $name)
    {
        $this->otp = $otp;
        $this->name = $name;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your Verification Code')
            ->view('emails.verify_email');
    }
}
