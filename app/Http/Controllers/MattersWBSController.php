<?php

namespace App\Http\Controllers;

use App\CLMatter;
use App\CLUser;
use App\Http\Libraries\HelperLibrary;
use App\Http\Resources\IntegrationUserListResource;
use App\Http\Resources\MattersWBSResource;
use App\Http\Resources\MatterTypeResource;
use App\MatterWBS;
use App\PPMatter;
use App\PPUser;
use Illuminate\Http\Request;

class MattersWBSController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = MatterWBS::where("is_delete", 0)->paginate(HelperLibrary::perPage());
        return MattersWBSResource::collection($data);
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $row = new MatterWBS;
        $row->firm_id = \App\Http\Libraries\HelperLibrary::getFirmID();
        $row->matter_type = $request->matter_type;
        $row->lead_days = $request->lead_days;
        $row->time_entries = $request->time_entries;
        $row->total_cost = $request->total_cost;
        $row->created_by = \App\Http\Libraries\HelperLibrary::getLoggedInUser()->id;
        $row->updated_by = \App\Http\Libraries\HelperLibrary::getLoggedInUser()->id;
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
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data = MatterWBS::where("is_delete", 0)->where("id", $id)->first();
        return new MattersWBSResource($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $row = MatterWBS::find($id);
        $row->firm_id = \App\Http\Libraries\HelperLibrary::getFirmID();
        $row->matter_type = $request->matter_type;
        $row->lead_days = $request->lead_days;
        $row->time_entries = $request->time_entries;
        $row->total_cost = $request->total_cost;
        $row->updated_by = \App\Http\Libraries\HelperLibrary::getLoggedInUser()->id;
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $row = MatterWBS::find($id);
        $row->is_delete = 1;
        $row->updated_by = \App\Http\Libraries\HelperLibrary::getLoggedInUser()->id;
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
     * return All Matter types in json format
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getMatterTypes()
    {
        $rows = PPMatter::where("matter_type", "<>", null)->select('matter_type')->distinct()->get();
        return MatterTypeResource::collection($rows);
    }

    public function getUserTypes()
    {
        $rows = PPMatter::where("matter_type", "<>", null)->select('matter_type')->distinct()->get();
        $attorney = array("id" => "Attorney", "text" => "Attorney");
        $paralegal = array("id" => "Paralegal-Staff", "text" => "Paralegal Staff");
        $admin = array("id" => "Admin-Staff", "text" => "Admin Staff");
        $other = array("id" => "Others", "text" => "Others");
        $data = array($attorney, $paralegal, $admin, $other);
        $return = array("data" => $data);
        $return = (object)$return;
        return json_encode($return);
    }

    public function getOriginatingAttorney()
    {
//        $data_raw =[];
        $data=[];
        $temp = [];
//        $rows="";
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
//            $rows = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("can_be_calculated", true)->get();
        } else {
//            $data_raw = CLMatter::select('clio_originating_attorney_id')->where('firm_id',HelperLibrary::getFirmID())->distinct()->get();
//            return $data_raw;
//            for($i = 0;$i<sizeof($data_raw);$i++)
//            {
//                $data[$i] = CLUser::select('name','id')->where("firm_id",HelperLibrary::getFirmID())->where('id',$data_raw[$i]->clio_originating_attorney_id)->get();
                $data = CLUser::select('name','id')->where("firm_id",HelperLibrary::getFirmID())->where('type','Attorney')->where("can_be_calculated", true)->get();
//            }
//            return $data[0];
        }
            for($i =0;$i<sizeof($data);$i++)
            {
                if($data[$i]!="[]") {
                    $temp[$i] = array("id" => $data[$i]->id, "text" => $data[$i]->name);
                }
            }
        return  array("data"=>$temp);
    }


}
