<?php

namespace App\Http\Controllers;

use App\ActivityLog;
use App\FirmUser;
use App\Http\Libraries\HelperLibrary;
use App\Http\Libraries\StripeLibrary;
use App\Http\Resources\FirmUserActivityLogs;
use App\Http\Resources\FirmUserResource;
use App\Http\Resources\FirmUsersResource;
use App\Mail\FirmUserCreate;
use App\Mail\UserRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class FirmUserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        if(HelperLibrary::isSuperAdmin())
        {
            $data = FirmUser::where("is_delete", 0)->paginate(HelperLibrary::perPage());
        }else
        {
            $data = FirmUser::where("is_delete", 0)->where("firm_id",HelperLibrary::getFirmID())->paginate(HelperLibrary::perPage());
        }
        return FirmUsersResource::collection($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $success = false;
        if (HelperLibrary::isAdmin()) {
            Auth::guard("firm_users")->logout();
            Auth::guard("users")->logout();
            Auth::guard("firm_users")->loginUsingId($request->id);
            $success = true;
        }
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
        $stripe = new StripeLibrary($request->firm);
        $row = FirmUser::where('email',$request->email)->first();
        if($row != null) {
            $success = false;
            return response()->json([
                "success" => $success
            ]);
        } else {
            $row = new FirmUser;
            $row->firm_id = $request->firm;
            if ($request->filled("display_name")) {
                $row->display_name = $request->display_name;
            } else {
                $row->display_name = "{$request->last_name}, {$request->first_name}";
            }
            $row->first_name = $request->first_name;
            $row->middle_name = $request->middle_name;
            $row->last_name = $request->last_name;
            $row->address = $request->address;
            $row->contact = $request->contact;
            $row->email = $request->email;
            $row->password = Hash::make($request->password);
            $row->firm_role_id = $request->role;
            $row->created_by = HelperLibrary::getLoggedInUser()->id;
            $row->updated_by = HelperLibrary::getLoggedInUser()->id;
            if ($row->save()) {
                    Mail::to($row->email)->queue(new FirmUserCreate($row));
                    if($row->firm->subscribedToPlan('ftt_monthly', 'main'))
                    {
                        $stripe->subscribeUser();
                    }
                    $success = true;


            } else {
                $success = false;
            }
            return response()->json([
                "success" => $success
            ]);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $row = FirmUser::find($id);
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
     * @return FirmUserResource
     */
    public function edit($id)
    {
        $data = FirmUser::where("is_delete", 0)->where("id", $id)->first();
        return new FirmUserResource($data);
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
        $row = FirmUser::find($id);
        $row->firm_id = $request->firm;
        if ($request->filled("display_name")) {
            $row->display_name = $request->display_name;
        }
        $row->first_name = $request->first_name;
        $row->middle_name = $request->middle_name;
        $row->last_name = $request->last_name;
        $row->address = $request->address;
        $row->contact = $request->contact;
        $row->email = $request->email;
        if ($request->filled("password")) {
            $row->password = Hash::make($request->password);
        }
        $row->firm_role_id = $request->role;
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
        $row = FirmUser::find($id);
        $stripe = new StripeLibrary($row->firm_id);
        $row->is_delete = 1;
        $row->updated_by = HelperLibrary::getLoggedInUser()->id;
        if ($row->save()) {
            if($row->firm->subscribedToPlan('ftt_monthly', 'main'))
            {
                $stripe->subscribeUser();
            }
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
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function activityLogs(Request $request)
    {
        $logs = ActivityLog::where("firm_user_id", $request->id)->orderBy("id", "desc")->paginate(HelperLibrary::perPage());
        return FirmUserActivityLogs::collection($logs);
    }
}
