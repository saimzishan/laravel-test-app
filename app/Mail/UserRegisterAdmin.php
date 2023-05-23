<?php

namespace App\Mail;

use App\FirmUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserRegisterAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public $user = null;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(FirmUser $user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->markdown('emails.auth.register_admin')
            ->from("no-reply@firmtrak.com")
            ->subject("Notification of New User Registration");
    }
}
