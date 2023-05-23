<?php

namespace App\Http\Controllers;

use App\CLUser;
use App\FirmIntegration;
use App\Http\Libraries\HelperLibrary;
use App\Http\Resources\FTESetupResource;
use App\Http\Resources\IntegrationUserListResource;
use App\Jobs\FirmSummaryGenerateJob;
use App\PPUser;
use DB;
use Illuminate\Http\Request;

class FTESetupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
            $data = PPUser::where("firm_id", HelperLibrary::getFirmID())->paginate(HelperLibrary::perPage());
        } else {
            $data = CLUser::where("firm_id", HelperLibrary::getFirmID())->paginate(HelperLibrary::perPage());
        }
        return FTESetupResource::collection($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function create()
    {
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
            $rows = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("can_be_calculated", true)->get();
        } else {
            $rows = CLUser::where("firm_id", HelperLibrary::getFirmID())->where("can_be_calculated", true)->get();
        }
        return IntegrationUserListResource::collection($rows);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
     * @return FTESetupResource
     */
    public function edit($id)
    {
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
            $data = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("id", $id)->first();
        } else {
            $data = CLUser::where("firm_id", HelperLibrary::getFirmID())->where("id", $id)->first();
        }
        return new FTESetupResource($data);
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
        FirmIntegration::where('firm_id', HelperLibrary::getFirmID())
            ->update(['status_message' => "Refresh is in progress. Refresh times vary depending on the amount of data being imported.",'percentage'=>"95"]);
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
            $row = PPUser::find($id);
        } else {
            $row = CLUser::find($id);
        }
        $row->firm_id = HelperLibrary::getFirmID();
        $row->type = $request->type;
        $row->date_of_joining = $request->date_of_joining;
        $row->hours_per_week = $request->hours_per_week;
        $row->rate_per_hour = $request->rate_per_hour;
        $row->cost_per_hour = $request->cost_per_hour;
        $row->fte_hours_per_month = $request->fte_hours_per_month;
        $row->fte_equivalence = $row->calculateFTEEquivilance();
        $row->monthly_billable_target = $request->monthly_billable_target;
        $row->can_be_calculated = $request->can_be_calculated ? true : false;
        if ($row->save()) {
            FirmSummaryGenerateJob::dispatch($row->firm_id, "expense","",null)->onQueue("summary");
            FirmSummaryGenerateJob::dispatch($row->firm_id, "aopGPM","",null)->onQueue("summary");
            FirmSummaryGenerateJob::dispatch($row->firm_id, "productivity","",null)->onQueue("summary");
            FirmSummaryGenerateJob::dispatch($row->firm_id, "","",$row->id)->onQueue("summary");
            FirmIntegration::where('firm_id', HelperLibrary::getFirmID())
                ->update(['status_message' => null,'percentage'=>"100","status"=>"Synced"]);
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
        //
    }
}
