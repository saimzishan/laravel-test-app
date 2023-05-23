<?php

namespace App\Http\Controllers\Support;

use App\Http\Libraries\HelperLibrary;
use App\Http\Resources\Support\SingleTicketReplyResource;
use App\SupportTicketReply;
use App\Mail\SupportTicketReply as STRM;
use App\SupportTicketReplyAttachment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mail;

class TicketReplyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        $request->validate([
            'comment' => 'required',
            'support_ticket_id'=>'required',
        ]);
        $ticket_reply = new SupportTicketReply();
        $ticket_reply->support_ticket_id = $request->support_ticket_id;
        $ticket_reply->user_id = HelperLibrary::getLoggedInUser()->id;
        $ticket_reply->user_type = HelperLibrary::getUserRole();
        $ticket_reply->comment = $request->comment;
        $ticket_reply->save();
        if($request->hasFile('attachment')) {
            $name = $request->attachment->getClientOriginalName();
            $tmpname = time() . $name;
            $ticket_attachment = new SupportTicketReplyAttachment();
            $ticket_attachment->support_ticket_reply_id = $ticket_reply->id;
            $ticket_attachment->name = $name;
            $ticket_attachment->file = $request->attachment->storeAs("attachments", $tmpname, "public");
            $ticket_attachment->save();
        }
        $sendTo = $ticket_reply->relationTicket->firm_user_id == $ticket_reply->user_id
            ? "support@firmtrak.com" : $ticket_reply->user->email;
        Mail::to($sendTo)->queue(new STRM($ticket_reply));
        return response()->json([
            "success" => true
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
     * @return SingleTicketReplyResource
     */
    public function edit($id)
    {
        $data = SupportTicketReply::find($id);
        return new SingleTicketReplyResource($data);
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
        $request->validate([
            'comment'=>'required',
        ]);
        $reply = SupportTicketReply::find($id);
        $reply->comment = $request->comment;
        $reply->save();
        if ($request->attachment_state=="deleted") {
            SupportTicketReplyAttachment::find($request->attachment_id)->delete();
        }
        if($request->hasFile('new_attachment')) {
            $name = $request->new_attachment->getClientOriginalName();
            $tmpname = time() . $name;
            $ticket_attachment = new SupportTicketReplyAttachment();
            $ticket_attachment->support_ticket_reply_id = $reply->id;
            $ticket_attachment->name = $name;
            $ticket_attachment->file = $request->new_attachment->storeAs("attachments", $tmpname, "public");
            $ticket_attachment->save();
        }
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
        SupportTicketReply::find($id)->delete();
        return response()->json([
            "success" => true
        ]);
    }
}
