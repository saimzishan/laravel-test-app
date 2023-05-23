<?php

namespace App\Http\Controllers;

use App\CLUser;
use App\Definition;
use App\Exports\ContactSaleReportExport;
use App\Exports\FirmSaleReportExport;
use App\Firm;
use App\FirmIntegration;
use App\FirmUser;
use App\Http\Resources\FirmListResource;
use App\Http\Resources\FirmResource;
use App\Http\Resources\FirmsResource;
use App\PPUser;
use Illuminate\Http\Request;
use App\Http\Libraries\HelperLibrary;
use Excel;

class FirmController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        if (HelperLibrary::hasFirm()) {
            $data = Firm::where("is_delete", 0)
                ->where("id", HelperLibrary::getFirmID())
                ->paginate(HelperLibrary::perPage());
        } else {
            $data = Firm::where("is_delete", 0)
                ->paginate(HelperLibrary::perPage());
        }
        return FirmsResource::collection($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function create()
    {
        if (HelperLibrary::hasFirm()) {
            $rows = Firm::where("is_delete", 0)->where("is_active", 1)->where("id", HelperLibrary::getFirmID())->get();
        } else {
            $rows = Firm::where("is_delete", 0)->where("is_active", 1)->get();
        }
        return FirmListResource::collection($rows);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $row = new Firm;
        $row->name = $request->name;
        $row->address = $request->address;
        $row->contact = $request->contact;
        $row->desc = $request->desc;
        $row->integration = $request->integration;
        if ($request->hasFile("logo")) {
            $row->logo = $request->logo->storeAs('firm', time() . "_logo." . $request->logo->extension(), "uploads");
        }
        $row->created_by = HelperLibrary::getLoggedInUser()->id;
        $row->updated_by = HelperLibrary::getLoggedInUser()->id;
        if ($row->save()) {
            $row->createIntegration();
            $row->makeDefaultDefinitions();
            $success = true;
        } else {
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
    public function show(Request $request, $id)
    {
        $row = Firm::find($id);
        $row->is_active = $request->action == "active" ? 1 : 0;
        $row->updated_by = HelperLibrary::getLoggedInUser()->id;
        if ($row->save()) {
            $success = true;
        } else {
            $success = false;
        }
        return response()->json([
            "success" => $success
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return FirmResource
     */
    public function edit($id)
    {
        $data = Firm::where("is_delete", 0)->where("id", $id)->first();
        return new FirmResource($data);
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
        $row = Firm::find($id);
        $row->name = $request->name;
        $row->address = $request->address;
        $row->contact = $request->contact;
        $row->desc = $request->desc;
        $row->integration = $request->integration;
        if ($request->hasFile("logo")) {
            $row->logo = $request->logo->storeAs('firm', time() . "_logo." . $request->logo->extension(), "uploads");
        }
        $row->updated_by = HelperLibrary::getLoggedInUser()->id;
        if ($row->save()) {
            $success = true;
        } else {
            $success = false;
        }
        return response()->json([
            "success" => $success
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
        $row = Firm::find($id);
        $row->is_delete = 1;
        $row->updated_by = HelperLibrary::getLoggedInUser()->id;
        if ($row->save()) {
            $success = true;
        } else {
            $success = false;
        }
        return response()->json([
            "success" => $success
        ]);
    }

    /**
     * Change Firm Plan
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function changePlan(Request $request)
    {
        $row = Firm::find($request->id);
        $row->is_free = $request->is_free;
        if ($row->is_free) {
            $row->stripe_id = null;
            $row->card_brand = null;
            $row->card_last_four = null;
            $row->package = $request->package;
            $row->trial_ends_at = null;
            if ($row->subscriptions()->where("ends_at", "")->count() == 1) {
                if ($row->subscribed("main")) {
                    $row->subscription("main")->cancel();
                }
            }
        } else {
            $row->trial_ends_at = now()->addDays(HelperLibrary::getSettings(["trial_period"])->trial_period + 1);
        }
        $row->updated_by = HelperLibrary::getLoggedInUser()->id;
        if ($row->save()) {
            $success = true;
        } else {
            $success = false;
        }
        return response()->json([
            "success" => $success
        ]);
    }

    public function contactSalesReport(Request $request)
    {

        $users = null ;
        if($request->users=="" or $request->users==null)
        {
            $users = 0;
        }
        $tier = Definition::checkTiers($request->users);
        $rawFirm = Firm::select("id","name","address","contact","integration","created_at as Registered")->where("is_delete",0)->get();
        $count = 0;
        $data = [];
        foreach($rawFirm as $firm)
        {
                $rawUser = $firm->users->first();
                $data[] = array("id"=>$firm->id,"Firm_Name"=>$firm->name,"name"=>$rawUser->last_name.",".$rawUser->first_name ,"Email"=>$rawUser->email,"Address"=>$firm->address,"Contact"=>$firm->contact,"Integration"=>Definition::getIntegrationCode($firm->integration),"Registration"=>substr($firm->created_at,0,10));
        }
        if($request->type=="excel")
        {
            $mydata = collect($data);
            ob_end_clean();
            ob_start();
            return Excel::download(new ContactSaleReportExport($mydata), time()."-contact-sales-report.xlsx");
        }
        if($request->type=="pdf")
        {
            $mydata = collect($data);
            ob_end_clean();
            ob_start();
            return Excel::download(new ContactSaleReportExport($mydata), time()."-contact-sales-report.pdf");
        }else
        {
            return response()->json([
                "data"=>$data
            ]);
        }

    }
    public function firmSalesReport(Request $request)
    {
        $data=[];
        $rawFirm = Firm::select("id","name","integration","created_at as Registration")->where("is_delete",0)->get();
        for($i=0;$i<sizeof($rawFirm);$i++)
        {
            $status = FirmIntegration::select("status")->where("firm_id",$rawFirm[$i]->id)->get();
            $paid=Firm::select("id")->where("stripe_id","!=",null)->where("card_brand","<>",null)->where("card_last_four","<>",null)->where("id",$rawFirm[$i]->id)->get();
            $data[$i]["id"] = $rawFirm[$i]->id;
            $data[$i]["name"] = $rawFirm[$i]->name;
            $data[$i]["integration"] = Definition::getIntegrationCode($rawFirm[$i]->integration);
            $data[$i]["Registration"] = $rawFirm[$i]->Registration;
            $data[$i]["Status"] = $status[0]->status;
            if(isset($paid[0]->id))
            {
                $data[$i]["Paid"] = "Paid";
                if($data[$i]["integration"]=="PP")
                {
                    $data[$i]["Users"] = PPUser::where("firm_id",$data[$i]["id"])->count("id");
                    $tier = Definition::checkTiers($data[$i]["Users"]);
                    $data[$i]["Tier"] = $tier["Tier"];
                    $data[$i]["Price"] = $tier["Price"];
                }
                else
                {
                    $data[$i]["Users"] = CLUser::where("firm_id",$data[$i]["id"])->where("enabled",1)->count("id");
                    $tier = Definition::checkTiers($data[$i]["Users"]);
                    $data[$i]["Tier"] = $tier["Tier"];
                    $data[$i]["Price"] = $tier["Price"];
                }
            }
            else
            {
                $data[$i]["Paid"] = "";
                if($data[$i]["integration"]=="PP")
                {
                    $data[$i]["Users"] = PPUser::where("firm_id",$data[$i]["id"])->count("id");
                    $tier = Definition::checkTiers($data[$i]["Users"]);
                    $data[$i]["Tier"] = $tier["Tier"];
                    $data[$i]["Price"] = $tier["Price"];

                }
                else
                {
                    $data[$i]["Users"] = CLUser::where("firm_id",$data[$i]["id"])->where("enabled",1)->count("id");
                    $tier = Definition::checkTiers($data[$i]["Users"]);
                    $data[$i]["Tier"] = $tier["Tier"];
                    $data[$i]["Price"] = $tier["Price"];

                }
            }



            $dataa[$i] = array("id" => $data[$i]["id"], "Firm_Name" => $data[$i]["name"], "Integration" => $data[$i]["integration"], "Registration" => substr($data[$i]["Registration"], 0, 10), "Status" => $data[$i]["Status"], "Paid" => $data[$i]["Paid"], "Users" => $data[$i]["Users"], "PricingTier" => $data[$i]["Tier"], "ExpectedRevenue" => "$" . number_format($data[$i]["Price"]));
        }
                if($request->type=="excel")
        {
           $mydata = collect($dataa);
            ob_end_clean();
            ob_start();
            return Excel::download(new FirmSaleReportExport($mydata), time()."-firm-sales-report.xlsx");
        }
        if($request->type=="pdf")
        {
            $mydata = collect($dataa);
            ob_end_clean();
            ob_start();
            return Excel::download(new FirmSaleReportExport($mydata), time()."-firm-sales-report.pdf");
        }else
        {
            return response()->json([
                "data"=>$dataa
            ]);
        }

//        return $dataa;

    }

}
