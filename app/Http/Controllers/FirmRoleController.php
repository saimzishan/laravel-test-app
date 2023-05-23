<?php

namespace App\Http\Controllers;

use App\FirmRole;
use App\FirmRolePermission;
use App\Http\Libraries\HelperLibrary;
use App\Http\Resources\FirmRoleListResource;
use App\Http\Resources\FirmRolePermissionResource;
use App\Http\Resources\FirmRolesResource;
use App\PackageRole;
use Illuminate\Http\Request;

class FirmRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $data = FirmRole::where("is_delete", 0)
            ->where("firm_id", HelperLibrary::getFirmID())
            ->paginate(HelperLibrary::perPage());
        return FirmRolesResource::collection($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function create(Request $request)
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
        $roles = [];
        $row = new FirmRole;
        $row->firm_id = HelperLibrary::getFirmID();
        $row->name = $request->name;
        $row->created_by = HelperLibrary::getLoggedInUser()->id;
        $row->updated_by = HelperLibrary::getLoggedInUser()->id;
        foreach ($request->roles as $role) {
            $role = (object) $role;
            $per = new FirmRolePermission;
            $per->firm_id = $row->firm_id;
            $per->type = $role->type;
            $per->slug = $role->slug;
            $per->is_allowed = $role->is_allowed;
            $per->created_by = HelperLibrary::getLoggedInUser()->id;
            $per->updated_by = HelperLibrary::getLoggedInUser()->id;
            $roles[] = $per;
        }
        if ($row->save()) {
            $row->permissions()->saveMany($roles);
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
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function show(Request $request, $id)
    {
        $data = FirmRole::where("firm_id", HelperLibrary::getFirmID())
            ->where("is_delete", 0)->get();
        return FirmRoleListResource::collection($data);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return FirmRolesResource
     */
    public function edit($id)
    {
        $data = FirmRole::where("is_delete", 0)->where("id", $id)->first();
        return new FirmRolesResource($data);
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
        $roles = [];
        $row = FirmRole::find($id);
        $row->name = $request->name;
        $row->updated_by = HelperLibrary::getLoggedInUser()->id;
        foreach ($request->roles as $role) {
            $role = (object) $role;
            $per = FirmRolePermission::find($role->id);
            $per->type = $role->type;
            $per->slug = $role->slug;
            $per->is_allowed = $role->is_allowed;
            $per->updated_by = HelperLibrary::getLoggedInUser()->id;
            $roles[] = $per;
        }
        if ($row->save()) {
            $row->permissions()->saveMany($roles);
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
        $row = FirmRole::find($id);
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


}
