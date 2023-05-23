<?php

namespace App\Http\Controllers\Stripe;

use App\Firm;
use App\Mail\FirmStripeInvoicePaymentSucceeded;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

class WebHookController extends CashierController
{

    /**
     * Handle a Stripe webhook.
     *
     * @param  array  $payload
     * @return Response
     */
    public function handleInvoicePaymentSucceeded($payload)
    {
        //Storage::disk("local")->put("abc.txt", json_encode($payload));
        $customer_id = $payload['data']['object']['customer'];
        $firm = Firm::where("is_delete", 0)->where("is_active", 1)->where("stripe_id", $customer_id);
        if ($firm->count() == 1) {
            $firm = $firm->first();
            Mail::to("payments@firmtrak.com")->queue(new FirmStripeInvoicePaymentSucceeded($firm));

            //old
            //foreach ($firm->getFirmAdmins() as $admin)
                //Mail::to($admin->email)->queue(new FirmStripeInvoicePaymentSucceeded($firm));
            //}
        }
    }

}
