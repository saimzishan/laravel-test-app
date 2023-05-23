<?php

namespace App\Mail;

use App\FirmUserOther;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserRegisterAdminOtherIntegration extends Mailable
{
    use Queueable, SerializesModels;

    public $user = null;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(FirmUserOther $user)
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
        return $this->markdown('emails.auth.other_integration_register_admin')
            ->from("no-reply@firmtrak.com")
            ->subject("Notification of New User Registration");
    }
}
