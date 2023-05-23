<?php

namespace App\Http\Controllers\Support;

use App\Http\Libraries\HelperLibrary;
use App\Http\Resources\Support\SingleTicketResource;
use App\Http\Resources\Support\SingleTicketViewResource;
use App\Http\Resources\Support\TicketResource;
use App\Mail\SupportTicketGeneration;
use App\SupportTicket;
use App\SupportTicketReply;
use App\SupportTicketReplyAttachment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        if (HelperLibrary::getUserRole() == "firm_user") {
            $data = SupportTicket::NotDeleted()
                ->FirmUser(HelperLibrary::getLoggedInUser()->id)
                ->orderBy("id", "desc");
        } else {
            $data = SupportTicket::NotDeleted()
                ->orderBy("id", "desc");
        }
        return TicketResource::collection($data->paginate(HelperLibrary::perPage()));
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
            'subject' => 'required',
            'description'=>'required',
            'priority'=>'required',
        ]);
        $ticket = new SupportTicket();
        $ticket->subject = $request->subject;
        $ticket->priority = $request->priority;
        $ticket->status = 1;
        $ticket->firm_user_id = HelperLibrary::getLoggedInUser()->id;
        $ticket->save();
        $ticket_reply = new SupportTicketReply();
        $ticket_reply->support_ticket_id = $ticket->id;
        $ticket_reply->user_id = $ticket->firm_user_id;
        $ticket_reply->user_type = "firm_user";
        $ticket_reply->comment = $request->description;
        $ticket_reply->save();
        if($request->hasFile('file')) {
            $name = $request->file->getClientOriginalName();
            $tmpname = time() . $name;
            $ticket_attachment = new SupportTicketReplyAttachment();
            $ticket_attachment->support_ticket_reply_id = $ticket_reply->id;
            $ticket_attachment->name = $name;
            $ticket_attachment->file = $request->file->storeAs("attachments", $tmpname, "public");
            $ticket_attachment->save();
        }
        Mail::to("support@firmtrak.com")->queue(new SupportTicketGeneration($ticket));
        return response()->json([
            "success" => true
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return SingleTicketViewResource
     */
    public function show($id)
    {
        $data = SupportTicket::find($id);
        return new SingleTicketViewResource($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return SingleTicketResource
     */
    public function edit($id)
    {
        $data = SupportTicket::find($id);
        return new SingleTicketResource($data);
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
            'subject' => 'required',
            'description1'=>'required',
            'priority'=>'required',
        ]);
        $ticket = SupportTicket::find($id);
        $ticket->subject = $request->subject;
        $ticket->priority = $request->priority;
        if (HelperLibrary::getUserRole() != "firm_user") {
            $ticket->status = $request->status_number;
        }
        $ticket->getPrimaryReply()->comment = $request->description1;
        if ($request->attachment_state=="deleted") {
            SupportTicketReplyAttachment::find($request->attachment_id)->delete();
        }
        $ticket->push();
        if($request->hasFile('new_attachment')) {
            $name = $request->new_attachment->getClientOriginalName();
            $tmpname = time() . $name;
            $ticket_attachment = new SupportTicketReplyAttachment();
            $ticket_attachment->support_ticket_reply_id = $ticket->getPrimaryReply()->id;
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
        SupportTicket::find($id)->delete();
        return response()->json([
            "success" => true
        ]);
    }
}
