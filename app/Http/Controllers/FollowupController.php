<?php

namespace App\Http\Controllers;

use App\CLContact;
use App\Followup;
use App\Http\Libraries\HelperLibrary;
use App\Http\Resources\FollowupResource;
use App\Http\Resources\FollowupsResource;
use App\PPContact;
use Illuminate\Http\Request;

class FollowupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $data = Followup::notdeleted()->where("firm_id", HelperLibrary::getFirmID())
            ->where("pp_account_id", $request->account_id)->paginate(HelperLibrary::perPage());
        return FollowupsResource::collection($data);
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
        $row = new Followup();
        $row->firm_id = HelperLibrary::getFirmID();
        $row->pp_account_id = $request->account_id;
        $row->type = $request->type;
        $row->date = $request->date;
        $row->comment = $request->comment;
        $row->reply_date = $request->reply_date;
        $row->reply_response = $request->reply_response;
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
    public function show($id)
    {
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
            $contact = PPContact::where("firm_id", HelperLibrary::getFirmID())->where("pp_account_id", $id)->where("is_primary", 1)->first();
            $phone = $contact->phone_no ? $contact->phone_no : $contact->phone_work;
            $email = $contact->email;
        } else {
            $contact = CLContact::find($id);
            $phone = $contact->primary_phone_no;
            $email = $contact->primary_email_address;
        }
        return response()->json([
            "display_name" => $contact->getDisplayName(),
            "contact" => $phone,
            "email" => $email,
            "outstanding_amount" => "$" . number_format(round($contact->getInvoicesTotalOutstanding(), 2)),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @param int $id
     * @return FollowupResource
     */
    public function edit(Request $request, $id)
    {
        $data = Followup::notdeleted()->where("firm_id", HelperLibrary::getFirmID())
            ->where("id", $request->id)->first();
        return new FollowupResource($data);
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
        $row = Followup::find($id);
        $row->type = $request->type;
        $row->date = $request->date;
        $row->comment = $request->comment;
        $row->reply_date = $request->reply_date;
        $row->reply_response = $request->reply_response;
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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $row = Followup::find($id);
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
