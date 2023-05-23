<?php

namespace App\Http\Controllers;

use App\Firm;
use App\FirmIntegration;
use App\Http\Libraries\HelperLibrary;
use App\Http\Resources\FirmPlanResource;
use App\Mail\FirmDisconnectNotification;
use App\Mail\FirmSubscriptionCancellation;
use App\Promotion;
use App\PromotionShiftRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class FirmPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return FirmPlanResource
     */
    public function index()
    {
        $firm = Firm::find(HelperLibrary::getFirmID());
        return new FirmPlanResource($firm);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $firm = Firm::find(HelperLibrary::getFirmID());
        try {
            if ($request->filled("plan")) {
                $firm->subscription('main')->skipTrial()->swap($request->plan);
                $firm->package = $request->plan;
                $firm->save();
                $success = true;
            } else {
                $success = false;
            }
        } catch (\Exception $e) {
            $success = false;
        }
        return response() ->json([
            "success" => $success
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function cancel(Request $request)
    {
        $firm = Firm::find(HelperLibrary::getFirmID());
        $checkPromotion = Promotion::get();
        try {
            if($firm->subscribedToPlan('ftt_monthly_all', 'main'))
            {
                $firm->subscription('main','ftt_monthly_all')->cancelNow();
            } else if ($firm->subscribedToPlan('ftt_monthly', 'main')) {
                $firm->subscription('main','ftt_monthly')->cancelNow();
            } elseif($checkPromotion != null) {
                foreach ($checkPromotion as $c) {
                    if ($firm->subscribedToPlan($c->stripe_plan_id, 'main')) {
                        $firm->subscription('main', $c->stripe_plan_id)->cancelNow();
                        PromotionShiftRequest::where("firm_id", HelperLibrary::getFirmID())->delete();
                    }
                }
            }
            sleep(0.5);
            if($firm->subscribedToPlan('ftt_user', 'secondary'))
            {
                $firm->subscription('secondary','ftt_user')->cancelNow();
            }
            //if condition(subscribe_email) should be removed when test cases completes for all firms
            if($firm->subscribe_email != null and $firm->subscribe_user_name != null) {
                Mail::to($firm->subscribe_email)->queue(new FirmDisconnectNotification($firm));
            }
            Firm::doDeleteSummary($firm->id);
            $row = FirmIntegration::where("firm_id", HelperLibrary::getFirmID())->first();
            $row->code = "";
            $row->access_token = "";
            $row->refresh_token = "";
            $row->status = "Disconnected";
            $row->save();

            $success = true;
        } catch (\Exception $e) {
            $success = false;
        }
        return response() ->json([
            "success" => $success
        ]);
    }
}
