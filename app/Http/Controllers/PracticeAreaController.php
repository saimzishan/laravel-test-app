<?php

namespace App\Http\Controllers;

use App\Http\Libraries\HelperLibrary;
use App\Http\Resources\PracticeAreaResource;
use App\Http\Resources\PracticeAreasResource;
use App\PracticeArea;
use Illuminate\Http\Request;

class PracticeAreaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $data = PracticeArea::where("is_delete", 0)->paginate(HelperLibrary::perPage());
        return PracticeAreasResource::collection($data);
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
        $row = new PracticeArea();
        $row->name = $request->name;
        $row->created_by = HelperLibrary::getLoggedInUser()->id;
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $row = PracticeArea::find($id);
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
     * @return PracticeAreaResource
     */
    public function edit($id)
    {
        $data = PracticeArea::where("is_delete", 0)->where("id", $id)->first();
        return new PracticeAreaResource($data);
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
        $row = PracticeArea::find($id);
        $row->name = $request->name;
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
        $row = PracticeArea::find($id);
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
