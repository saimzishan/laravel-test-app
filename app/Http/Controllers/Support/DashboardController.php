<?php

namespace App\Http\Controllers\Support;

use App\Http\Libraries\HelperLibrary;
use App\Http\Resources\Support\TicketResource;
use App\SupportTicket;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        if (HelperLibrary::getLoggedInUser()->getType() == "firm_user") {
            $total = SupportTicket::NotDeleted()->FirmUser(HelperLibrary::getLoggedInUser()->id)->count();
            $closed = SupportTicket::NotDeleted()->FirmUser(HelperLibrary::getLoggedInUser()->id)->where('status', 6)->count();
            $new = SupportTicket::NotDeleted()->FirmUser(HelperLibrary::getLoggedInUser()->id)->where('status', 1)->count();
            $in_progress = SupportTicket::NotDeleted()->FirmUser(HelperLibrary::getLoggedInUser()->id)->whereIn('status', [2, 3, 5])->count();
            $tickets = SupportTicket::NotDeleted()->FirmUser(HelperLibrary::getLoggedInUser()->id);
        } else {
            $total = SupportTicket::NotDeleted()->count();
            $closed = SupportTicket::NotDeleted()->where('status', 6)->count();
            $new = SupportTicket::NotDeleted()->where('status', 1)->count();
            $in_progress = SupportTicket::NotDeleted()->whereIn('status', [2, 3, 5])->count();
            $tickets = SupportTicket::NotDeleted();
        }
        $tickets = $tickets->orderBy("id", "desc")->limit(5)->get();
        return response()->json([
            "count" => [
                "total" => $total,
                "closed" => $closed,
                "new" => $new,
                "in_progress" => $in_progress,
            ],
            "tickets" => TicketResource::collection($tickets)
        ]);
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
