<?php

namespace App\Mail;

use App\Firm;
use App\FirmUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class FirmDisconnectNotification extends Mailable
{
    use Queueable, SerializesModels;
    public $firm;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Firm $firm)
    {
        $this->firm = $firm;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.firm.firm_disconnect')
            ->from("no-reply@firmtrak.com")
            ->subject("Firm Disconnection from firmTRAK")
            ->bcc("info@firmtrak.com");
    }
}
