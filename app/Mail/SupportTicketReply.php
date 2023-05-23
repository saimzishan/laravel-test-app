<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use ReflectionProperty;
use App\SupportTicketReply as STR;

class SupportTicketReply extends Mailable
{
    use Queueable, SerializesModels;

    public $reply;

    /**
     * Create a new message instance.
     *
     * @param \App\SupportTicketReply $reply
     */
    public function __construct(STR $reply)
    {
        $this->reply = $reply;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->markdown('emails.support.ticket_reply')
            ->from("no-reply@firmtrak.com")
            ->subject("Ticket Reply")
            ->bcc("info@firmtrak.com");
    }
}
