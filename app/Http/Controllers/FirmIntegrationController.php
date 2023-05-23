<?php

namespace App\Http\Controllers;

use App\Firm;
use App\FirmIntegration;
use App\Mail\FirmDisconnectNotification;
use App\Mail\FirmSubscriptionCancellation;
use App\Promotion;
use App\PromotionShiftRequest;
use Illuminate\Support\Facades\Artisan;
use App\Http\Libraries\HelperLibrary;
use App\Jobs\FirmSyncJob;
use App\Jobs\FirmSyncUpdateJob;
use Illuminate\Http\Request;
use App\Http\Libraries\ClioAPILibrary;
use App\Http\Resources\FirmIntegrationResource;
use App\Http\Libraries\PracticePantherAPILibrary;
use Illuminate\Support\Facades\Mail;

class FirmIntegrationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return FirmIntegrationResource
     */
    public function index()
    {
        $data = FirmIntegration::where("firm_id", HelperLibrary::getFirmID())->first();
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
            $tmp = HelperLibrary::getSettings(["pp_client_id"]);
            $app = ["client_id" => $tmp->pp_client_id];
        } elseif (HelperLibrary::getFirmIntegration() == "clio") {
            $tmp = HelperLibrary::getSettings(["cl_app_id"]);
            $app = ["client_id" => $tmp->cl_app_id];
        } else {
            $app = ["client_id" => ""];
        }
        return new FirmIntegrationResource($data, $app);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $success = false;
//        $success = true;
//        $step = $request->filled("step") ? $request->step : 1;
//        $firm_id = HelperLibrary::getFirmID();
//        if (HelperLibrary::getFirmIntegration()=='practice_panther') {
//            $lib = new PracticePantherAPILibrary($firm_id);
//            if ($step == 1) {
//                $success = $lib->refreshToken();
//            } elseif ($step == 2) {
//                $success = $lib->syncUsers();
//            } elseif ($step == 3) {
//                $success = $lib->syncAccounts();
//            } elseif ($step == 4) {
//                $success = $lib->syncContacts();
//            } elseif ($step == 5) {
//                $success = $lib->syncMatters();
//            } elseif ($step == 6) {
//                $success = $lib->syncTasks();
//            } elseif ($step == 7) {
//                $success = $lib->syncTimeEntries();
//            } elseif ($step == 8) {
//                $success = $lib->syncInvoices();
//            } elseif ($step == 9) {
//                $success = $lib->syncExpenses();
//            }
//        } else {
//            $lib = new ClioAPILibrary($firm_id);
//            if ($step == 1) {
//                $success = $lib->refreshToken();
//            } elseif ($step == 2) {
//                $success = $lib->syncUsers();
//            } elseif ($step == 3) {
//                $success = $lib->syncContacts();
//            } elseif ($step == 4) {
//                $success = $lib->syncMatters();
//            } elseif ($step == 5) {
//                $success = $lib->syncTasks();
//            } elseif ($step == 6) {
//                $success = $lib->syncTimeEntries();
//            } elseif ($step == 7) {
//                $success = $lib->syncInvoices();
//            }
//        }
        return response()->json([
            "success" => $success
        ]);
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
        $checkPromotion = Promotion::get();
        $success = false;
        try {
            if($firm->subscribedToPlan('ftt_monthly_all', 'main'))
            {
                $firm->subscription('main','ftt_monthly_all')->cancelNow();
            } elseif ($checkPromotion != null) {
                foreach($checkPromotion as $c) {
                    if ($firm->subscribedToPlan($c->stripe_plan_id, 'main')) {
                        $firm->subscription('main',$c->stripe_plan_id)->cancelNow();
                        PromotionShiftRequest::where("firm_id",HelperLibrary::getFirmID())->delete();
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
            $row->updated_by = HelperLibrary::getLoggedInUser()->id;
            if ($row->save()) {
                $success = true;
            } else {
                $success = false;
            }

        } catch (\Exception $e) {
            $success = false;
        }


        return response()->json([
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
        $firm_id = HelperLibrary::getFirmID();
        $row = FirmIntegration::where("firm_id", $firm_id)->first();
        $row->status = "In-Queue";
        $row->save();
        FirmSyncUpdateJob::dispatch($firm_id)->onQueue("syncupdate");
        return response()->json([
            "success" => true
        ]);
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
     * Setup The PP Access granded action
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function auth(Request $request)
    {
        $firm_id = HelperLibrary::getFirmID();
        Artisan::call("firm:deleteSummary", ["firm_id"=> $firm_id]);
        $row = FirmIntegration::where("firm_id", $firm_id)->first();
        $row->code = $request->code;
        $row->status = "In-Queue";
        $row->last_sync = null;
        $row->updated_by = HelperLibrary::getLoggedInUser()->id;
        $row->save();
        $ppLib = new PracticePantherAPILibrary($firm_id);
        $ppLib->generateToken();
        $ppLib->refreshToken();
        FirmSyncJob::dispatch($firm_id)->onQueue("sync");
        return redirect($request->state);
    }

    /**
     * Setup The Clio Access granded action
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function authClio(Request $request)
    {
        $firm_id = HelperLibrary::getFirmID();
        Artisan::call("firm:deleteSummary", ["firm_id"=> $firm_id]);
        $row = FirmIntegration::where("firm_id", $firm_id)->first();
        $row->code = $request->code;
        $row->status = "In-Queue";
        $row->last_sync = null;
        $row->updated_by = HelperLibrary::getLoggedInUser()->id;
        $row->save();
        $ppLib = new ClioAPILibrary($firm_id);
        $ppLib->generateToken();
        FirmSyncJob::dispatch($firm_id)->onQueue("sync");
        return redirect($request->state);
    }
    public function authQuickbooks(Request $request)
    {
       dd($request);
    }
    public function cancelSubscription()
    {
        $success= false;
        $firm = Firm::find(HelperLibrary::getFirmID());
        $checkPromotion = Promotion::notDeleted()->first();
        try {
            if($firm->subscribedToPlan('ftt_monthly_all', 'main'))
            {
                $firm->subscription('main','ftt_monthly_all')->cancelNow();
            } elseif ($firm->subscribedToPlan($checkPromotion->stripe_plan_id, 'main')) {
                $firm->subscription('main',$checkPromotion->stripe_plan_id)->cancelNow();
                PromotionShiftRequest::where("firm_id",HelperLibrary::getFirmID())->delete();
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
        return $success;
    }
}
