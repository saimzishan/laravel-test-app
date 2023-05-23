<?php

namespace App\Http\Controllers;

use App\FirmIntegration;
use App\PPInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Libraries\PPToCLfirmTRAKLibrary;
use App\Http\Libraries\ClioPostLibrary;
use App\Http\Libraries\PracticePantherPostLibrary;

class PostController extends Controller
{
    public function index()
    {
        $lib = new PracticePantherPostLibrary(1);
        return $lib->sendContact();

    }
    public function sendDummy($id)
    {
        $lib = new PracticePantherPostLibrary($id);
        return $lib->dummyAccount();

    }
    public function sendtoDummyClio($id)
    {
        $lib = new ClioPostLibrary($id);
        $a[] = $lib->syncUsers();
        $a[] = $lib->syncContacts();
        $a[] = $lib->syncMatters();
        $a[] = $lib->syncTasks();
        $a[] = $lib->syncTimeEntries();
        $a[] = $lib->syncInvoices();
        $a[] = $lib->syncPracticeAreas();
        $a[] = $lib->syncInvoiceLineItems();
        $a[] = $lib->syncCredits();
        $a[] = $lib->dummyAccount();
     
        
        return $a;

    }
   public function changeNameDummyClio($id)
    {
        $lib = new ClioPostLibrary($id);
        $a[] = $lib->dummyAccount();
        
        return $a;

    }
    public function sendPPToCLDummy()
    {
        $lib = new PPToCLfirmTRAKLibrary(25);
        $ret[] = $lib->syncUsers();
        $ret[] = $lib->syncContacts();
        $ret[] = $lib->syncMatters();
        $ret[] = $lib->syncTasks();
        $ret[] = $lib->syncTimeEntries();
        $ret[] = $lib->syncInvoices();
        return $ret;

    }
    public function test()
    {
        PPInvoice::calcRevenue("2019-02", "month", 1);
//        $integration = FirmIntegration::where("firm_id", 79)->first();
//        $temp2 = Carbon::createFromFormat("Y-m-d H:i:s",$integration->updated_at);
//        $temp2->subDays( 2);
//        $temp1 = $temp2->toAtomString();
//
//        $d = substr($temp1,0,19);
//        dd($temp2,$temp1,$d);

    }
}
