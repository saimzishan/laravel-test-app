<?php

namespace App\Http\Controllers;

use App\Http\Resources\PromotionResource;
use App\Promotion;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $data = Promotion::notDeleted()->get();
        return PromotionResource::collection($data);
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
        $row = Promotion::create([
            "name" => $request->name,
            "stripe_plan_id" => $request->stripe_plan_id,
            "validity" => $request->validity,
            "start_date" => $request->start_date,
            "end_date" => $request->end_date,
        ]);
        return response()->json([
            "success" => true,
            "id" => $row->id,
            "start_date_formatted" => (new Carbon($row->start_date))->format("m/d/Y"),
            "end_date_formatted" => (new Carbon($row->end_date))->format("m/d/Y"),
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
        Promotion::find($id)->fill([
            "name" => $request->name,
            "stripe_plan_id" => $request->stripe_plan_id,
            "validity" => $request->validity,
            "start_date" => $request->start_date,
            "end_date" => $request->end_date,
        ])->save();
        return response()->json([
            "success" => true,
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
        Promotion::find($id)->fill([
            "is_delete" => 1,
        ])->save();
        return response()->json([
            "success" => true,
        ]);
    }
}
