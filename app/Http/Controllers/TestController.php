<?php

namespace App\Http\Controllers;

use App\CLContact;
use App\CLInvoiceLineItem;
use App\CLUser;
use App\Firm;
use App\Http\Libraries\HelperLibrary;
use App\Http\Libraries\StripeLibrary;
use App\PPInvoice;
use App\Promotion;
use App\PromotionShiftRequest;
use App\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function test() {
//        $firm = Firm::find(HelperLibrary::getFirmID());
//        $stripe = new StripeLibrary(HelperLibrary::getFirmID());
//        $stripe->updateUserQuantity();
  //      $col = PPInvoice::calcTotalCollectedHoursAverage("2019-09", "month",1,89);
//dd($col);
//        if($firm->subscribed('main','monthly')) {
//            $d = $firm->subscribed('main','monthly')->quantity();
//            dd($d);
//        }
//        $firm = Firm::find(HelperLibrary::getFirmID());
//        $checkPromotion = Promotion::notDeleted()
//            ->where("start_date", "<=", (new Carbon($firm->created_at))->format("Y-m-d"))
//            ->where("end_date", ">=", (new Carbon($firm->created_at))->format("Y-m-d"))
//            ->first();
//        //dd($checkPromotion);
//        $subscription = $checkPromotion->stripe_plan_id;
//        PromotionShiftRequest::create([
//            "promotion_id" => $checkPromotion->id,
//            "firm_id" => $firm->id,
//            "subscription_from" => $subscription,
//            "subscription_to" => "ftt_monthly",
//            "end_date" => (new Carbon($firm->created_at))->addDays($checkPromotion->validity)->format("Y-m-d"),
//        ]);




    }

}
