<?php

namespace App\Http\Libraries;

use App\Firm;
use App\Http\Libraries\HelperLibrary;
use Illuminate\Http\Request;

class StripeLibrary
{
private $firm_id = null;

    /**
     * @return null
     */
    public function __construct($firm_id)
    {
        $this->firm_id = $firm_id;
    }

    public function updateUserQuantity()
    {
        try {
            $success = true;
            $firm = Firm::find($this->firm_id);
            $qty = $firm->getPaymentUsers();
            $prev = $firm->no_of_users;
            if ($firm->subscribedToPlan('ftt_monthly', 'main') ) {
                if($qty > $prev)
                {
                    $q = $qty - $prev;
                    $firm->Subscription('main', 'ftt_monthly')->incrementQuantity($q);

                } else if ($qty < $prev)
                {
                    $q = $prev - $qty;
                    $firm->Subscription('main', 'ftt_monthly')->decrementQuantity($q);
                }
                $firm->no_of_users = $qty;
                $firm->save();
            } else if ($firm->subscribedToPlan('ftt_monthly_all', 'main')) {
                if($qty > $prev)
                {
                    $q = $qty - $prev;
                    $firm->Subscription('main', 'ftt_monthly_all')->incrementQuantity($q);

                } else if ($qty < $prev)
                {
                    $q = $prev - $qty;
                    $firm->Subscription('main', 'ftt_monthly_all')->decrementQuantity($q);
                }
                $firm->no_of_users = $qty;
                $firm->save();
            } 
            else {
                $success = false;
            }

        } catch (\Exception $e) {
            $success = false;
        }
        $this->subscribeUser();
        return response()->json([
            "success" => $success
        ]);
    }
    public function subscribeUser()
    {
        try {
            $success = true;
            $firm = Firm::find($this->firm_id);
            $qty = $firm->getPaymentUsers();
            $user = $firm->users->count();
            if($qty < 3 )
            {
                if ($firm->subscribedToPlan('ftt_user', 'secondary')) {
                    if ($user < 2) {
                        $firm->subscription('secondary','ftt_user')->cancel();
                    } else {
                        $firm->Subscription('secondary', 'ftt_user')->updateQuantity($user);
                    }

                } else {
                    if ($user > 1) {
                        $t = $user - 1;
                        $firm->newSubscription('secondary', 'ftt_user')->quantity($t)->create();
                    }

                }
            } else if ($qty > 3 and $qty < 10) {
                if ($firm->subscribedToPlan('ftt_user', 'secondary')) {
                    if ($user < 4) {
                        $firm->Subscription('secondary', 'ftt_user')->cancel();
                    } else {
                        $t = $user - 3;
                        $firm->Subscription('secondary', 'ftt_user')->updateQuantity($t);
                    }
                } else {
                    if ($user > 3) {
                        $t = $user - 3;
                        $firm->newSubscription('secondary', 'ftt_user')->quantity($t)->create();
                    }
                }
            }  else if ($qty > 9 and $qty < 20) {
                if ($firm->subscribedToPlan('ftt_user', 'secondary')) {
                    if ($user < 7) {
                        $firm->Subscription('secondary', 'ftt_user')->cancel();
                    } else {
                        $t = $user - 6;
                        $firm->Subscription('secondary', 'ftt_user')->updateQuantity($t);
                    }
                } else {
                    if ($user > 6) {
                        $t = $user - 6;
                        $firm->newSubscription('secondary', 'ftt_user')->quantity($t)->create();
                    }
                }
            }
            $firm->save();
            $success = true;
        } catch (\Exception $e) {
            $success = false;
        }
        return response()->json([
            "success" => $success
        ]);
    }
}

