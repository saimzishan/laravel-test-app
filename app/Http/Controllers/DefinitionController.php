<?php

namespace App\Http\Controllers;

use App\Definition;
use App\FirmIntegration;
use App\Jobs\FirmSummaryGenerateJob;
use Illuminate\Http\Request;

class DefinitionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ret = [];
        $data = Definition::where("firm_id", \App\Http\Libraries\HelperLibrary::getFirmID())->get();
        foreach ($data as $row) {
            $ret[$row->category][$row->type][$row->label] = $row->value;
        }
        return response()->json($ret);
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
        $success = true;
        foreach ($request->except('category') as $type=>$data) {
            foreach ($data as $label=>$value) {
                $row = Definition::where("firm_id", \App\Http\Libraries\HelperLibrary::getFirmID())
                    ->where("category", $request->category)
                    ->where("type", $type)
                    ->where("label", $label)->first();
                $row->value = $value;
                $success = $row->save();
                if($success)
                {

                    FirmSummaryGenerateJob::dispatch($row->firm_id, "arAging","",null)->onQueue("summary");
                    FirmSummaryGenerateJob::dispatch($row->firm_id, "arAgingDetail","",null)->onQueue("summary");
                }
            }
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
}
