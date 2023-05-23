<?php

namespace App\Mail;

use App\FirmUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class FirmFirstTimeSync extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    /**
     * Create a new message instance.
     *
     * @param FirmUser $user
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

        return $this->markdown('emails.firm.first_time_sync')
            ->from("no-reply@firmtrak.com")
            ->subject("First Time Integration Sync")
            ->bcc("info@firmtrak.com");
    }
}
