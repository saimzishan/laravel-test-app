<?php

namespace App\Mail;

use App\Firm;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class FirmStripeInvoicePaymentSucceeded extends Mailable
{
    use Queueable, SerializesModels;

    public $firm;
    public $invoice;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Firm $firm)
    {
        $this->firm = $firm;
        $this->invoice = $firm->invoices()->first();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->markdown('emails.subscription.invoice_payment_succeeded')
            ->from("no-reply@firmtrak.com")
            ->subject("Payment Receipt For firmTRAK Visualize")
            ->cc("payments@firmtrak.com");
    }
}
