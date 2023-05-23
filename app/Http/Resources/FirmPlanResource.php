<?php

namespace App\Http\Resources;

use App\Http\Libraries\HelperLibrary;
use Illuminate\Http\Resources\Json\JsonResource;

class FirmPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $package = HelperLibrary::getFirmPackage();
        return [
            "subscription_status" => $this->is_free ? true : $this->subscribed("main"),
            "subscription_cancelled" => $this->isSubscriptionCancelled(),
            "subscription_name" => $this->getCurrentPackageName(),
            "subscription_type" => $this->getSubscriptionType(),
            "subscription_expiry" => $this->getStripeSubscriptionExpiry(),
            "connect_status" => $this->isStripeConnected(),
            "plan" => $this->getCurrentPlan(),
            "no_of_users" => $this->getPaymentUsers(),
            "no_of_ft_users" => $this->users->count(),
            "total_payment" => $this->getTotalPaymentFromPackage(),
            "card_digits" => $this->card_last_four,
            "stripe_key" => config("services.stripe.key"),
        ];
    }
}
